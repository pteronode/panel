<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use League\Fractal\Resource\Item;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Permission;
use Illuminate\Container\Container;
use Pterodactyl\Models\EggVariable;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Services\Servers\StartupCommandService;
use Pterodactyl\Services\Allocations\AssignmentService;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;

class ServerTransformer extends BaseClientTransformer
{
    /**
     * ServerController constructor.
     */
    public function __construct(
        private DaemonServerRepository $daemonRepository
    ) {
        parent::__construct();
    }

    protected array $defaultIncludes = ['allocations', 'variables'];

    protected array $availableIncludes = ['egg', 'subusers'];

    public function getResourceName(): string
    {
        return Server::RESOURCE_NAME;
    }

    /**
     * An array of individual ports or port ranges to use when selecting an allocation. If
     * empty, all ports will be considered when finding an allocation. If set, only ports appearing
     * in the array or range will be used.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function setPorts(array $ports): array
    {
        $stored = [];
        if (!is_null($ports)) {
            foreach ($ports as $port) {
                if (is_digit($port)) {
                    $stored[] = $port;
                }

                // Ranges are stored in the ports array as an array which can be
                // better processed in the repository.
                if (preg_match(AssignmentService::PORT_RANGE_REGEX, $port, $matches)) {
                    if (abs($matches[2] - $matches[1]) > AssignmentService::PORT_RANGE_LIMIT) {
                        throw new DisplayException(trans('exceptions.allocations.too_many_ports'));
                    }

                    foreach (range($matches[1], $matches[2]) as $n) {
                        $stored[] = $n;
                    }
                }
            }
        }

        return $stored;
    }

    /**
     * Implementation that works on multidimensional arrays
     * 
     * Taken from https://github.com/NinoSkopac/array_column_recursive
     */
    function array_column_recursive(array $haystack, $needle) {
        $found = [];
        array_walk_recursive($haystack, function($value, $key) use (&$found, $needle) {
            if ($key == $needle)
                $found[] = $value;
        });
        return $found;
    }

    /**
     * Transform a server model into a representation that can be returned
     * to a client.
     */
    public function transform(Server $server): array
    {
        /** @var \Pterodactyl\Services\Servers\StartupCommandService $service */
        $service = Container::getInstance()->make(StartupCommandService::class);

        $user = $this->request->user();

        $pod = [];
        // Don't return any error because the servers will disappear from the list.
        try {
            $pod = $this->daemonRepository->setServer($server)->getDetails();
        } catch(\Exception $error) {
            // do nothing
        }

        return [
            'server_owner' => $user->id === $server->owner_id,
            'identifier' => $server->uuidShort,
            'internal_id' => $server->id,
            'uuid' => $server->uuid,
            'name' => $server->name,
            'cluster' => $server->cluster->name,
            'is_cluster_under_maintenance' => $server->cluster->isUnderMaintenance(),
            'sftp_details' => [
                'ip' => $ip = $this->array_column_recursive($pod, 'ip') ? current($this->array_column_recursive($pod, 'ip')) : current($this->array_column_recursive($pod, 'clusterIP')),
                'port' => 2022,
            ],
            'service' => [
                'ip' => $ip = $this->array_column_recursive($pod, 'ip') ? current($this->array_column_recursive($pod, 'ip')) : current($this->array_column_recursive($pod, 'clusterIP')),
                'port' => $server->default_port,
                'additional_ports' => $server->additional_ports ? $this->setPorts($server->additional_ports) : [],
            ],
            'default_port' => $server->default_port,
            'additional_ports' => $server->additional_ports ? $this->setPorts($server->additional_ports) : [],
            'description' => $server->description,
            'limits' => [
                'memory' => $server->memory,
                // 'swap' => $server->swap,
                'disk' => $server->disk,
                // 'io' => $server->io,
                'cpu' => $server->cpu,
                // 'threads' => $server->threads,
                'oom_disabled' => $server->oom_disabled,
            ],
            'invocation' => $service->handle($server, !$user->can(Permission::ACTION_STARTUP_READ, $server)),
            'docker_image' => $server->image,
            'egg_features' => $server->egg->inherit_features,
            'feature_limits' => [
                'databases' => $server->database_limit,
                'allocations' => $server->allocation_limit,
                'backups' => $server->backup_limit,
            ],
            'status' => $server->status,
            // This field is deprecated, please use "status".
            'is_suspended' => $server->isSuspended(),
            // This field is deprecated, please use "status".
            'is_installing' => !$server->isInstalled(),
            'is_transferring' => !is_null($server->transfer),
        ];
    }

    /**
     * Returns the allocations associated with this server.
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeAllocations(Server $server): Collection
    {
        $transformer = $this->makeTransformer(AllocationTransformer::class);

        $user = $this->request->user();
        // While we include this permission, we do need to actually handle it slightly different here
        // for the purpose of keeping things functionally working. If the user doesn't have read permissions
        // for the allocations we'll only return the primary server allocation, and any notes associated
        // with it will be hidden.
        //
        // This allows us to avoid too much permission regression, without also hiding information that
        // is generally needed for the frontend to make sense when browsing or searching results.
        if (!$user->can(Permission::ACTION_ALLOCATION_READ, $server)) {
            $primary = clone $server->allocation;
            $primary->notes = null;

            return $this->collection([$primary], $transformer, Allocation::RESOURCE_NAME);
        }

        return $this->collection($server->allocations, $transformer, Allocation::RESOURCE_NAME);
    }

    /**
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeVariables(Server $server): Collection|NullResource
    {
        if (!$this->request->user()->can(Permission::ACTION_STARTUP_READ, $server)) {
            return $this->null();
        }

        return $this->collection(
            $server->variables->where('user_viewable', true),
            $this->makeTransformer(EggVariableTransformer::class),
            EggVariable::RESOURCE_NAME
        );
    }

    /**
     * Returns the egg associated with this server.
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeEgg(Server $server): Item
    {
        return $this->item($server->egg, $this->makeTransformer(EggTransformer::class), Egg::RESOURCE_NAME);
    }

    /**
     * Returns the subusers associated with this server.
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeSubusers(Server $server): Collection|NullResource
    {
        if (!$this->request->user()->can(Permission::ACTION_USER_READ, $server)) {
            return $this->null();
        }

        return $this->collection($server->subusers, $this->makeTransformer(SubuserTransformer::class), Subuser::RESOURCE_NAME);
    }
}
