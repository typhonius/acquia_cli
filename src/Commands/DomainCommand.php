<?php

namespace AcquiaCli\Commands;

/**
 * Class DomainCommand
 * @package AcquiaCli\Commands
 */
class DomainCommand extends AcquiaCommand
{

    /**
     * Add a domain to an environment.
     *
     * @param string $uuid
     * @param string $environment
     * @param string $domain
     *
     * @command domain:add
     */
    public function acquiaAddDomain($uuid, $environment, $domain)
    {

        if (!preg_match(self::UUIDv4, $uuid)) {
            $uuid = $this->getUuidFromHostingName($uuid);
        }
        $id = $this->getIdFromEnvironmentName($uuid, $environment);


        $this->say("Adding ${domain} to ${environment} environment");
        $task = $this->cloudapi->addDomain($id, $domain);
        $this->waitForTask($uuid, 'DomainAdded');
    }

    /**
     * Remove a domain to an environment.
     *
     * @param string $uuid
     * @param string $environment
     * @param string $domain
     *
     * @command domain:remove
     */
    public function acquiaRemoveDomain($uuid, $environment, $domain)
    {
        if (!preg_match(self::UUIDv4, $uuid)) {
            $uuid = $this->getUuidFromHostingName($uuid);
        }
        $id = $this->getIdFromEnvironmentName($uuid, $environment);

        if ($this->confirm('Are you sure you want to remove this domain?')) {
            $this->say("Removing ${domain} from ${environment} environment");
            $task = $this->cloudapi->deleteDomain($id, $domain);
            $this->waitForTask($uuid, 'DomainRemoved');
        }
    }

    /**
     * List all domains.
     *
     *
     * @param string $site
     * @param string $domain
     * @param string $environmentFrom
     * @param string $environmentTo
     *
     * @command domain:move
     */
    public function acquiaMoveDomain($site, $domain, $environmentFrom, $environmentTo)
    {
        if ($this->confirm("Are you sure you want to move ${domain} from ${environmentFrom} to ${environmentTo}?")) {
            $this->say("Moving ${domain} from ${environmentFrom} to ${environmentTo}");
            $task = $this->cloudapi->moveDomain($site, $domain, $environmentFrom, $environmentTo);
            $this->waitForTask($site, $task);
        }
    }
}
