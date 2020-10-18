<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Endpoints\Account;
use AcquiaCli\Cli\Config;

/**
 * Class AccountCommand
 *
 * @package AcquiaCli\Commands
 */
class AccountCommand extends AcquiaCommand
{

    /**
     * Gets information about the user's account.
     *
     * @command account
     */
    public function account(Config $config, Account $accountAdapter)
    {
        $account = $accountAdapter->get();

        if ($this->input()->getOption('format') === 'json') {
            return $this->writeln(json_encode($account));
        }

        $extraConfig = $config->get('extraconfig');
        $tz = $extraConfig['timezone'];
        $format = $extraConfig['format'];
        $timezone = new \DateTimeZone($tz);

        $lastLogin = new \DateTime($account->last_login_at);
        $lastLogin->setTimezone($timezone);
        $createdAt = new \DateTime($account->created_at);
        $createdAt->setTimezone($timezone);

        $this->say(sprintf('Name: %s', $account->name));
        $this->say(sprintf('Last login: %s', $lastLogin->format($format)));
        $this->say(sprintf('Created at: %s', $createdAt->format($format)));
        $this->say(sprintf('Status: %s', $account->flags->active ? '✓' : ' '));
        $this->say(sprintf('TFA: %s', $account->flags->tfa ? '✓' : ' '));
    }
}
