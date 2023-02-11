<?php

namespace Pterodactyl\Http\Requests\Admin\Cluster;

use Pterodactyl\Rules\Fqdn;
use Pterodactyl\Models\Cluster;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class ClusterFormRequest extends AdminFormRequest
{
    /**
     * Get rules to apply to data in this request.
     */
    public function rules(): array
    {
        if ($this->method() === 'PATCH') {
            return Cluster::getRulesForUpdate($this->route()->parameter('cluster'));
        }

        $data = Cluster::getRules();
        $data['fqdn'][] = Fqdn::make('scheme');

        return $data;
    }
}
