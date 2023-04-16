<?php

namespace Kubectyl\Providers;

use Laravel\Sanctum\Sanctum;
use Kubectyl\Models\ApiKey;
use Kubectyl\Models\Server;
use Kubectyl\Policies\ServerPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     */
    protected $policies = [
        Server::class => ServerPolicy::class,
    ];

    public function boot()
    {
        Sanctum::usePersonalAccessTokenModel(ApiKey::class);

        $this->registerPolicies();

        Gate::define('edit-post', function ($user, $post) {
            return $user->can('edit-post', $post);
        });
    }

    public function register()
    {
        Sanctum::ignoreMigrations();
    }
}
