<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\AccountCommand;

class AccountCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp()
    {
        $this->setupCommandTester(AccountCommand::class);
    }

    public function testDownloadDrushCommands()
    {

        list($actualResponse, $statusCode) = $this->executeCommand('drush:aliases');

        $this->assertEquals(
            preg_match(
                '@>  Acquia Cloud Drush Aliases archive downloaded to ((\S+)AcquiaDrushAliases\w+\.tar\.gz).*@',
              $actualResponse, $matches),
            1
        );

        $this->assertStringStartsWith('>  Acquia Cloud Drush Aliases archive downloaded to ', $actualResponse);
        $this->assertStringContainsString(sys_get_temp_dir(), $matches[2]);

        $testFilePath = sprintf(
            '%s/vendor/typhonius/acquia-php-sdk-v2/tests/Fixtures/Endpoints/%s',
            dirname(dirname(__DIR__)),
            'Account/getDrushAliases.dat'
        );
        $this->assertFileExists($testFilePath);
        $testFileContents = file_get_contents($testFilePath);
        $downloadedFile = $matches[1];
        $downloadedFileContents = file_get_contents($downloadedFile);
        $this->assertEquals($testFileContents, $downloadedFileContents);
    }

    /**
     * @dataProvider accountProvider
     */
    public function testAccountInfo($command, $expected)
    {
        list($actualResponse, $statusCode) = $this->executeCommand($command);
        $this->assertSame($expected, $actualResponse);
    }

    public function accountProvider()
    {

        $infoResponse = <<<INFO
>  Name: jane.doe
>  Last login: 2017-03-29 05:07:54
>  Created at: 2017-03-29 05:07:54
>  Status: ✓
>  TFA: ✓
INFO;

        return [
            [
                'account',
                $infoResponse
            ]
        ];
    }
}
