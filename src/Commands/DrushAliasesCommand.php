<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Endpoints\Account;

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
            $this->say(sprintf('Acquia Cloud Drush Aliases downloaded to %s', $tmpFile));
            $this->say('Run the following command to install them:');
            $this->writeln(sprintf('$ tar -C $HOME -xf %s', $tmpFile));
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
