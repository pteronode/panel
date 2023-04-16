<?php

namespace Kubectyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Request;
use Kubectyl\Models\Snapshot;
use Kubectyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Kubectyl\Facades\Activity;
use Kubectyl\Models\Permission;
use Illuminate\Auth\Access\AuthorizationException;
use Kubectyl\Services\Backups\DeleteBackupService;
use Kubectyl\Services\Backups\DownloadLinkService;
use Kubectyl\Repositories\Eloquent\BackupRepository;
use Kubectyl\Services\Backups\InitiateBackupService;
use Kubectyl\Repositories\Kuber\DaemonBackupRepository;
use Kubectyl\Transformers\Api\Client\BackupTransformer;
use Kubectyl\Http\Controllers\Api\Client\ClientApiController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Kubectyl\Http\Requests\Api\Client\Servers\Backups\StoreBackupRequest;

class BackupController extends ClientApiController
{
    /**
     * BackupController constructor.
     */
    public function __construct(
        private DaemonBackupRepository $daemonRepository,
        private DeleteBackupService $deleteBackupService,
        private InitiateBackupService $initiateBackupService,
        private DownloadLinkService $downloadLinkService,
        private BackupRepository $repository
    ) {
        parent::__construct();
    }

    /**
     * Returns all the snapshots for a given server instance in a paginated
     * result set.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request, Server $server): array
    {
        if (!$request->user()->can(Permission::ACTION_BACKUP_READ, $server)) {
            throw new AuthorizationException();
        }

        $limit = min($request->query('per_page') ?? 20, 50);

        return $this->fractal->collection($server->snapshots()->paginate($limit))
            ->transformWith($this->getTransformer(BackupTransformer::class))
            ->addMeta([
                'snapshot_count' => $this->repository->getNonFailedBackups($server)->count(),
            ])
            ->toArray();
    }

    /**
     * Starts the snapshot process for a server.
     *
     * @throws \Spatie\Fractalistic\Exceptions\InvalidTransformation
     * @throws \Spatie\Fractalistic\Exceptions\NoTransformerSpecified
     * @throws \Throwable
     */
    public function store(StoreBackupRequest $request, Server $server): array
    {
        $action = $this->initiateBackupService
            ->setIgnoredFiles(explode(PHP_EOL, $request->input('ignored') ?? ''));

        // Only set the lock status if the user even has permission to delete snapshots,
        // otherwise ignore this status. This gets a little funky since it isn't clear
        // how best to allow a user to create a snapshot that is locked without also preventing
        // them from just filling up a server with snapshots that can never be deleted?
        if ($request->user()->can(Permission::ACTION_BACKUP_DELETE, $server)) {
            $action->setIsLocked((bool) $request->input('is_locked'));
        }

        $snapshot = $action->handle($server, $request->input('name'));

        Activity::event('server:snapshot.start')
            ->subject($snapshot)
            ->property(['name' => $snapshot->name, 'locked' => (bool) $request->input('is_locked')])
            ->log();

        return $this->fractal->item($snapshot)
            ->transformWith($this->getTransformer(BackupTransformer::class))
            ->toArray();
    }

