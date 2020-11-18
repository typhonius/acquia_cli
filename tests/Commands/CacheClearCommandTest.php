<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class CacheClearCommandTest extends AcquiaCliTestCase
{

    public function testClearCache()
    {
        // Run a basic command to fill the cache.
        $command = ['database:backup:list', 'devcloud:devcloud2', 'dev'];
        $this->execute($command);

        // Ensure the items exist in the cache before we attempt a clear.
        $cache = new FilesystemAdapter('acquiacli');

        $this->assertTrue($cache->hasItem('application.devcloud.devcloud2'));
        $this->assertTrue($cache->hasItem('environment.a47ac10b-58cc-4372-a567-0e02b2c3d470.dev'));

        // Clear the cache.
        $command = ['cache:clear'];
        $this->execute($command);

        $this->assertFalse($cache->hasItem('application.devcloud.devcloud2'));
        $this->assertFalse($cache->hasItem('environment.a47ac10b-58cc-4372-a567-0e02b2c3d470.dev'));
    }
}
