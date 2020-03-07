<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Endpoints\Account;

/**
 * Class AccountCommand
 * @package AcquiaCli\Commands
 */
class AccountCommand extends AcquiaCommand
{

    protected $accountAdapter;

    public function __construct()
    {
        parent::__construct();

        $this->accountAdapter = new Account($this->cloudapi);
    }

    /**
     * Gets information about the user's account.
     *
     * @command account
     */
    public function account()
    {
        $extraConfig = $this->cloudapiService->getExtraConfig();
        $tz = $extraConfig['timezone'];
        $format = $extraConfig['format'];
        $timezone = new \DateTimeZone($tz);

        $account = $this->accountAdapter->get();

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
