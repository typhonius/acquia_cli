<?php

namespace AcquiaCli\Tests;

use AcquiaCloudApi\Connector\Client;
use Symfony\Component\Console\Input\ArgvInput;
use Robo\Config\Config;
use Consolidation\Config\Loader\ConfigProcessor;
use Consolidation\Config\Loader\YamlConfigLoader;
use AcquiaCli\AcquiaCli;
use Symfony\Component\Console\Output\BufferedOutput;
use Consolidation\AnnotatedCommand\CommandData;


use Robo\Robo;


use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

/**
 * Class AcquiaCliTestCase
 */
abstract class AcquiaCliTestCase extends TestCase
{

    public $client;

    public function setUp()
    {
        $this->client = $this->getMockClient();
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
     * Returns a PSR7 Stream for a given fixture.
     *
     * @param  string     $fixture The fixture to create the stream for.
     * @return Psr7\Stream
     */
    protected function getPhpSdkResponse($fixture): object
    {
        $path = sprintf(
            '%s/vendor/typhonius/acquia-php-sdk-v2/tests/Fixtures/Endpoints/%s',
            dirname(__DIR__),
            $fixture
        );
        $this->assertFileExists($path);
        if ($contents = file_get_contents($path)) {
            return json_decode($contents);
        } else {
            throw new \Exception(sprintf('Fixture file %s not able to be opened.', $path));
        }
    }

    /**
     * Returns a PSR7 Response (JSON) for a given fixture.
     *
     * @param  string        $fixture    The fixture to create the response for.
     * @param  integer       $statusCode A HTTP Status Code for the response.
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
     * @param  string        $fixture    The fixture to create the response for.
     * @param  integer       $statusCode A HTTP Status Code for the response.
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

    public function sendRequestCallback($verb, $path)
    {
        //echo sprintf('%s -> %s', $verb, $path) . PHP_EOL;
        $fixtureMap = self::getFixtureMap();

        if ($fixture = $fixtureMap[$path][$verb]) {
            return $this->getPsr7JsonResponseForFixture($fixture);
        }
    }

    public static function getFixtureMap()
    {
        return [
            '/account' => [
                'get' => 'Account/getAccount.json'
            ],
            '/applications' => [
                'get' => 'Applications/getAllApplications.json',
            ],
            '/applications/a47ac10b-58cc-4372-a567-0e02b2c3d470/environments' => [
                'get' => 'Environments/getAllEnvironments.json'
            ],
            '/applications/uuid/tags' => [
                'get' => 'Applications/getAllTags.json',
                'post' => 'Applications/createTag.json',
            ],
            '/applications/uuid/tags/name' => [
                'delete' => 'Applications/deleteTag.json',
            ],
            '/applications/uuid/databases' => [
                'get' => 'Databases/getAllDatabases.json',
                'post' => 'Databases/createDatabases.json',
            ],
            '/applications/uuid/databases/dbName/actions/erase' => [
                'post' => 'Databases/truncateDatabases.json',
            ],
            '/applications/uuid/databases/dbName' => [
                'delete' => 'Databases/deleteDatabases.json'
            ],
            '/roles/roleUuid' => [
                'put' => 'Roles/updateRole.json',
                'delete' => 'Roles/deleteRole.json'
            ],
            '/organizations/organisation/roles' => [
                'post' => 'Roles/createRole.json'
            ],
            '/applications/uuid/environments' => [
                'get' => 'Environments/getAllEnvironments.json'
            ],
            '/applications/uuid/notifications' => [
                'get' => 'Notifications/getAllNotifications.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a' => [
                'delete' => 'Environments/deleteCDEnvironment.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/databases/database2/backups' => [
                'get' => 'DatabaseBackups/getAllDatabaseBackups.json',
                'post' => 'DatabaseBackups/createDatabaseBackup.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/databases/database1/backups' => [
                'get' => 'DatabaseBackups/getAllDatabaseBackups.json',
                'post' => 'DatabaseBackups/createDatabaseBackup.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/databases' => [
                'post' => 'Databases/copyDatabases.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/databases/dbName/backups/1234/actions/restore' => [
                'post' => 'DatabaseBackups/restoreDatabaseBackup.json',
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/domains' => [
                'post' => 'Domains/createDomain.json',
                'get' => 'Domains/getAllDomains.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/domains/domain' => [
                'delete' => 'Domains/deleteDomain.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/domains/domain/status' => [
                'get' => 'Domains/getDomainStatus.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/domains/actions/clear-varnish' => [
                'post' => 'Domains/purgeVarnish.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/files' => [
                'post' => 'Environments/copyFiles.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/crons' => [
                'get' => 'Crons/getAllCrons.json',
                'post' => 'Crons/createCron.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/crons/cronId' => [
                'get' => 'Crons/getCron.json',
                'delete' => 'Crons/deleteCron.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/crons/cronId/actions/enable' => [
                'post' => 'Crons/enableCron.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/crons/cronId/actions/disable' => [
                'post' => 'Crons/disableCron.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/code/actions/switch' => [
                'post' => 'Code/switchCode.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/code' => [
                'post' => 'Code/deployCode.json'
            ],
            '/applications/uuid/code' => [
                'get' => 'Code/getAllCode.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/livedev/actions/disable' => [
                'post' => 'Environments/disableLiveDev.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/livedev/actions/enable' => [
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
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/actions/change-label' => [
                'post' => 'Environments/renameEnvironment.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/production-mode/actions/disable' => [
                'post' => 'Environments/disableProductionMode.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/production-mode/actions/enable' => [
                'post' => 'Environments/disableProductionMode.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/variables/variable_one' => [
                'put' => 'Variables/updateVariable.json',
                'get' => 'Variables/getVariable.json',
                'delete' => 'Variables/deleteVariable.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/variables' => [
                'get' => 'Variables/getAllVariables.json',
                'post' => 'Variables/createVariable.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/logs' => [
                'get' => 'Logs/getAllLogs.json'
            ],
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/logs/apache-access' => [
                'post' => 'Logs/createLogSnapshot.json'
            ],
            '/teams/teamUuid/invites' => [
                'post' => 'Teams/invite.json'
            ],
            '/organizations/organizationUuid/teams' => [
                'post' => 'Teams/createTeam.json'
            ],
            '/organizations/g47ac10b-58cc-4372-a567-0e02b2c3d472/roles' => [
                'get' => 'Roles/getAllRoles.json'
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
            '/organizations/g47ac10b-58cc-4372-a567-0e02b2c3d472/admins' => [
                'get' => 'Organizations/getAdmins.json'
            ],
            '/organizations/g47ac10b-58cc-4372-a567-0e02b2c3d472/teams' => [
                'get' => 'Organizations/getTeams.json'
            ],
            '/organizations/g47ac10b-58cc-4372-a567-0e02b2c3d472/applications' => [
                'get' => 'Organizations/getApplications.json'
            ],
            '/organizations/g47ac10b-58cc-4372-a567-0e02b2c3d472/members' => [
                'get' => 'Organizations/getMembers.json'
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
            '/environments/bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a/insight' => [
                'get' => 'Insights/getEnvironment.json'
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

    public function execute($command)
    {

        $config = new Config();
        $loader = new YamlConfigLoader();
        $processor = new ConfigProcessor();
        $processor->extend($loader->load(dirname(__DIR__) . '/default.acquiacli.yml'));
        $config->import($processor->export());

        array_unshift($command, 'acquiacli', '--no-wait', '--yes');
        $input = new ArgvInput($command);
        $output = new BufferedOutput();

        $app = new AcquiaCliTest($config, $this->client, $input, $output);
        $app->run($input, $output);

        Robo::unsetContainer();

        return $output->fetch();
    }
}
