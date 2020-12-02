<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\FilesCommand;

class FilesCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp(): void
    {
        $this->setupCommandTester(FilesCommand::class);
    }

    /**
     * @dataProvider filesProvider
     */
    public function testFilesCommands($command, $arguments, $expected)
    {
        list($actualResponse, $statusCode) = $this->executeCommand($command, $arguments);
        $this->assertSame($expected, $actualResponse);
    }

    public function filesProvider()
    {

        return [
            [
                'files:copy',
                ['uuid' => 'devcloud:devcloud2', 'environmentFrom' => 'dev', 'environmentTo' => 'test'],
                '>  Copying files from Dev to Stage'
            ]
        ];
    }
}
