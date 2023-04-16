<?php

namespace Kubectyl\Exceptions\Service\Backup;

use Kubectyl\Exceptions\DisplayException;

class TooManyBackupsException extends DisplayException
{
    /**
     * TooManyBackupsException constructor.
     */
    public function __construct(int $backupLimit)
    {
        parent::__construct(
            sprintf('Cannot create a new snapshot, this server has reached its limit of %d snapshots.', $backupLimit)
        );
    }
}
