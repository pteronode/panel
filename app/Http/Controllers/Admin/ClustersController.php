<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\View\View;
use Pterodactyl\Models\Cluster;
use Pterodactyl\Models\Allocation;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Clusters\ClusterUpdateService;
use Illuminate\Cache\Repository as CacheRepository;
use Pterodactyl\Services\Clusters\ClusterCreationService;
use Pterodactyl\Services\Clusters\ClusterDeletionService;
use Pterodactyl\Services\Allocations\AssignmentService;
use Pterodactyl\Services\Helpers\SoftwareVersionService;
use Pterodactyl\Http\Requests\Admin\Cluster\ClusterFormRequest;
use Pterodactyl\Contracts\Repository\ClusterRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Cluster\AllocationFormRequest;
use Pterodactyl\Services\Allocations\AllocationDeletionService;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Cluster\AllocationAliasFormRequest;

class ClustersController extends Controller
{
    /**
     * ClustersController constructor.
     */
    public function __construct(
        protected AlertsMessageBag $alert,
        protected AllocationDeletionService $allocationDeletionService,
        protected AllocationRepositoryInterface $allocationRepository,
        protected AssignmentService $assignmentService,
        protected CacheRepository $cache,
        protected ClusterCreationService $creationService,
        protected ClusterDeletionService $deletionService,
        protected LocationRepositoryInterface $locationRepository,
        protected ClusterRepositoryInterface $repository,
        protected ServerRepositoryInterface $serverRepository,
        protected ClusterUpdateService $updateService,
        protected SoftwareVersionService $versionService,
        protected ViewFactory $view
    ) {
    }

    /**
     * Displays create new cluster page.
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
     * Post controller to create a new cluster on the system.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(ClusterFormRequest $request): RedirectResponse
    {
        $cluster = $this->creationService->handle($request->normalize());
        // $this->alert->info(trans('admin/node.notices.node_created'))->flash();

        return redirect()->route('admin.clusters.view.configuration', $cluster->id);
    }

    /**
     * Updates settings for a cluster.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function updateSettings(ClusterFormRequest $request, Cluster $cluster): RedirectResponse
    {
        $this->updateService->handle($cluster, $request->normalize(), $request->input('reset_secret') === 'on');
        $this->alert->success(trans('admin/node.notices.node_updated'))->flash();

        return redirect()->route('admin.clusters.view.settings', $cluster->id)->withInput();
    }

    /**
     * Removes a single allocation from a node.
     *
     * @throws \Pterodactyl\Exceptions\Service\Allocation\ServerUsingAllocationException
     */
    public function allocationRemoveSingle(int $node, Allocation $allocation): Response
    {
        $this->allocationDeletionService->handle($allocation);

        return response('', 204);
    }

    /**
     * Removes multiple individual allocations from a node.
     *
     * @throws \Pterodactyl\Exceptions\Service\Allocation\ServerUsingAllocationException
     */
    public function allocationRemoveMultiple(Request $request, int $node): Response
    {
        $allocations = $request->input('allocations');
        foreach ($allocations as $rawAllocation) {
            $allocation = new Allocation();
            $allocation->id = $rawAllocation['id'];
            $this->allocationRemoveSingle($node, $allocation);
        }

        return response('', 204);
    }

    /**
     * Remove all allocations for a specific IP at once on a node.
     */
    public function allocationRemoveBlock(Request $request, int $cluster): RedirectResponse
    {
        $this->allocationRepository->deleteWhere([
            ['cluster_id', '=', $cluster],
            ['server_id', '=', null],
            ['ip', '=', $request->input('ip')],
        ]);

        $this->alert->success(trans('admin/node.notices.unallocated_deleted', ['ip' => $request->input('ip')]))
            ->flash();

        return redirect()->route('admin.clusters.view.allocation', $cluster);
    }

    /**
     * Sets an alias for a specific allocation on a cluster.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function allocationSetAlias(AllocationAliasFormRequest $request): \Symfony\Component\HttpFoundation\Response
    {
        $this->allocationRepository->update($request->input('allocation_id'), [
            'ip_alias' => (empty($request->input('alias'))) ? null : $request->input('alias'),
        ]);

        return response('', 204);
    }

    /**
     * Creates new allocations on a cluster.
     *
     * @throws \Pterodactyl\Exceptions\Service\Allocation\CidrOutOfRangeException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\InvalidPortMappingException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\PortOutOfRangeException
     * @throws \Pterodactyl\Exceptions\Service\Allocation\TooManyPortsInRangeException
     */
    public function createAllocation(AllocationFormRequest $request, Cluster $cluster): RedirectResponse
    {
        $this->assignmentService->handle($cluster, $request->normalize());
        $this->alert->success(trans('admin/node.notices.allocations_added'))->flash();

        return redirect()->route('admin.clusters.view.allocation', $cluster->id);
    }

    /**
     * Deletes a cluster from the system.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete(int|Cluster $cluster): RedirectResponse
    {
        $this->deletionService->handle($cluster);
        $this->alert->success(trans('admin/node.notices.node_deleted'))->flash();

        return redirect()->route('admin.clusters');
    }
}
