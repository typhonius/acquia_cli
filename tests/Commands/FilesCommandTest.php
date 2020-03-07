<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class FilesCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider filesProvider
     */
    public function testFilesCommands($command, $fixture, $expected)
    {
        $actualResponse = $this->execute($command);
        $this->assertSame($expected, $actualResponse);
    }

    public function filesProvider()
    {

        return [
            [
                ['files:copy', 'uuid', 'environmentFrom', 'environmentTo'],
                'Environments/copyFiles.json',
                '>  Copying files from Mock Env to Mock Env' . PHP_EOL
            ]
        ];
    }
}
