<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Endpoints\Account;
use PharData;

/**
 * Class DrushAliasesCommand
 * @package AcquiaCli\Commands
 */
class DrushAliasesCommand extends AcquiaCommand
{

    /**
     * Downloads a compressed archive of Drush aliases and prompts the user to unpack it in the right place.
     * @option install Install Drush aliases directly.
     *
     * @command drush:aliases
     */
    public function downloadDrushAliases($options = ['install' => false])
    {
        $account = new Account($this->cloudapi);
        $aliases = $account->getDrushAliases();
        $drushArchive = tempnam(sys_get_temp_dir(), 'AcquiaDrushAliases') . '.tar.gz';
        if (file_put_contents($drushArchive, $aliases, LOCK_EX)) {
            if ($options['install'] && $this->confirm(
                sprintf(
                    'Are you sure you want to automatically unpack Acquia Cloud Drush Aliases to %s',
                    getenv('HOME')
                )
            )) {
                $drushDirectory = getenv('HOME') . '/.drush';
                if (!is_dir($drushDirectory)) {
                    mkdir($drushDirectory, 0700);
                }
                $archive = new PharData($drushArchive . '/.drush');
                $drushFiles = [];
                foreach (new \RecursiveIteratorIterator($archive, \RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
                    $drushFiles[] = '.drush/' . $file->getFileName();
                }
                $archive->extractTo(getenv('HOME'), $drushFiles, true);
            } else {
                $this->say(sprintf('Acquia Cloud Drush Aliases downloaded to %s', $drushArchive));
                $this->say('Run the following command to install them:');
                $this->writeln(sprintf('$ tar -C $HOME -xf %s', $drushArchive));
                $this->yell(
                    'This command will unpack into ~/.acquia and ~/.drush potentially overwriting existing files!',
                    40,
                    'yellow'
                );
                $this->say('Use the --install option to install aliases directly.');
            }
        } else {
            $this->say('Unable to download Drush Aliases');
        }
    }
}
