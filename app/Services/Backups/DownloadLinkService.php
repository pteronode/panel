<?php

namespace Kubectyl\Services\Backups;

use Carbon\CarbonImmutable;
use Kubectyl\Models\User;
use Kubectyl\Models\Snapshot;
use Kubectyl\Services\Clusters\ClusterJWTService;
use Kubectyl\Extensions\Backups\BackupManager;

class DownloadLinkService
{
    /**
     * DownloadLinkService constructor.
     */
    public function __construct(private BackupManager $backupManager, private ClusterJWTService $jwtService)
    {
    }

    /**
     * Returns the URL that allows for a backup to be downloaded by an individual
     * user, or by the Wings control software.
     */
    public function handle(Snapshot $backup, User $user): string
    {
        if ($backup->disk === Snapshot::ADAPTER_AWS_S3) {
            return $this->getS3BackupUrl($backup);
        }

        $token = $this->jwtService
            ->setExpiresAt(CarbonImmutable::now()->addMinutes(15))
            ->setUser($user)
            ->setClaims([
                'backup_uuid' => $backup->uuid,
                'server_uuid' => $backup->server->uuid,
            ])
            ->handle($backup->server->cluster, $user->id . $backup->server->uuid);

        return sprintf('%s/download/backup?token=%s', $backup->server->cluster->getConnectionAddress(), $token->toString());
    }

    /**
     * Returns a signed URL that allows us to download a file directly out of a non-public
     * S3 bucket by using a signed URL.
     */
    protected function getS3BackupUrl(Snapshot $backup): string
    {
        /** @var \Kubectyl\Extensions\Filesystem\S3Filesystem $adapter */
        $adapter = $this->backupManager->adapter(Snapshot::ADAPTER_AWS_S3);

        $request = $adapter->getClient()->createPresignedRequest(
            $adapter->getClient()->getCommand('GetObject', [
                'Bucket' => $adapter->getBucket(),
                'Key' => sprintf('%s/%s.tar.gz', $backup->server->uuid, $backup->uuid),
                'ContentType' => 'application/x-gzip',
            ]),
            CarbonImmutable::now()->addMinutes(5)
        );

        return $request->getUri()->__toString();
    }
}
