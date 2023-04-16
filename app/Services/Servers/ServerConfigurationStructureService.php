<?php

namespace Kubectyl\Services\Servers;

use Kubectyl\Models\Mount;
use Kubectyl\Models\Server;

class ServerConfigurationStructureService
{
    /**
     * ServerConfigurationStructureService constructor.
     */
    public function __construct(private EnvironmentService $environment)
    {
    }

    /**
     * Return a configuration array for a specific server when passed a server model.
     *
     * DO NOT MODIFY THIS FUNCTION. This powers legacy code handling for the new Wings
     * daemon, if you modify the structure rockets will break unexpectedly.
     */
    public function handle(Server $server, array $override = [], bool $legacy = false): array
    {
        $clone = $server;
        // If any overrides have been set on this call make sure to update them on the
        // cloned instance so that the configuration generated uses them.
        if (!empty($override)) {
            $clone = $server->fresh();
            foreach ($override as $key => $value) {
                $clone->setAttribute($key, $value);
            }
        }

        return $legacy
            ? $this->returnLegacyFormat($clone)
            : $this->returnCurrentFormat($clone);
    }

    /**
     * Returns the new data format used for the Wings daemon.
     */
    protected function returnCurrentFormat(Server $server): array
    {
        $array = [
            'uuid' => $server->uuid,
            'meta' => [
                'name' => $server->name,
                'description' => $server->description,
            ],
            'suspended' => $server->isSuspended(),
            'environment' => $this->environment->handle($server),
            'invocation' => $server->startup,
            'skip_rocket_scripts' => $server->skip_scripts,
            'build' => [
                'memory_limit' => $server->memory,
                'cpu_limit' => $server->cpu,
                'disk_space' => $server->disk,
            ],
            'container' => [
                'image' => $server->image,
                'requires_rebuild' => false,
            ],
            'mounts' => $server->mounts->map(function (Mount $mount) {
                return [
                    'source' => $mount->source,
                    'target' => $mount->target,
                    'read_only' => $mount->read_only,
                ];
            }),
            'rocket' => [
                'id' => $server->rocket->uuid,
                'file_denylist' => $server->rocket->inherit_file_denylist,
            ],
            'node_selectors' => $server->node_selectors,
        ];

        if (!empty($server->default_port)) {
            $array['ports'] = [
                'default' => [
                    'port' => $server->default_port,
                ],
                'mappings' => $server->additional_ports ? $server->additional_ports : [],
            ];
        } else {
            $array['allocations'] = [
                'default' => [
                    'ip' => $ip = $server->allocation ? $server->allocation->ip : null,
                    'port' => $server->allocation ? $server->allocation->port : null,
                ],
                'mappings' => $server->getAllocationMappings(),
            ];
        }

        return $array;
    }

    /**
     * Returns the legacy server data format to continue support for old rocket configurations
     * that have not yet been updated.
     *
     * @deprecated
     */
    protected function returnLegacyFormat(Server $server): array
    {
        return [
            'uuid' => $server->uuid,
            'build' => [
                // 'default' => [
                //     'ip' => $server->allocation->ip,
                //     'port' => $server->allocation->port,
                // ],
                'default' => [
                    'port' => $server->default_port,
                ],
                // 'ports' => $server->allocations->groupBy('ip')->map(function ($item) {
                //     return $item->pluck('port');
                // })->toArray(),
                'env' => $this->environment->handle($server),
                // 'oom_disabled' => $server->oom_disabled,
                'memory' => (int) $server->memory,
                // 'swap' => (int) $server->swap,
                // 'io' => (int) $server->io,
                'cpu' => (int) $server->cpu,
                // 'threads' => $server->threads,
                'disk' => (int) $server->disk,
                'image' => $server->image,
            ],
            'service' => [
                'rocket' => $server->rocket->uuid,
                'skip_scripts' => $server->skip_scripts,
            ],
            'rebuild' => false,
            'suspended' => $server->isSuspended() ? 1 : 0,
        ];
    }
}
