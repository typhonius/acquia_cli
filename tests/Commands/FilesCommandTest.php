<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class FilesCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider filesProvider
     */
    public function testFilesCommands($command, $expected)
    {
        $actualResponse = $this->execute($command);
        $this->assertSame($expected, $actualResponse);
    }

    public function filesProvider()
    {

        return [
            [
                ['files:copy', 'uuid', 'dev', 'test'],
                '>  Copying files from Dev to Stage' . PHP_EOL
            ]
        ];
    }
}
