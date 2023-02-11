<?php

namespace Pterodactyl\Services\Clusters;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Pterodactyl\Models\Cluster;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Contracts\Repository\ClusterRepositoryInterface;

class ClusterCreationService
{
    /**
     * ClusterCreationService constructor.
     */
    public function __construct(protected ClusterRepositoryInterface $repository)
    {
    }

    /**
     * Create a new node on the panel.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle(array $data): Cluster
    {
        $data['uuid'] = Uuid::uuid4()->toString();
        $data['daemon_token'] = app(Encrypter::class)->encrypt(Str::random(Cluster::DAEMON_TOKEN_LENGTH));
        $data['bearer_token'] = app(Encrypter::class)->encrypt($data['bearer_token']);
        $data['daemon_token_id'] = Str::random(Cluster::DAEMON_TOKEN_ID_LENGTH);

        return $this->repository->create($data, true, true);
    }
}
