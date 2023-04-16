<?php

namespace Kubectyl\Services\Deployment;

use Kubectyl\Models\Cluster;
use Webmozart\Assert\Assert;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Kubectyl\Exceptions\Service\Deployment\NoViableClusterException;

class FindViableClustersService
{
    protected array $locations = [];
    protected ?int $disk = null;
    protected ?int $memory = null;

    /**
     * Set the locations that should be searched through to locate available clusters.
     */
    public function setLocations(array $locations): self
    {
        Assert::allIntegerish($locations, 'An array of location IDs should be provided when calling setLocations.');

        $this->locations = $locations;

        return $this;
    }

    /**
     * Set the amount of disk that will be used by the server being created. Clusters will be
     * filtered out if they do not have enough available free disk space for this server
     * to be placed on.
     */
    public function setDisk(int $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * Set the amount of memory that this server will be using. As with disk space, clusters that
     * do not have enough free memory will be filtered out.
     */
    public function setMemory(int $memory): self
    {
        $this->memory = $memory;

        return $this;
    }

    /**
     * Returns an array of clusters that meet the provided requirements and can then
     * be passed to the AllocationSelectionService to return a single allocation.
     *
     * This functionality is used for automatic deployments of servers and will
     * attempt to find all clusters in the defined locations that meet the disk and
     * memory availability requirements. Any clusters not meeting those requirements
     * are tossed out, as are any clusters marked as non-public, meaning automatic
     * deployments should not be done against them.
     *
     * @param int|null $page If provided the results will be paginated by returning
     *                       up to 50 clusters at a time starting at the provided page.
     *                       If "null" is provided as the value no pagination will
     *                       be used.
     *
     * @throws \Kubectyl\Exceptions\Service\Deployment\NoViableClusterException
     */
    public function handle(int $perPage = null, int $page = null): LengthAwarePaginator|Collection
    {
        Assert::integer($this->disk, 'Disk space must be an int, got %s');
        Assert::integer($this->memory, 'Memory usage must be an int, got %s');

        $query = Cluster::query()->select('clusters.*')
            ->selectRaw('IFNULL(SUM(servers.memory), 0) as sum_memory')
            ->selectRaw('IFNULL(SUM(servers.disk), 0) as sum_disk')
            ->leftJoin('servers', 'servers.cluster_id', '=', 'clusters.id')
            ->where('clusters.public', 1);

        if (!empty($this->locations)) {
            $query = $query->whereIn('clusters.location_id', $this->locations);
        }

        $results = $query->groupBy('clusters.id')
            ->havingRaw('(IFNULL(SUM(servers.memory), 0) + ?) <= (clusters.memory * (1 + (clusters.memory_overallocate / 100)))', [$this->memory])
            ->havingRaw('(IFNULL(SUM(servers.disk), 0) + ?) <= (clusters.disk * (1 + (clusters.disk_overallocate / 100)))', [$this->disk]);

        if (!is_null($page)) {
            $results = $results->paginate($perPage ?? 50, ['*'], 'page', $page);
        } else {
            $results = $results->get()->toBase();
        }

        if ($results->isEmpty()) {
            throw new NoViableClusterException(trans('exceptions.deployment.no_viable_clusters'));
        }

        return $results;
    }
}
