<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;

/**
 * Class DrushAliasesCommand
 * @package AcquiaCli\Commands
 */
class DrushAliasesCommand extends AcquiaCommand
{

    /**
     * @command drush:aliases
     */
    public function downloadDrushAliases()
    {
        $aliases = $this->cloudapi->drushAliases();
        $tmpFile = tempnam(sys_get_temp_dir(), 'AcquiaDrushAliases') . '.tar.gz';
        if (file_put_contents($tmpFile, $aliases, LOCK_EX)) {
            $this->say("Acquia Cloud Drush Aliases downloaded to ${tmpFile}");
            $this->say('Run the following command to install them.');
            $this->say("tar -C \$HOME -xf ${tmpFile}");
        } else {
            $this->say('Unable to download Drush Aliases');
        }
    }
}
