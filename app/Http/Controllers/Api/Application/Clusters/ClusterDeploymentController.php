<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Clusters;

use Pterodactyl\Services\Deployment\FindViableClustersService;
use Pterodactyl\Transformers\Api\Application\ClusterTransformer;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Clusters\GetDeployableClustersRequest;

class ClusterDeploymentController extends ApplicationApiController
{
    /**
     * ClusterDeploymentController constructor.
     */
    public function __construct(private FindViableClustersService $viableClustersService)
    {
        parent::__construct();
    }

    /**
     * Finds any nodes that are available using the given deployment criteria. This works
     * similarly to the server creation process, but allows you to pass the deployment object
     * to this endpoint and get back a list of all Nodes satisfying the requirements.
     *
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableClusterException
     */
    public function __invoke(GetDeployableClustersRequest $request): array
    {
        $data = $request->validated();
        $clusters = $this->viableClustersService->setLocations($data['location_ids'] ?? [])
            ->setMemory($data['memory'])
            ->setDisk($data['disk'])
            ->handle($request->query('per_page'), $request->query('page'));

        return $this->fractal->collection($clusters)
            ->transformWith($this->getTransformer(ClusterTransformer::class))
            ->toArray();
    }
}
