<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class AccountCommandTest extends AcquiaCliTestCase
{

    // public function testDownloadDrushCommands()
    // {
    //     $command = ['drush:aliases'];
    //     $actualResponse = $this->execute($command);

    //     $this->assertEquals(
    //         preg_match(
    //             '@>  Acquia Cloud Drush Aliases archive downloaded to ((\S+)AcquiaDrushAliases(\w+).sql.gz)@',
    //           $actualResponse, $matches),
    //         1
    //     );

    //     $this->assertStringStartsWith('>  Acquia Cloud Drush Aliases archive downloaded to ', $actualResponse);
    //     $this->assertStringContainsString(sys_get_temp_dir(), $matches[2]);

    //     $path = sprintf(
    //         '%s/vendor/typhonius/acquia-php-sdk-v2/tests/Fixtures/Endpoints/%s',
    //         dirname(dirname(__DIR__)),
    //         'Account/getDrushAliases.dat'
    //     );
    //     $this->assertFileExists($path);
    //     $contents = file_get_contents($path);
    // }

    /**
     * @dataProvider accountProvider
     */
    public function testAccountInfo($command, $expected)
    {
        $actualResponse = $this->execute($command);
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
                ['account'],
                $infoResponse . PHP_EOL
            ]
        ];
    }
}
