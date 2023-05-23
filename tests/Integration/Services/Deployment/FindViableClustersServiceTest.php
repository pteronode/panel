<?php

namespace Kubectyl\Tests\Integration\Services\Deployment;

use Kubectyl\Models\Server;
use Kubectyl\Models\Cluster;
use Kubectyl\Models\Database;
use Kubectyl\Models\Location;
use Illuminate\Support\Collection;
use Kubectyl\Tests\Integration\IntegrationTestCase;
use Kubectyl\Services\Deployment\FindViableClustersService;
use Kubectyl\Exceptions\Service\Deployment\NoViableClusterException;

class FindViableClustersServiceTest extends IntegrationTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Database::query()->delete();
        Server::query()->delete();
        Cluster::query()->delete();
    }

    public function testExceptionIsThrownIfNoDiskSpaceHasBeenSet()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Disk space must be an int, got NULL');

        $this->getService()->handle();
    }

    public function testExceptionIsThrownIfNoMemoryHasBeenSet()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Memory usage must be an int, got NULL');

        $this->getService()->setDisk(10)->handle();
    }

    /**
     * Ensure that errors are not thrown back when passing in expected values.
     *
     * @see https://github.com/pterodactyl/panel/issues/2529
     */
    public function testNoExceptionIsThrownIfStringifiedIntegersArePassedForLocations()
    {
        $this->getService()->setLocations([1, 2, 3]);
        $this->getService()->setLocations(['1', '2', '3']);
        $this->getService()->setLocations(['1', 2, 3]);

        try {
            $this->getService()->setLocations(['a']);
            $this->fail('This expectation should not be called.');
        } catch (\Exception $exception) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
            $this->assertSame('An array of location IDs should be provided when calling setLocations.', $exception->getMessage());
        }

        try {
            $this->getService()->setLocations(['1.2', '1', 2]);
            $this->fail('This expectation should not be called.');
        } catch (\Exception $exception) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
            $this->assertSame('An array of location IDs should be provided when calling setLocations.', $exception->getMessage());
        }
    }

    public function testExpectedClusterIsReturnedForLocation()
    {
        /** @var \Kubectyl\Models\Location[] $locations */
        $locations = Location::factory()->times(2)->create();

        /** @var \Kubectyl\Models\Cluster[] $clusters */
        $clusters = [
            // This cluster should never be returned once we've completed the initial test which
            // runs without a location filter.
            Cluster::factory()->create([
                'location_id' => $locations[0]->id,
                'memory' => 2048,
                'disk' => 1024 * 100,
            ]),
            Cluster::factory()->create([
                'location_id' => $locations[1]->id,
                'memory' => 1024,
                'disk' => 10240,
                'disk_overallocate' => 10,
            ]),
            Cluster::factory()->create([
                'location_id' => $locations[1]->id,
                'memory' => 1024 * 4,
                'memory_overallocate' => 50,
                'disk' => 102400,
            ]),
        ];

        // Expect that all the clusters are returned as we're under all of their limits
        // and there is no location filter being provided.
        $response = $this->getService()->setDisk(512)->setMemory(512)->handle();
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertCount(3, $response);
        $this->assertInstanceOf(Cluster::class, $response[0]);

        // Expect that only the last cluster is returned because it is the only one with enough
        // memory available to this instance.
        $response = $this->getService()->setDisk(512)->setMemory(2049)->handle();
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertCount(1, $response);
        $this->assertSame($clusters[2]->id, $response[0]->id);

        // Helper, I am lazy.
        $base = function () use ($locations) {
            return $this->getService()->setLocations([$locations[1]->id])->setDisk(512);
        };

        // Expect that we can create this server on either cluster since the disk and memory
        // limits are below the allowed amount.
        $response = $base()->setMemory(512)->handle();
        $this->assertCount(2, $response);
        $this->assertSame(2, $response->where('location_id', $locations[1]->id)->count());

        // Expect that we can only create this server on the second cluster since the memory
        // allocated is over the amount of memory available to the first cluster.
        $response = $base()->setMemory(2048)->handle();
        $this->assertCount(1, $response);
        $this->assertSame($clusters[2]->id, $response[0]->id);

        // Expect that we can only create this server on the second cluster since the disk
        // allocated is over the limit assigned to the first cluster (even with the overallocate).
        $response = $base()->setDisk(20480)->setMemory(256)->handle();
        $this->assertCount(1, $response);
        $this->assertSame($clusters[2]->id, $response[0]->id);

        // Expect that we could create the server on either cluster since the disk allocated is
        // right at the limit for Cluster 1 when the overallocate value is included in the calc.
        $response = $base()->setDisk(11264)->setMemory(256)->handle();
        $this->assertCount(2, $response);

        // Create two servers on the first cluster so that the disk space used is equal to the
        // base amount available to the cluster (without overallocation included).
        $servers = Collection::make([
            $this->createServerModel(['cluster_id' => $clusters[1]->id, 'disk' => 5120]),
            $this->createServerModel(['cluster_id' => $clusters[1]->id, 'disk' => 5120]),
        ]);

        // Expect that we cannot create a server with a 1GB disk on the first cluster since there
        // is not enough space (even with the overallocate) available to the cluster.
        $response = $base()->setDisk(1024)->setMemory(256)->handle();
        $this->assertCount(1, $response);
        $this->assertSame($clusters[2]->id, $response[0]->id);

        // Cleanup servers since we need to test some other stuff with memory here.
        $servers->each->delete();

        // Expect that no viable cluster can be found when the memory limit for the given instance
        // is greater than either cluster can support, even with the overallocation limits taken
        // into account.
        $this->expectException(NoViableClusterException::class);
        $base()->setMemory(10000)->handle();

        // Create four servers so that the memory used for the second cluster is equal to the total
        // limit for that cluster (pre-overallocate calculation).
        Collection::make([
            $this->createServerModel(['cluster_id' => $clusters[2]->id, 'memory_limit' => 1024]),
            $this->createServerModel(['cluster_id' => $clusters[2]->id, 'memory_limit' => 1024]),
            $this->createServerModel(['cluster_id' => $clusters[2]->id, 'memory_limit' => 1024]),
            $this->createServerModel(['cluster_id' => $clusters[2]->id, 'memory_limit' => 1024]),
        ]);

        // Expect that either cluster can support this server when we account for the overallocate
        // value of the second cluster.
        $response = $base()->setMemory(500)->handle();
        $this->assertCount(2, $response);
        $this->assertSame(2, $response->where('location_id', $locations[1]->id)->count());

        // Expect that only the first cluster can support this server when we go over the remaining
        // memory for the second clusters overallocate calculation.
        $response = $base()->setMemory(640)->handle();
        $this->assertCount(1, $response);
        $this->assertSame($clusters[1]->id, $response[0]->id);
    }

    private function getService(): FindViableClustersService
    {
        return $this->app->make(FindViableClustersService::class);
    }
}
