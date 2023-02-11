<?php

namespace Pterodactyl\Http\Controllers\Admin\Clusters;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Pterodactyl\Models\Cluster;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Collection;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Pterodactyl\Repositories\Eloquent\ClusterRepository;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Traits\Controllers\JavascriptInjection;
use Pterodactyl\Services\Helpers\SoftwareVersionService;
use Pterodactyl\Repositories\Eloquent\LocationRepository;
use Pterodactyl\Repositories\Eloquent\AllocationRepository;

class ClusterViewController extends Controller
{
    use JavascriptInjection;

    /**
     * ClusterViewController constructor.
     */
    public function __construct(
        private AllocationRepository $allocationRepository,
        private LocationRepository $locationRepository,
        private ClusterRepository $repository,
        private ServerRepository $serverRepository,
        private SoftwareVersionService $versionService,
        private ViewFactory $view
    ) {
    }

    /**
     * Returns index view for a specific cluster on the system.
     */
    public function index(Request $request, Cluster $cluster): View
    {
        $cluster = $this->repository->loadLocationAndServerCount($cluster);

        return $this->view->make('admin.clusters.view.index', [
            'cluster' => $cluster,
            'servers' => $this->serverRepository->loadAllServersForCluster($cluster->id, 25),
            'version' => $this->versionService,
        ]);
    }

    /**
     * Returns the settings page for a specific cluster.
     */
    public function settings(Request $request, Cluster $cluster): View
    {
        $cluster['bearer_token'] = app(Encrypter::class)->decrypt($cluster['bearer_token']);

        return $this->view->make('admin.clusters.view.settings', [
            'cluster' => $cluster,
            'locations' => $this->locationRepository->all(),
        ]);
    }

    /**
     * Return the configuration page for a specific cluster.
     */
    public function configuration(Request $request, Cluster $cluster): View
    {
        return $this->view->make('admin.clusters.view.configuration', compact('cluster'));
    }

    /**
     * Return the node allocation management page.
     */
    public function allocations(Request $request, Cluster $cluster): View
    {
        $cluster = $this->repository->loadNodeAllocations($cluster);

        $this->plainInject(['cluster' => Collection::wrap($cluster)->only(['id'])]);

        return $this->view->make('admin.clusters.view.allocation', [
            'cluster' => $cluster,
            'allocations' => Allocation::query()->where('cluster_id', $cluster->id)
                ->groupBy('ip')
                ->orderByRaw('INET_ATON(ip) ASC')
                ->get(['ip']),
        ]);
    }

    /**
     * Return a listing of servers that exist for this specific node.
     */
    public function servers(Request $request, Cluster $cluster): View
    {
        $this->plainInject([
            'cluster' => Collection::wrap($cluster->makeVisible(['daemon_token_id', 'daemon_token']))
                ->only(['scheme', 'fqdn', 'daemonListen', 'daemon_token_id', 'daemon_token']),
        ]);

        return $this->view->make('admin.clusters.view.servers', [
            'cluster' => $cluster,
            'servers' => $this->serverRepository->loadAllServersForCluster($cluster->id, 25),
        ]);
    }
}