    /**
     * Toggles the lock status of a given snapshot for a server.
     *
     * @throws \Throwable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function toggleLock(Request $request, Server $server, Snapshot $snapshot): array
    {
        if (!$request->user()->can(Permission::ACTION_BACKUP_DELETE, $server)) {
            throw new AuthorizationException();
        }

        $action = $snapshot->is_locked ? 'server:snapshot.unlock' : 'server:snapshot.lock';

        $snapshot->update(['is_locked' => !$snapshot->is_locked]);

        Activity::event($action)->subject($snapshot)->property('name', $snapshot->name)->log();

        return $this->fractal->item($snapshot)
            ->transformWith($this->getTransformer(BackupTransformer::class))
            ->toArray();
    }

    /**
     * Returns information about a single snapshot.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function view(Request $request, Server $server, Snapshot $snapshot): array
    {
        if (!$request->user()->can(Permission::ACTION_BACKUP_READ, $server)) {
            throw new AuthorizationException();
        }

        return $this->fractal->item($snapshot)
            ->transformWith($this->getTransformer(BackupTransformer::class))
            ->toArray();
    }

    /**
     * Deletes a snapshot from the panel as well as the remote source where it is currently
     * being stored.
     *
     * @throws \Throwable
     */
    public function delete(Request $request, Server $server, Snapshot $snapshot): JsonResponse
    {
        if (!$request->user()->can(Permission::ACTION_BACKUP_DELETE, $server)) {
            throw new AuthorizationException();
        }

        $this->deleteBackupService->handle($snapshot);

        Activity::event('server:snapshot.delete')
            ->subject($snapshot)
            ->property(['name' => $snapshot->name, 'failed' => !$snapshot->is_successful])
            ->log();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Download the snapshot for a given server instance. For daemon local files, the file
     * will be streamed back through the Panel. For AWS S3 files, a signed URL will be generated
     * which the user is redirected to.
     *
     * @throws \Throwable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function download(Request $request, Server $server, Snapshot $snapshot): JsonResponse
    {
        if (!$request->user()->can(Permission::ACTION_BACKUP_DOWNLOAD, $server)) {
            throw new AuthorizationException();
        }

        if ($snapshot->disk !== Snapshot::ADAPTER_AWS_S3 && $snapshot->disk !== Snapshot::ADAPTER_KUBER) {
            throw new BadRequestHttpException('The snapshot requested references an unknown disk driver type and cannot be downloaded.');
        }

        $url = $this->downloadLinkService->handle($snapshot, $request->user());

        Activity::event('server:snapshot.download')->subject($snapshot)->property('name', $snapshot->name)->log();

        return new JsonResponse([
            'object' => 'signed_url',
            'attributes' => ['url' => $url],
        ]);
    }

    /**
     * Handles restoring a snapshot by making a request to the Kuber instance telling it
     * to begin the process of finding (or downloading) the snapshot and unpacking it
     * over the server files.
     *
     * If the "truncate" flag is passed through in this request then all the
     * files that currently exist on the server will be deleted before restoring.
     * Otherwise, the archive will simply be unpacked over the existing files.
     *
     * @throws \Throwable
     */
    public function restore(Request $request, Server $server, Snapshot $snapshot): JsonResponse
    {
        if (!$request->user()->can(Permission::ACTION_BACKUP_RESTORE, $server)) {
            throw new AuthorizationException();
        }

        // Cannot restore a snapshot unless a server is fully installed and not currently
        // processing a different snapshot restoration request.
        if (!is_null($server->status)) {
            throw new BadRequestHttpException('This server is not currently in a state that allows for a snapshot to be restored.');
        }

        if (!$snapshot->is_successful && is_null($snapshot->completed_at)) {
            throw new BadRequestHttpException('This snapshot cannot be restored at this time: not completed or failed.');
        }

        $log = Activity::event('server:snapshot.restore')
            ->subject($snapshot)
            ->property(['name' => $snapshot->name, 'truncate' => $request->input('truncate')]);

        $log->transaction(function () use ($snapshot, $server, $request) {
            // If the snapshot is for an S3 file we need to generate a unique Download link for
            // it that will allow Wings to actually access the file.
            if ($snapshot->disk === Snapshot::ADAPTER_AWS_S3) {
                $url = $this->downloadLinkService->handle($snapshot, $request->user());
            }

            // Update the status right away for the server so that we know not to allow certain
            // actions against it via the Panel API.
            $server->update(['status' => Server::STATUS_RESTORING_SNAPSHOT]);

            $this->daemonRepository->setServer($server)->restore($snapshot, $url ?? null, $request->input('truncate'));
        });

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
