<?php

namespace Pterodactyl\Services\Clusters;

use Illuminate\Support\Str;
use Pterodactyl\Models\Cluster;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Repositories\Eloquent\ClusterRepository;
use Pterodactyl\Repositories\Wings\DaemonConfigurationRepository;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Exceptions\Service\Cluster\ConfigurationNotPersistedException;

class ClusterUpdateService
{
    /**
     * ClusterUpdateService constructor.
     */
    public function __construct(
        private ConnectionInterface $connection,
        private DaemonConfigurationRepository $configurationRepository,
        private Encrypter $encrypter,
        private ClusterRepository $repository
    ) {
    }

    /**
     * Update the configuration values for a given node on the machine.
     *
     * @throws \Throwable
     */
    public function handle(Cluster $cluster, array $data, bool $resetToken = false): Cluster
    {
        if ($resetToken) {
            $data['daemon_token'] = $this->encrypter->encrypt(Str::random(Cluster::DAEMON_TOKEN_LENGTH));
            $data['daemon_token_id'] = Str::random(Cluster::DAEMON_TOKEN_ID_LENGTH);
        }

        $data['bearer_token'] = $this->encrypter->encrypt($data['bearer_token']);

        [$updated, $exception] = $this->connection->transaction(function () use ($data, $cluster) {
            /** @var \Pterodactyl\Models\Cluster $updated */
            $updated = $this->repository->withFreshModel()->update($cluster->id, $data, true, true);

            try {
                // If we're changing the FQDN for the node, use the newly provided FQDN for the connection
                // address. This should alleviate issues where the node gets pointed to a "valid" FQDN that
                // isn't actually running the daemon software, and therefore you can't actually change it
                // back.
                //
                // This makes more sense anyways, because only the Panel uses the FQDN for connecting, the
                // node doesn't actually care about this.
                //
                // @see https://github.com/pterodactyl/panel/issues/1931
                $cluster->fqdn = $updated->fqdn;

                $this->configurationRepository->setNode($cluster)->update($updated);
            } catch (DaemonConnectionException $exception) {
                Log::warning($exception, ['cluster_id' => $cluster->id]);

                // Never actually throw these exceptions up the stack. If we were able to change the settings
                // but something went wrong with Wings we just want to store the update and let the user manually
                // make changes as needed.
                //
                // This avoids issues with proxies such as Cloudflare which will see Wings as offline and then
                // inject their own response pages, causing this logic to get fucked up.
                //
                // @see https://github.com/pterodactyl/panel/issues/2712
                return [$updated, true];
            }

            return [$updated, false];
        });

        if ($exception) {
            throw new ConfigurationNotPersistedException(trans('exceptions.node.daemon_off_config_updated'));
        }

        return $updated;
    }
}
