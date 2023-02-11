<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Clusters;

use Pterodactyl\Models\Cluster;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use Pterodactyl\Services\Clusters\ClusterUpdateService;
use Pterodactyl\Services\Clusters\ClusterCreationService;
use Pterodactyl\Services\Clusters\ClusterDeletionService;
use Pterodactyl\Transformers\Api\Application\ClusterTransformer;
use Pterodactyl\Http\Requests\Api\Application\Clusters\GetClusterRequest;
use Pterodactyl\Http\Requests\Api\Application\Clusters\GetClustersRequest;
use Pterodactyl\Http\Requests\Api\Application\Clusters\StoreClusterRequest;
use Pterodactyl\Http\Requests\Api\Application\Clusters\DeleteClusterRequest;
use Pterodactyl\Http\Requests\Api\Application\Clusters\UpdateClusterRequest;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class ClusterController extends ApplicationApiController
{
    /**
     * ClusterController constructor.
     */
    public function __construct(
        private ClusterCreationService $creationService,
        private ClusterDeletionService $deletionService,
        private ClusterUpdateService $updateService
    ) {
        parent::__construct();
    }

    /**
     * Return all the clusters currently available on the Panel.
     */
    public function index(GetClustersRequest $request): array
    {
        $clusters = QueryBuilder::for(Cluster::query())
            ->allowedFilters(['uuid', 'name', 'fqdn', 'daemon_token_id'])
            ->allowedSorts(['id', 'uuid', 'memory', 'disk'])
            ->paginate($request->query('per_page') ?? 50);

        return $this->fractal->collection($clusters)
            ->transformWith($this->getTransformer(ClusterTransformer::class))
            ->toArray();
    }

    /**
     * Return data for a single instance of a cluster.
     */
    public function view(GetClusterRequest $request, Cluster $cluster): array
    {
        return $this->fractal->item($cluster)
            ->transformWith($this->getTransformer(ClusterTransformer::class))
            ->toArray();
    }

    /**
     * Create a new cluster on the Panel. Returns the created cluster and an HTTP/201
     * status response on success.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(StoreClusterRequest $request): JsonResponse
    {
        $cluster = $this->creationService->handle($request->validated());

        return $this->fractal->item($cluster)
            ->transformWith($this->getTransformer(ClusterTransformer::class))
            ->addMeta([
                'resource' => route('api.application.clusters.view', [
                    'cluster' => $cluster->id,
                ]),
            ])
            ->respond(201);
    }

    /**
     * Update an existing cluster on the Panel.
     *
     * @throws \Throwable
     */
    public function update(UpdateClusterRequest $request, Cluster $cluster): array
    {
        $cluster = $this->updateService->handle(
            $cluster,
            $request->validated(),
            $request->input('reset_secret') === true
        );

        return $this->fractal->item($cluster)
            ->transformWith($this->getTransformer(ClusterTransformer::class))
            ->toArray();
    }

    /**
     * Deletes a given cluster from the Panel as long as there are no servers
     * currently attached to it.
     *
     * @throws \Pterodactyl\Exceptions\Service\HasActiveServersException
     */
    public function delete(DeleteClusterRequest $request, Cluster $cluster): JsonResponse
    {
        $this->deletionService->handle($cluster);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
