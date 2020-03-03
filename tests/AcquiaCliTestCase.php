<?php

namespace AcquiaCli\Tests;

use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Endpoints\Databases;
use AcquiaCloudApi\Endpoints\DatabaseBackups;
use AcquiaCloudApi\Endpoints\Environments;
use AcquiaCloudApi\Response\OperationResponse;
use AcquiaCloudApi\Response\DatabasesResponse;
use AcquiaCloudApi\Response\BackupsResponse;
use AcquiaCloudApi\Response\EnvironmentsResponse;
use AcquiaCloudApi\Response\ApplicationsResponse;

use League\Container\ContainerAwareInterface;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Robo\Config\Config;
use Consolidation\Config\Loader\ConfigProcessor;
use Consolidation\Config\Loader\YamlConfigLoader;
use AcquiaCli\AcquiaCli;
use Symfony\Component\Console\Output\BufferedOutput;


use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;

/**
 * Class AcquiaCliTestCase
 */
abstract class AcquiaCliTestCase extends TestCase
{

    protected function setup()
    {
        parent::setUp();
    }

    protected function getPsr7StreamForFixture($fixture): Psr7\Stream
    {
        $path = sprintf('%s/vendor/typhonius/acquia-php-sdk-v2/tests/Fixtures/Endpoints/%s', dirname(__DIR__), $fixture);
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
        $path = sprintf('%s/vendor/typhonius/acquia-php-sdk-v2/tests/Fixtures/Endpoints/%s', dirname(__DIR__), $fixture);
        $this->assertFileExists($path);
        return json_decode(file_get_contents($path));
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
     * @param  mixed  $response
     * @return Client
     */
    protected function getMockClient($response = '')
    {
        if ($response) {
            $connector = $this
                ->getMockBuilder('AcquiaCloudApi\Connector\Connector')
                ->disableOriginalConstructor()
                ->setMethods(['sendRequest'])
                ->getMock();

            $connector
                ->expects($this->atLeastOnce())
                ->method('sendRequest')
                ->willReturn($response);
        } else {
            $connector = $this
                ->getMockBuilder('AcquiaCloudApi\Connector\Connector')
                ->disableOriginalConstructor()
                ->getMock();
        }

        $client = Client::factory($connector);

        return $client;
    }

    /**
     * Mock client class.
     *
     * @param  mixed  $response
     * @return Client
     */
    protected function getDatabasesMock()
    {
        $databases = $this
            ->getMockBuilder('AcquiaCli\Endpoints\Databases')
            ->disableOriginalConstructor()
            ->setMethods(['create', 'getAll', 'delete', 'truncate', 'copy'])
            ->getMock();

        $createFixture = $this->getPhpSdkResponse('Databases/createDatabases.json');
        $dbCreateResponse = new OperationResponse($createFixture);
        $databases->method('create')->willReturn($dbCreateResponse);

        $getAllFixture = $this->getPhpSdkResponse('Databases/getAllDatabases.json');
        $dbGetAllResponse = new DatabasesResponse($getAllFixture->_embedded->items);
        $databases->method('getAll')->willReturn($dbGetAllResponse);

        return $databases;
    }

    /**
     * Mock client class.
     *
     * @param  mixed  $response
     * @return Client
     */
    protected function getDatabaseBackupsMock()
    {
        $databaseBackups = $this
            ->getMockBuilder('AcquiaCli\Endpoints\DatabaseBackups')
            ->disableOriginalConstructor()
            ->setMethods(['create', 'get', 'getAll', 'download', 'restore'])
            ->getMock();

        $createFixture = $this->getPhpSdkResponse('DatabaseBackups/createDatabaseBackup.json');
        $dbCreateResponse = new OperationResponse($createFixture);
        $databaseBackups->method('create')->willReturn($dbCreateResponse);

        $getAllFixture = $this->getPhpSdkResponse('DatabaseBackups/getAllDatabaseBackups.json');
        $dbGetAllResponse = new BackupsResponse($getAllFixture->_embedded->items);
        $databaseBackups->method('getAll')->willReturn($dbGetAllResponse);

        return $databaseBackups;
    }

    /**
     * Mock client class.
     *
     * @param  mixed  $response
     * @return Client
     */
    protected function getEnvironmentsMock()
    {

        $environments = $this
            ->getMockBuilder('AcquiaCli\Endpoints\Environments')
            ->disableOriginalConstructor()
            ->setMethods(['get', 'getAll'])
            ->getMock();

        $getAllFixture = $this->getPhpSdkResponse('Environments/getAllEnvironments.json');
        $environmentGetAllResponse = new EnvironmentsResponse($getAllFixture->_embedded->items);
        $environments->method('getAll')->willReturn($environmentGetAllResponse);

        return $environments;
    }

    protected function getApplicationsMock()
    {

        $environments = $this
            ->getMockBuilder('AcquiaCli\Endpoints\Applications')
            ->disableOriginalConstructor()
            ->setMethods(['get', 'getAll'])
            ->getMock();

        $getAllFixture = $this->getPhpSdkResponse('Applications/getAllApplications.json');
        $applicationsGetAllResponse = new ApplicationsResponse($getAllFixture->_embedded->items);
        $environments->method('getAll')->willReturn($applicationsGetAllResponse);

        return $environments;
    }


    protected function getPrivateProperty($className, $propertyName)
    {
        $reflector = new \ReflectionClass($className);
        $property = $reflector->getProperty($propertyName);
        $property->setAccessible(true);

        return $property;
    }

    public function execute($client, $command)
    {
        array_unshift($command, 'acquiacli');
        $input = new ArgvInput($command);
        $output = new BufferedOutput();

        $app = new AcquiaCliTest($input, $output, $client);
        $app->run($input, $output);

        return $output->fetch();
    }
}
