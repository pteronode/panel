<?php

namespace Pterodactyl\Models;

use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Container\Container;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property string $uuid
 * @property bool $public
 * @property string $name
 * @property string|null $description
 * @property int $location_id
 * @property string $fqdn
 * @property string $scheme
 * @property bool $behind_proxy
 * @property bool $maintenance_mode
 * @property int $upload_size
 * @property string $daemon_token_id
 * @property string $daemon_token
 * @property int $daemonListen
 * @property string $daemonBase
 * @property string $sftp_image
 * @property int $sftp_port
 * @property string $host
 * @property string $bearer_token
 * @property bool $insecure
 * @property string $service_type
 * @property string $storage_class
 * @property string $ns
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Pterodactyl\Models\Location $location
 * @property \Pterodactyl\Models\Mount[]|\Illuminate\Database\Eloquent\Collection $mounts
 * @property \Pterodactyl\Models\Server[]|\Illuminate\Database\Eloquent\Collection $servers
 * @property \Pterodactyl\Models\Allocation[]|\Illuminate\Database\Eloquent\Collection $allocations
 */
class Cluster extends Model
{
    use Notifiable;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'cluster';

    public const DAEMON_TOKEN_ID_LENGTH = 16;
    public const DAEMON_TOKEN_LENGTH = 64;

    /**
     * The table associated with the model.
     */
    protected $table = 'clusters';

    /**
     * The attributes excluded from the model's JSON form.
     */
    protected $hidden = ['daemon_token_id', 'daemon_token', 'bearer_token'];

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'location_id' => 'integer',
        'daemonListen' => 'integer',
        'sftp_port' => 'integer',
        'behind_proxy' => 'boolean',
        'public' => 'boolean',
        'maintenance_mode' => 'boolean',
        'insecure' => 'boolean',
        'metallb_shared_ip' => 'boolean',
    ];

    /**
     * Fields that are mass assignable.
     */
    protected $fillable = [
        'public', 'name', 'location_id',
        'fqdn', 'scheme', 'behind_proxy',
        'upload_size', 'daemonBase', 'sftp_image', 'sftp_port',
        'daemonListen', 'description', 'maintenance_mode', 'insecure',
        'service_type', 'storage_class', 'ns', 'dns_policy', 'image_pull_policy',
        'metallb_shared_ip'
    ];

    public static array $validationRules = [
        'name' => 'required|regex:/^([\w .-]{1,100})$/',
        'description' => 'string|nullable',
        'location_id' => 'required|exists:locations,id',
        'public' => 'boolean',
        'fqdn' => 'required|string',
        'scheme' => 'required',
        'behind_proxy' => 'boolean',
        'daemonBase' => 'sometimes|required|regex:/^([\/][\d\w.\-\/]+)$/',
        'sftp_image' => 'required|string',
        'sftp_port' => 'required|numeric|between:1,65535',
        'daemonListen' => 'required|numeric|between:1,65535',
        'maintenance_mode' => 'boolean',
        'upload_size' => 'int|between:1,1024',
        'host' => 'required|string',
        'bearer_token' => 'required_if:insecure,1,true',
        'insecure' => 'boolean',
        'cert_file' => 'required_if:insecure,0,false',
        'key_file' => 'required_if:insecure,0,false',
        'ca_file' => 'required_if:insecure,0,false',
        'dns_policy' => 'required|string',
        'image_pull_policy' => 'required|string',
        'storage_class' => 'required|string',
        'ns' => 'required|string',
        'service_type' => 'required|string',
        'metallb_address_pool' => 'nullable|string',
        'metallb_shared_ip' => 'boolean',
    ];

    /**
     * Default values for specific columns that are generally not changed on base installs.
     */
    protected $attributes = [
        'public' => true,
        'behind_proxy' => false,
        'daemonBase' => '/var/lib/kubectyl/volumes',
        'sftp_image' => 'ghcr.io/kubectyl/sftp-server:latest',
        'sftp_port' => 2022,
        'daemonListen' => 8080,
        'maintenance_mode' => false,
        'insecure' => false,
        'dns_policy' => "clusterfirst",
        'image_pull_policy' => "ifnotpresent",
        'metallb_shared_ip' => true,
    ];

    /**
     * Get the connection address to use when making calls to this cluster.
     */
    public function getConnectionAddress(): string
    {
        return sprintf('%s://%s:%s', $this->scheme, $this->fqdn, $this->daemonListen);
    }

    /**
     * Returns the configuration as an array.
     */
    public function getConfiguration(): array
    {
        return [
            'debug' => false,
            'uuid' => $this->uuid,
            'token_id' => $this->daemon_token_id,
            'token' => Container::getInstance()->make(Encrypter::class)->decrypt($this->daemon_token),
            'api' => [
                'host' => '0.0.0.0',
                'port' => $this->daemonListen,
                'ssl' => [
                    'enabled' => (!$this->behind_proxy && $this->scheme === 'https'),
                    'cert' => '/etc/letsencrypt/live/' . Str::lower($this->fqdn) . '/fullchain.pem',
                    'key' => '/etc/letsencrypt/live/' . Str::lower($this->fqdn) . '/privkey.pem',
                ],
                'upload_limit' => $this->upload_size,
            ],
            'system' => [
                'data' => $this->daemonBase,
                'sftp' => [
                    'bind_port' => $this->sftp_port,
                    'sftp_image' => $this->sftp_image,
                ],
            ],
            'cluster' => [
                'host' => $this->host,
                'bearer_token' => Container::getInstance()->make(Encrypter::class)->decrypt($this->bearer_token),
                'insecure' => $this->insecure,
                'cert_file' => $this->cert_file,
                'key_file' => $this->key_file,
                'ca_file' => $this->ca_file,
                'dns_policy' => $this->dns_policy,
                'image_pull_policy' => $this->image_pull_policy,
                'storage_class' => $this->storage_class,
                'namespace' => $this->ns,
                'service_type' => $this->service_type,
                'metallb_address_pool' => $this->metallb_address_pool,
                'metallb_shared_ip' => $this->metallb_shared_ip,
            ],
            'allowed_mounts' => $this->mounts->pluck('source')->toArray(),
            'remote' => route('index'),
        ];
    }

    /**
     * Returns the configuration in Yaml format.
     */
    public function getYamlConfiguration(): string
    {
        return Yaml::dump($this->getConfiguration(), 4, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
    }

    /**
     * Returns the configuration in JSON format.
     */
    public function getJsonConfiguration(bool $pretty = false): string
    {
        return json_encode($this->getConfiguration(), $pretty ? JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT : JSON_UNESCAPED_SLASHES);
    }

    /**
     * Helper function to return the decrypted key for a cluster.
     */
    public function getDecryptedKey(): string
    {
        return (string) Container::getInstance()->make(Encrypter::class)->decrypt(
            $this->daemon_token
        );
    }

    public function isUnderMaintenance(): bool
    {
        return $this->maintenance_mode;
    }

    public function mounts(): HasManyThrough
    {
        return $this->hasManyThrough(Mount::class, MountCluster::class, 'cluster_id', 'id', 'id', 'mount_id');
    }

    /**
     * Gets the location associated with a cluster.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Gets the servers associated with a cluster.
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    /**
     * Gets the allocations associated with a cluster.
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }
}
