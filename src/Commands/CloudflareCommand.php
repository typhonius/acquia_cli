<?php

namespace AcquiaCli\Commands;

use \Cloudflare\API\Auth\APIKey;
use \Cloudflare\API\Adapter\Guzzle;
use Cloudflare\API\Endpoints\DNS;
use Cloudflare\API\Endpoints\EndpointException;
use Cloudflare\API\Endpoints\Zones;
use Robo\Robo;
use Symfony\Component\Console\Helper\Table;

/**
 * Class CloudflareCommand
 * @package AcquiaCli\Commands
 */
class CloudflareCommand extends AcquiaCommand
{

    /** @var DNS $dns */
    protected $dns;

    /** @var Zones $zones */
    protected $zones;

    /** @var array $records */
    protected $records = [];

    /**
     * CloudflareCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $cloudflare = Robo::config()->get('cloudflare');

        $key = new APIKey($cloudflare['mail'], $cloudflare['key']);
        $adapter = new Guzzle($key);
        $this->dns = new DNS($adapter);
        $this->zones = new Zones($adapter);
    }

    /**
     * @param string $zoneId
     * @param string $type
     * @param string $name
     * @param int $page
     */
    private function buildCloudflareDnsRecordList($zoneId, $type = '', $name = '', $page = 1)
    {
        static $records;

        $type = $type == 'all' ? '' : $type;

        $records[$page] = $this->dns->listRecords($zoneId, $type, $name, '', $page, 100);

        if (empty($records[$page]->result)) {
            $this->yell('No records available', 40, 'red');
            exit;
        }

        if ($records[$page]->result_info->page != $records[$page]->result_info->total_pages) {
            $page++;
            $this->buildCloudflareDnsRecordList($zoneId, $type, $name, $page);
        } else {
            foreach ($records as $record) {
                $this->records = array_merge($this->records, $record->result);
            }
        }
    }

    /**
     * Shows a list of all domain records in Cloudflare.
     *
     * @command cf:list
     *
     * @param string $domain
     * @param string $type
     * @param string $name
     */
    public function cloudflareDnsList($domain, $type = '', $name = '')
    {
        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(array('ID', 'Type', 'Name', 'Content', 'TTL', 'Priority', 'Proxiable', 'Proxied'));

        $zoneId = $this->getCloudflareZoneId($domain);
        $this->buildCloudflareDnsRecordList($zoneId, $type, $name, 1);

        if (!empty($this->records)) {
            foreach ($this->records as $record) {
                $priority = $record->priority ?: '';
                $proxiable = $record->proxiable ? '✅' : '❌';
                $proxied = $record->proxied ? '✅' : '❌';
                $table
                    ->addRows(array(
                        array(
                            $record->id,
                            $record->type,
                            $record->name,
                            $record->content,
                            $record->ttl,
                            $priority,
                            $proxiable,
                            $proxied),
                    ));
            }
            $table->render();
            $total = count($this->records);
            $this->yell("Total records: ${total}");
        }
    }

    /**
     * Updates an existing record in Cloudflare.
     *
     * @command cf:update
     *
     * @param string $domain
     * @param string $recordId
     * @param string $type
     * @param string $name
     * @param string $content
     * @param int    $ttl
     */
    public function cloudflareDnsUpdate($domain, $recordId, $type, $name, $content, $ttl = 3600)
    {
        $this->say("${name}.${domain} ${ttl} IN ${type} ${content}");
        if ($this->confirm('Are you sure you want to update this record?')) {
            $zoneId = $this->getCloudflareZoneId($domain);
            $details = [
                'type' => $type,
                'name' => $name,
                'content' => $content,
                'ttl' => $ttl,
                'proxied' => false,
            ];
            $this->dns->updateRecordDetails($zoneId, $recordId, $details);
        }
    }

    /**
     * Creates a new DNS record in Cloudflare.
     *
     * @command cf:add
     *
     * @param string $domain
     * @param string $type
     * @param string $name
     * @param string $content
     * @param int    $ttl
     */
    public function cloudflareDnsAdd($domain, $type, $name, $content, $ttl = 3600)
    {
        $this->say("${name}.${domain} ${ttl} IN ${type} ${content}");
        if ($this->confirm('Are you sure you want to add this record?')) {
            $zoneId = $this->getCloudflareZoneId($domain);
            $this->dns->addRecord($zoneId, $type, $name, $content, $ttl, false);
        }
    }

    /**
     * Gets a zone ID matching a domain stored in Cloudflare.
     *
     * @command cf:zone
     *
     * @param string $domain
     */
    public function cloudflareZoneIdGet($domain)
    {
        $zoneId = $this->getCloudflareZoneId($domain);
        $this->yell("Zone ID for ${domain} is ${zoneId}");
    }

    /**
     * Lists zones stored in Cloudflare.
     *
     * @command cf:zones
     *
     */
    public function cloudflareZonesList()
    {
        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(array('ID', 'Name', 'Status', 'Nameservers', 'Vanity nameservers'));

        $zones = $this->zones->listZones();

        if (sizeof($zones->result) < 1) {
            throw new EndpointException('No available zones.');
        }

        foreach ($zones->result as $zone) {
            // @codingStandardsIgnoreStart Zend.NamingConventions.ValidVariableName.NotCamelCaps
            $ns = !empty($zone->name_servers) ? implode(', ', $zone->name_servers) : '';
            $vanityNs = !empty($zone->vanity_name_servers) ? implode(', ', $zone->vanity_name_servers) : '';
            // @codingStandardsIgnoreEnd Zend.NamingConventions.ValidVariableName.NotCamelCaps
            $table
                ->addRows(array(
                    array(
                        $zone->id,
                        $zone->name,
                        $zone->status,
                        $ns,
                        $vanityNs,
                    ),
                ));
        }

        $table->render();
    }

    /**
     * @param string $domain
     * @return string
     */
    private function getCloudflareZoneId($domain)
    {
        return $this->zones->getZoneID($domain);
    }
}
