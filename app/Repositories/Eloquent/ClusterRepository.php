<?php

namespace Kubectyl\Repositories\Eloquent;

use Kubectyl\Models\Cluster;
use Illuminate\Support\Collection;
use Kubectyl\Contracts\Repository\ClusterRepositoryInterface;

class ClusterRepository extends EloquentRepository implements ClusterRepositoryInterface
{
    /**
     * Return the model backing this repository.
     */
    public function model(): string
    {
        return Cluster::class;
    }

    /**
     * Return the usage stats for a single cluster.
     */
    public function getUsageStats(Cluster $cluster): array
    {
        $stats = $this->getBuilder()
            ->selectRaw('IFNULL(SUM(servers.memory), 0) as sum_memory, IFNULL(SUM(servers.disk), 0) as sum_disk')
            ->join('servers', 'servers.cluster_id', '=', 'clusters.id')
            ->where('cluster_id', '=', $cluster->id)
            ->first();

        return Collection::make(['disk' => $stats->sum_disk, 'memory' => $stats->sum_memory])
            ->mapWithKeys(function ($value, $key) use ($cluster) {
                $maxUsage = $cluster->{$key};
                if ($cluster->{$key . '_overallocate'} > 0) {
                    $maxUsage = $cluster->{$key} * (1 + ($cluster->{$key . '_overallocate'} / 100));
                }

                $percent = ($value / $maxUsage) * 100;

                return [
                    $key => [
                        'value' => number_format($value),
                        'max' => number_format($maxUsage),
                        'percent' => $percent,
                        'css' => ($percent <= self::THRESHOLD_PERCENTAGE_LOW) ? 'green' : (($percent > self::THRESHOLD_PERCENTAGE_MEDIUM) ? 'red' : 'yellow'),
                    ],
                ];
            })
            ->toArray();
    }

    /**
     * Return the usage stats for a single cluster.
     */
    public function getUsageStatsRaw(Cluster $cluster): array
    {
        $stats = $this->getBuilder()->select(
            $this->getBuilder()->raw('IFNULL(SUM(servers.memory), 0) as sum_memory, IFNULL(SUM(servers.disk), 0) as sum_disk')
        )->join('servers', 'servers.cluster_id', '=', 'clusters.id')->where('cluster_id', $cluster->id)->first();

        return collect(['disk' => $stats->sum_disk, 'memory' => $stats->sum_memory])->mapWithKeys(function ($value, $key) use ($cluster) {
            $maxUsage = $cluster->{$key};
            if ($cluster->{$key . '_overallocate'} > 0) {
                $maxUsage = $cluster->{$key} * (1 + ($cluster->{$key . '_overallocate'} / 100));
            }

            return [
                $key => [
                    'value' => $value,
                    'max' => $maxUsage,
                ],
            ];
        })->toArray();
    }

    /**
     * Return a single cluster with location and server information.
     */
    public function loadLocationAndServerCount(Cluster $cluster, bool $refresh = false): Cluster
    {
        if (!$cluster->relationLoaded('location') || $refresh) {
            $cluster->load('location');
        }

        // This is quite ugly and can probably be improved down the road.
        // And by probably, I mean it should.
        if (is_null($cluster->servers_count) || $refresh) {
            $cluster->load('servers');
            $cluster->setRelation('servers_count', count($cluster->getRelation('servers')));
            unset($cluster->servers);
        }

        return $cluster;
    }

    /**
     * Attach a paginated set of allocations to a cluster mode including
     * any servers that are also attached to those allocations.
     */
    public function loadClusterAllocations(Cluster $cluster, bool $refresh = false): Cluster
    {
        $cluster->setRelation(
            'allocations',
            $cluster->allocations()
                ->orderByRaw('server_id IS NOT NULL DESC, server_id IS NULL')
                ->orderByRaw('INET_ATON(ip) ASC')
                ->orderBy('port')
                ->with('server:id,name')
                ->paginate(50)
        );

        return $cluster;
    }

    /**
     * Return a collection of clusters for all locations to use in server creation UI.
     */
    public function getClustersForServerCreation(): Collection
    {
        return $this->getBuilder()->with('allocations')->get()->map(function (Cluster $item) {
            $filtered = $item->getRelation('allocations')->where('server_id', null)->map(function ($map) {
                return collect($map)->only(['id', 'ip', 'port']);
            });

            $item->ports = $filtered->map(function ($map) {
                return [
                    'id' => $map['id'],
                    'text' => sprintf('%s:%s', $map['ip'], $map['port']),
                ];
            })->values();

            return [
                'id' => $item->id,
                'text' => $item->name,
                'allocations' => $item->ports,
            ];
        })->values();
    }
}
