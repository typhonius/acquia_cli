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
     * Downloads a compressed archive of Drush aliases and prompts the user to unpack it in the right place.
     *
     * @command drush:aliases
     */
    public function downloadDrushAliases()
    {
        $account = new Account($this->cloudapi);
        $aliases = $account->getDrushAliases();
        $tmpFile = tempnam(sys_get_temp_dir(), 'AcquiaDrushAliases') . '.tar.gz';
        if (file_put_contents($tmpFile, $aliases, LOCK_EX)) {
            $this->say("Acquia Cloud Drush Aliases downloaded to ${tmpFile}");
            $this->say('Run the following command to install them:');
            $this->writeln("$ tar -C \$HOME -xf ${tmpFile}");
            $this->yell(
                'This command will unpack into ~/.acquia and ~/.drush potentially overwriting existing files!',
                40,
                'yellow'
            );
        } else {
            $this->say('Unable to download Drush Aliases');
        }
    }
}
