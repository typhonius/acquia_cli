<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class PermissionsCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider permissionsProvider
     */
    public function testPermissionsCommands($command, $expected)
    {
        $actualResponse = $this->execute($command);
        $this->assertSame($expected, $actualResponse);
    }

    public function permissionsProvider()
    {

        $permissions = <<<LIST
+--------------------+--------------------------------------------------------------------------+
| Name               | Label                                                                    |
+--------------------+--------------------------------------------------------------------------+
| administer alerts  | Manage Insight alerts                                                    |
| deploy to non-prod | Pull and deploy code, files, or databases to non-production environments |
| deploy to prod     | Deploy code, files, or databases to the production environment           |
| pull from prod     | Pull files or databases from the production environment                  |
+--------------------+--------------------------------------------------------------------------+
LIST;

        return [
            [
                ['permissions:list'],
                $permissions . PHP_EOL
            ]
        ];
    }
}
