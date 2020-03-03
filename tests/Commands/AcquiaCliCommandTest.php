<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Tests\Helpers\CommandTesterTrait;
use AcquiaCli\Tests\Helpers\CommandTesterInterface;
use PHPUnit\Framework\TestCase;


class AcquiaCliCommandTest extends TestCase implements CommandTesterInterface
{

    use CommandTesterTrait;

    protected $commandClasses;

    public function setUp()
    {
        parent::setUp();
    }

}
