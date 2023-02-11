<?php

namespace Pterodactyl\Tests\Integration\Services\Servers;

use Mockery;
use Mockery\MockInterface;
use Pterodactyl\Models\Egg;
use GuzzleHttp\Psr7\Request;
use Pterodactyl\Models\Cluster;
use Pterodactyl\Models\User;
use GuzzleHttp\Psr7\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Allocation;
use Illuminate\Foundation\Testing\WithFaker;
use GuzzleHttp\Exception\BadResponseException;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class ServerCreationServiceTest extends IntegrationTestCase
{
    use WithFaker;

    protected MockInterface $daemonServerRepository;

    protected Egg $bungeecord;

    /**
     * Stub the calls to Wings so that we don't actually hit those API endpoints.
     */
    public function setUp(): void
    {
        parent::setUp();

        /* @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->bungeecord = Egg::query()
            ->where('author', 'support@pterodactyl.io')
            ->where('name', 'Bungeecord')
            ->firstOrFail();

        $this->daemonServerRepository = Mockery::mock(DaemonServerRepository::class);
        $this->swap(DaemonServerRepository::class, $this->daemonServerRepository);
    }

    /**
     * Test that a server is deleted from the Panel if Wings returns an error during the creation
     * process.
     */
    public function testErrorEncounteredByWingsCausesServerToBeDeleted()
    {
        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()->create();

        /** @var \Pterodactyl\Models\Location $location */
        $location = Location::factory()->create();

        /** @var \Pterodactyl\Models\Cluster $node */
        $node = Node::factory()->create([
            'location_id' => $location->id,
        ]);

        /** @var \Pterodactyl\Models\Allocation $allocation */
        // $allocation = Allocation::factory()->create([
        //     'node_id' => $node->id,
        // ]);

        $data = [
            'name' => $this->faker->name,
            'description' => $this->faker->sentence,
            'owner_id' => $user->id,
            'default_port' => 65535,
            'node_id' => $node->id,
            'memory' => 256,
            'swap' => 128,
            'disk' => 100,
            'io' => 500,
            'cpu' => 0,
            'startup' => 'java server2.jar',
            'image' => 'java:8',
            'egg_id' => $this->bungeecord->id,
            'environment' => [
                'BUNGEE_VERSION' => '123',
                'SERVER_JARFILE' => 'server2.jar',
            ],
        ];

        $this->daemonServerRepository->expects('setServer->create')->andThrows(
            new DaemonConnectionException(
                new BadResponseException('Bad request', new Request('POST', '/create'), new Response(500))
            )
        );

        $this->daemonServerRepository->expects('setServer->delete')->andReturnUndefined();

        $this->expectException(DaemonConnectionException::class);

        $this->getService()->handle($data);

        $this->assertDatabaseMissing('servers', ['owner_id' => $user->id]);
    }

    private function getService(): ServerCreationService
    {
        return $this->app->make(ServerCreationService::class);
    }
}
