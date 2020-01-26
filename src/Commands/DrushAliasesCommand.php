<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Endpoints\Account;

/**
 * Class DrushAliasesCommand
 * @package AcquiaCli\Commands
 */
class DrushAliasesCommand extends AcquiaCommand
{

    /**
     * Downloads a compressed archive of Drush aliases and prompts the user to unpack it in the right place.
     * @option install Install Drush aliases directly without confirmation.
     *
     * @command drush:aliases
     */
    public function downloadDrushAliases($options = ['install' => false])
    {
        $account = new Account($this->cloudapi);
        $aliases = $account->getDrushAliases();
        $drushArchive = tempnam(sys_get_temp_dir(), 'AcquiaDrushAliases') . '.tar.gz';
        $this->say(sprintf('Acquia Cloud Drush Aliases archive downloaded to %s', $drushArchive));
        if (file_put_contents($drushArchive, $aliases, LOCK_EX)) {
            if ($options['install'] || $this->confirm(
                sprintf(
                    'Do you want to automatically unpack Acquia Cloud Drush aliases to %s',
                    getenv('HOME')
                )
            )) {
                if (!$home = getenv('HOME')) {
                    throw new \Exception('Home directory not found.');
                }
                $drushDirectory = $home . '/.drush';
                if (!is_dir($drushDirectory)) {
                    mkdir($drushDirectory, 0700);
                }
                if (!is_writeable($drushDirectory)) {
                    chmod($drushDirectory, 0700);
                }
                $archive = new \PharData($drushArchive . '/.drush');
                $drushFiles = [];
                foreach (new \RecursiveIteratorIterator($archive, \RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
                    $drushFiles[] = '.drush/' . $file->getFileName();
                }

                $archive->extractTo($home, $drushFiles, true);
                $this->say(sprintf('Acquia Cloud Drush aliases installed into %s', $drushDirectory));
                unlink($drushArchive);
            } else {
                $this->say('Run the following command to install Drush aliases:');
                $this->writeln(sprintf('$ tar -C $HOME -xf %s', $drushArchive));
                $this->yell(
                    'This command will unpack into ~/.acquia and ~/.drush potentially overwriting existing files!',
                    40,
                    'yellow'
                );
            }
        } else {
            $this->say('Unable to download Drush Aliases');
        }
    }
}
