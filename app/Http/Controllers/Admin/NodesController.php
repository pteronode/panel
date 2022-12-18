<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\View\View;
use Pterodactyl\Models\Node;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Nodes\NodeUpdateService;
use Illuminate\Cache\Repository as CacheRepository;
use Pterodactyl\Services\Nodes\NodeCreationService;
use Pterodactyl\Services\Nodes\NodeDeletionService;
use Pterodactyl\Services\Allocations\AssignmentService;
use Pterodactyl\Services\Helpers\SoftwareVersionService;
use Pterodactyl\Http\Requests\Admin\Node\NodeFormRequest;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Services\Allocations\AllocationDeletionService;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;

class NodesController extends Controller
{
    /**
     * NodesController constructor.
     */
    public function __construct(
        protected AlertsMessageBag $alert,
        protected AllocationDeletionService $allocationDeletionService,
        protected AllocationRepositoryInterface $allocationRepository,
        protected AssignmentService $assignmentService,
        protected CacheRepository $cache,
        protected NodeCreationService $creationService,
        protected NodeDeletionService $deletionService,
        protected LocationRepositoryInterface $locationRepository,
        protected NodeRepositoryInterface $repository,
        protected ServerRepositoryInterface $serverRepository,
        protected NodeUpdateService $updateService,
        protected SoftwareVersionService $versionService,
        protected ViewFactory $view
    ) {
    }

    /**
     * Displays create new node page.
     */
    public function create(): View|RedirectResponse
    {
        $locations = $this->locationRepository->all();
        if (count($locations) < 1) {
            $this->alert->warning(trans('admin/node.notices.location_required'))->flash();

            return redirect()->route('admin.locations');
        }

        return $this->view->make('admin.clusters.new', ['locations' => $locations]);
    }

    /**
     * Post controller to create a new node on the system.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(NodeFormRequest $request): RedirectResponse
    {
        $node = $this->creationService->handle($request->normalize());
        // $this->alert->info(trans('admin/node.notices.node_created'))->flash();

        return redirect()->route('admin.clusters.view.configuration', $node->id);
    }

    /**
     * Updates settings for a node.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function updateSettings(NodeFormRequest $request, Node $node): RedirectResponse
    {
        $this->updateService->handle($node, $request->normalize(), $request->input('reset_secret') === 'on');
        $this->alert->success(trans('admin/node.notices.node_updated'))->flash();

        return redirect()->route('admin.clusters.view.settings', $node->id)->withInput();
    }

    /**
     * Deletes a node from the system.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete(int|Node $node): RedirectResponse
    {
        $this->deletionService->handle($node);
        $this->alert->success(trans('admin/node.notices.node_deleted'))->flash();

        return redirect()->route('admin.clusters');
    }
}
