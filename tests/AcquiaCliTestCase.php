<?php

namespace AcquiaCli\Tests;

use AcquiaCloudApi\Connector\Client;
use Symfony\Component\Console\Input\ArgvInput;
use AcquiaCli\Cli\Config;
use Consolidation\Config\Loader\ConfigProcessor;
use Consolidation\Config\Loader\YamlConfigLoader;
use AcquiaCli\Cli\AcquiaCli;
use Symfony\Component\Console\Output\BufferedOutput;
use Consolidation\AnnotatedCommand\CommandData;
use Robo\Robo;
use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use AcquiaCli\Injector\AcquiaCliInjector;
use AcquiaLogstream\LogstreamManager;

/**
 * Class AcquiaCliTestCase
 */
abstract class AcquiaCliTestCase extends TestCase
{

    public $client;
    public $logstream;

    public function setUp()
    {
        $this->client = $this->getMockClient();
        $this->logstream = $this->getMockLogstream();
    }

    protected function getPsr7StreamForFixture($fixture): StreamInterface
    {
        $path = sprintf(
            '%s/vendor/typhonius/acquia-php-sdk-v2/tests/Fixtures/Endpoints/%s',
            dirname(__DIR__),
            $fixture
        );
        $this->assertFileExists($path);
        $stream = Psr7\stream_for(file_get_contents($path));
        $this->assertInstanceOf(Psr7\Stream::class, $stream);

        return $stream;
    }

    /**
     * Returns a PSR7 Response (JSON) for a given fixture.
     *
     * @param  string  $fixture    The fixture to create the response for.
     * @param  integer $statusCode A HTTP Status Code for the response.
     * @return Psr7\Response
     */
    protected function getPsr7JsonResponseForFixture($fixture, $statusCode = 200): Psr7\Response
    {
        $stream = $this->getPsr7StreamForFixture($fixture);
        $this->assertNotNull(json_decode($stream));
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());

