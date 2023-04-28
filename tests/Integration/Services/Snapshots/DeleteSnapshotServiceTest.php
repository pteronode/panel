<?php

namespace Kubectyl\Tests\Integration\Services\Snapshots;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Kubectyl\Models\Snapshot;
use GuzzleHttp\Exception\ClientException;
use Kubectyl\Extensions\Backups\BackupManager;
use Kubectyl\Extensions\Filesystem\S3Filesystem;
use Kubectyl\Services\Backups\DeleteBackupService;
use Kubectyl\Tests\Integration\IntegrationTestCase;
use Kubectyl\Repositories\Kuber\DaemonBackupRepository;
use Kubectyl\Exceptions\Service\Backup\BackupLockedException;
use Kubectyl\Exceptions\Http\Connection\DaemonConnectionException;

class DeleteSnapshotServiceTest extends IntegrationTestCase
{
    public function testLockedSnapshotCannotBeDeleted()
    {
        $server = $this->createServerModel();
        $snapshot = Snapshot::factory()->create([
            'server_id' => $server->id,
            'is_locked' => true,
        ]);

        $this->expectException(BackupLockedException::class);

        $this->app->make(DeleteBackupService::class)->handle($snapshot);
    }

    public function testFailedSnapshotThatIsLockedCanBeDeleted()
    {
        $server = $this->createServerModel();
        $snapshot = Snapshot::factory()->create([
            'server_id' => $server->id,
            'is_locked' => true,
            'is_successful' => false,
        ]);

        $mock = $this->mock(DaemonBackupRepository::class);
        $mock->expects('setServer->delete')->with($snapshot)->andReturn(new Response());

        $this->app->make(DeleteBackupService::class)->handle($snapshot);

        $snapshot->refresh();

        $this->assertNotNull($snapshot->deleted_at);
    }

    public function testExceptionThrownDueToMissingSnapshotIsIgnored()
    {
        $server = $this->createServerModel();
        $snapshot = Snapshot::factory()->create(['server_id' => $server->id]);

        $mock = $this->mock(DaemonBackupRepository::class);
        $mock->expects('setServer->delete')->with($snapshot)->andThrow(
            new DaemonConnectionException(
                new ClientException('', new Request('DELETE', '/'), new Response(404))
            )
        );

        $this->app->make(DeleteBackupService::class)->handle($snapshot);

        $snapshot->refresh();

        $this->assertNotNull($snapshot->deleted_at);
    }

    public function testExceptionIsThrownIfNot404()
    {
        $server = $this->createServerModel();
        $snapshot = Snapshot::factory()->create(['server_id' => $server->id]);

        $mock = $this->mock(DaemonBackupRepository::class);
        $mock->expects('setServer->delete')->with($snapshot)->andThrow(
            new DaemonConnectionException(
                new ClientException('', new Request('DELETE', '/'), new Response(500))
            )
        );

        $this->expectException(DaemonConnectionException::class);

        $this->app->make(DeleteBackupService::class)->handle($snapshot);

        $snapshot->refresh();

        $this->assertNull($snapshot->deleted_at);
    }

    public function testS3ObjectCanBeDeleted()
    {
        $server = $this->createServerModel();
        $snapshot = Snapshot::factory()->create([
            'disk' => Snapshot::ADAPTER_AWS_S3,
            'server_id' => $server->id,
        ]);

        $manager = $this->mock(BackupManager::class);
        $adapter = $this->mock(S3Filesystem::class);

        $manager->expects('adapter')->with(Snapshot::ADAPTER_AWS_S3)->andReturn($adapter);

        $adapter->expects('getBucket')->andReturn('foobar');
        $adapter->expects('getClient->deleteObject')->with([
            'Bucket' => 'foobar',
            'Key' => sprintf('%s/%s.tar.gz', $server->uuid, $snapshot->uuid),
        ]);

        $this->app->make(DeleteBackupService::class)->handle($snapshot);

        $this->assertSoftDeleted($snapshot);
    }
}
