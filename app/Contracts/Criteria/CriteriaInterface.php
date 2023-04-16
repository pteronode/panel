<?php

namespace Kubectyl\Contracts\Criteria;

use Illuminate\Database\Eloquent\Model;
use Kubectyl\Repositories\Repository;

interface CriteriaInterface
{
    /**
     * Apply selected criteria to a repository call.
     */
    public function apply(Model $model, Repository $repository): mixed;
}