        return new Psr7\Response($statusCode, ['Content-Type' => 'application/json'], $stream);
    }

    /**
     * Returns a PSR7 Response (Gzip) for a given fixture.
     *
     * @param  string  $fixture    The fixture to create the response for.
     * @param  integer $statusCode A HTTP Status Code for the response.
     * @return Psr7\Response
     */
    protected function getPsr7GzipResponseForFixture($fixture, $statusCode = 200): Psr7\Response
    {
        $stream = $this->getPsr7StreamForFixture($fixture);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());

        return new Psr7\Response($statusCode, ['Content-Type' => 'application/octet-stream'], $stream);
    }

    /**
     * Mock client class.
     *
     * @return Client
     */
    protected function getMockClient()
    {
        $connector = $this
            ->getMockBuilder('AcquiaCloudApi\Connector\Connector')
            ->disableOriginalConstructor()
            ->setMethods(['sendRequest'])
            ->getMock();

        $connector
            ->expects($this->any())
            ->method('sendRequest')
            ->will($this->returnCallback(array($this, 'sendRequestCallback')));

        return Client::factory($connector);
    }

    /**
     * Mock client class.
     *
     * @return LogstreamManager
     */
    protected function getMockLogstream()
    {
        $logstream = $this
            ->getMockBuilder('AcquiaLogstream\LogstreamManager')
            ->disableOriginalConstructor()
            ->setMethods(['stream'])
            ->getMock();

        $logstream
            ->expects($this->any())
            ->method('stream')
            ->willReturn('');

        return $logstream;
    }

    /**
     * Callback for the mock client.
     */
    public function sendRequestCallback($verb, $path)
    {
        $fixtureMap = self::getFixtureMap();

        if ($fixture = $fixtureMap[$path][$verb]) {
            // Add in overrides for fixtures which should be downloaded
            // rather than responded to e.g. log:download
            if ($fixture === 'Logs/downloadLog.dat'
                || $fixture === 'DatabaseBackups/downloadDatabaseBackup.dat'
                || $fixture === 'Account/getDrushAliases.dat'
            ) {
                return $this->getPsr7GzipResponseForFixture($fixture);
            }
            return $this->getPsr7JsonResponseForFixture($fixture);
        }
    }

    /**
     * Run commands with a mock client.
     *
     * @see bin/acquia-robo.php
     */
    public function execute($command)
    {
        // Create an instance of the application and use some default parameters.
        $root = dirname(dirname(__DIR__));
        $config = new Config($root);
        $loader = new YamlConfigLoader();
        $processor = new ConfigProcessor();
        $processor->extend($loader->load(dirname(__DIR__) . '/default.acquiacli.yml'));
        $config->import($processor->export());

        array_unshift($command, 'acquiacli', '--no-wait', '--yes');
        $input = new ArgvInput($command);
        $output = new BufferedOutput();

        $app = new AcquiaCli($config, $this->client, $input, $output);

        // Override the LogstreamManager with a mock in the container.
        $container = Robo::getContainer();
        $container->add('logstream', $this->logstream);
        $parameterInjection = $container->get('parameterInjection');
        $parameterInjection->register('AcquiaLogstream\LogstreamManager', new AcquiaCliInjector);
        Robo::setContainer($container);

        $app->run($input, $output);

        // Unset the container so we're dealing with a fresh state for each command
        // This mimics the behaviour expected by users interacting with the application.
        Robo::unsetContainer();

        return $output->fetch();
    }

    public static function getFixtureMap()
    {
        return [
            '/account' => [
                'get' => 'Account/getAccount.json'
            ],
            '/account/drush-aliases/download' => [
                'get' => 'Account/getDrushAliases.dat'
            ],
            '/applications' => [
                'get' => 'Applications/getAllApplications.json',
            ],
            '/applications/a47ac10b-58cc-4372-a567-0e02b2c3d470' => [
                'put' => 'Applications/renameApplication.json'
            ],
            '/applications/a47ac10b-58cc-4372-a567-0e02b2c3d470/environments' => [
                'get' => 'Environments/getAllEnvironments.json'
            ],
            '/applications/a47ac10b-58cc-4372-a567-0e02b2c3d470/tags' => [
                'get' => 'Applications/getAllTags.json',
                'post' => 'Applications/createTag.json',
            ],
            '/applications/a47ac10b-58cc-4372-a567-0e02b2c3d470/tags/name' => [
                'delete' => 'Applications/deleteTag.json',
            ],
            '/applications/a47ac10b-58cc-4372-a567-0e02b2c3d470/databases' => [
                'get' => 'Databases/getAllDatabases.json',
                'post' => 'Databases/createDatabases.json',
            ],
            '/applications/a47ac10b-58cc-4372-a567-0e02b2c3d470/databases/dbName/actions/erase' => [
                'post' => 'Databases/truncateDatabases.json',
            ],
            '/applications/a47ac10b-58cc-4372-a567-0e02b2c3d470/databases/dbName' => [
                'delete' => 'Databases/deleteDatabases.json'
            ],
            '/roles/roleUuid' => [
                'put' => 'Roles/updateRole.json',
                'delete' => 'Roles/deleteRole.json'
            ],
            '/applications/a47ac10b-58cc-4372-a567-0e02b2c3d470/notifications' => [
                'get' => 'Notifications/getAllNotifications.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470' => [
                'delete' => 'Environments/deleteCDEnvironment.json'
            ],
            '/environments/32-a47ac10b-58cc-4372-a567-0e02b2c3d470/databases/database1/backups' => [
                'get' => 'DatabaseBackups/getAllDatabaseBackups.json',
                'post' => 'DatabaseBackups/createDatabaseBackup.json'
            ],
            '/environments/32-a47ac10b-58cc-4372-a567-0e02b2c3d470/databases/database2/backups' => [
                'get' => 'DatabaseBackups/getAllDatabaseBackups.json',
                'post' => 'DatabaseBackups/createDatabaseBackup.json'
            ],
            '/environments/15-a47ac10b-58cc-4372-a567-0e02b2c3d470/databases/database1/backups' => [
                'get' => 'DatabaseBackups/getAllDatabaseBackups.json',
                'post' => 'DatabaseBackups/createDatabaseBackup.json'
            ],
            '/environments/15-a47ac10b-58cc-4372-a567-0e02b2c3d470/databases/database2/backups' => [
                'get' => 'DatabaseBackups/getAllDatabaseBackups.json',
                'post' => 'DatabaseBackups/createDatabaseBackup.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/databases/database1/backups' => [
                'get' => 'DatabaseBackups/getAllDatabaseBackups.json',
                'post' => 'DatabaseBackups/createDatabaseBackup.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/databases/database2/backups' => [
                'get' => 'DatabaseBackups/getAllDatabaseBackups.json',
                'post' => 'DatabaseBackups/createDatabaseBackup.json'
            ],
            '/environments/32-a47ac10b-58cc-4372-a567-0e02b2c3d470/databases' => [
                'post' => 'Databases/copyDatabases.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/databases' => [
                'post' => 'Databases/copyDatabases.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/databases/dbName/backups/1234/actions/restore' => [
                'post' => 'DatabaseBackups/restoreDatabaseBackup.json',
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/databases/database2/backups/1/actions/download' => [
                'get' => 'DatabaseBackups/downloadDatabaseBackup.dat'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/databases/database2/backups/1/actions/download' => [
                'get' => 'DatabaseBackups/downloadDatabaseBackup.dat'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/domains' => [
                'post' => 'Domains/createDomain.json',
                'get' => 'Domains/getAllDomains.json'
            ],
            '/environments/32-a47ac10b-58cc-4372-a567-0e02b2c3d470/domains' => [
                'post' => 'Domains/createDomain.json',
                'get' => 'Domains/getAllDomains.json'
            ],
            '/environments/32-a47ac10b-58cc-4372-a567-0e02b2c3d470/domains/domain' => [
                'delete' => 'Domains/deleteDomain.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/domains/domain' => [
                'delete' => 'Domains/deleteDomain.json'
            ],
            '/environments/15-a47ac10b-58cc-4372-a567-0e02b2c3d470/domains/domain/status' => [
                'get' => 'Domains/getDomainStatus.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/domains/actions/clear-varnish' => [
                'post' => 'Domains/purgeVarnish.json'
            ],
            '/environments/32-a47ac10b-58cc-4372-a567-0e02b2c3d470/files' => [
                'post' => 'Environments/copyFiles.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/files' => [
                'post' => 'Environments/copyFiles.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/crons' => [
                'get' => 'Crons/getAllCrons.json',
                'post' => 'Crons/createCron.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/crons/cronId' => [
                'get' => 'Crons/getCron.json',
                'delete' => 'Crons/deleteCron.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/crons/cronId/actions/enable' => [
                'post' => 'Crons/enableCron.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/crons/cronId/actions/disable' => [
                'post' => 'Crons/disableCron.json'
            ],
            '/environments/15-a47ac10b-58cc-4372-a567-0e02b2c3d470/code/actions/switch' => [
                'post' => 'Code/switchCode.json'
            ],
            '/environments/32-a47ac10b-58cc-4372-a567-0e02b2c3d470/code' => [
                'post' => 'Code/deployCode.json'
            ],
            '/applications/a47ac10b-58cc-4372-a567-0e02b2c3d470/code' => [
                'get' => 'Code/getAllCode.json'
            ],
            '/applications/a47ac10b-58cc-4372-a567-0e02b2c3d470/insight' => [
                'get' => 'Insights/getAllInsights.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/livedev/actions/disable' => [
                'post' => 'Environments/disableLiveDev.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/livedev/actions/enable' => [
                'post' => 'Environments/enableLiveDev.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/servers' => [
                'get' => 'Servers/getAllServers.json'
            ],
            '/environments/15-a47ac10b-58cc-4372-a567-0e02b2c3d470/servers' => [
                'get' => 'Servers/getAllServers.json'
            ],
            '/environments/32-a47ac10b-58cc-4372-a567-0e02b2c3d470/servers' => [
                'get' => 'Servers/getAllServers.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/actions/change-label' => [
                'post' => 'Environments/renameEnvironment.json'
            ],
            '/environments/15-a47ac10b-58cc-4372-a567-0e02b2c3d470/production-mode/actions/disable' => [
                'post' => 'Environments/disableProductionMode.json'
            ],
            '/environments/15-a47ac10b-58cc-4372-a567-0e02b2c3d470/production-mode/actions/enable' => [
                'post' => 'Environments/disableProductionMode.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/variables/variable_one' => [
                'put' => 'Variables/updateVariable.json',
                'get' => 'Variables/getVariable.json',
                'delete' => 'Variables/deleteVariable.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/variables' => [
                'get' => 'Variables/getAllVariables.json',
                'post' => 'Variables/createVariable.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/logs' => [
                'get' => 'Logs/getAllLogs.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/logs/apache-access' => [
                'get' => 'Logs/downloadLog.dat',
                'post' => 'Logs/createLogSnapshot.json'
            ],
            '/teams/teamUuid/invites' => [
                'post' => 'Teams/invite.json'
            ],
            '/teams/teamUuid/applications' => [
                'post' => 'Teams/addApplication.json'
            ],
            '/permissions' => [
                'get' => 'Permissions/getPermissions.json'
            ],
            '/notifications/f4b37e3c-1g96-4ed4-ad20-3081fe0f9545' => [
                'get' => 'Notifications/getNotification.json'
            ],
            '/organizations' => [
                'get' => 'Organizations/getAllOrganizations.json'
            ],
            '/organizations/g47ac10b-58cc-4372-a567-0e02b2c3d470/admins' => [
                'get' => 'Organizations/getAdmins.json'
            ],
            '/organizations/g47ac10b-58cc-4372-a567-0e02b2c3d470/teams' => [
                'get' => 'Organizations/getTeams.json',
                'post' => 'Teams/createTeam.json'
            ],
            '/organizations/g47ac10b-58cc-4372-a567-0e02b2c3d470/applications' => [
                'get' => 'Organizations/getApplications.json'
            ],
            '/organizations/g47ac10b-58cc-4372-a567-0e02b2c3d470/members' => [
                'get' => 'Organizations/getMembers.json'
            ],
            '/organizations/g47ac10b-58cc-4372-a567-0e02b2c3d470/roles' => [
                'get' => 'Roles/getAllRoles.json',
                'post' => 'Roles/createRole.json'
            ],
            '/insight/siteId/modules' => [
                'get' => 'Insights/getModules.json'
            ],
            '/insight/siteId/alerts' => [
                'get' => 'Insights/getAllAlerts.json'
            ],
            '/insight/siteId/alerts/alertUuid' => [
                'get' => 'Insights/getAlert.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/insight' => [
                'get' => 'Insights/getEnvironment.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/logstream' => [
                'get' => 'Logs/getLogstream.json',
            ],
            '/applications/foobar/environments' => [
                'get' => 'Environments/getAllEnvironments.json'
            ],
            '/applications/foobar/databases' => [
                'get' => 'Databases/getAllDatabases.json',
                'post' => 'Databases/createDatabases.json',
            ],
            '/notifications/42b56cff-0b55-4bdf-a949-1fd0fca61c6c' => [
                'get' => 'Notifications/getNotification.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/log-forwarding-destinations' => [
                'get' => 'LogForwarding/getAllLogForwarding.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/log-forwarding-destinations/1234' => [
                'get' => 'LogForwarding/getLogForwarding.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/ssl/certificates' => [
                'get' => 'SslCertificates/getAllSslCertificates.json'
            ],
            '/environments/24-a47ac10b-58cc-4372-a567-0e02b2c3d470/ssl/certificates/1234' => [
                'get' => 'SslCertificates/getSslCertificate.json'
            ]
        ];
    }

    protected function getPrivateProperty($className, $propertyName)
    {
        $reflector = new \ReflectionClass($className);
        $property = $reflector->getProperty($propertyName);
        $property->setAccessible(true);

        return $property;
    }
}
