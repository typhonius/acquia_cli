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
+-----------------------------+--------------------------------------------------------------------------+
| Name                        | Label                                                                    |
+-----------------------------+--------------------------------------------------------------------------+
| administer alerts           | Manage Insight alerts                                                    |
| revoke insight installs     | Revoke Insight sites                                                     |
| deploy to non-prod          | Pull and deploy code, files, or databases to non-production environments |
| deploy to prod              | Deploy code, files, or databases to the production environment           |
| pull from prod              | Pull files or databases from the production environment                  |
| move file to non-prod       | Move files to non-production environments                                |
| move file to prod           | Move files to the production environment                                 |
| move file from prod         | Move files from production environments                                  |
| move file from non-prod     | Move files from non-production environments                              |
| clear varnish on non-prod   | Clear Varnish cache for non-production environments                      |
| clear varnish on prod       | Clear Varnish cache for the production environment                       |
| configure prod env          | Configure production environment                                         |
| configure non-prod env      | Configure non-production environments                                    |
| add an environment          | Add an environment                                                       |
| delete an environment       | Delete an environment                                                    |
| administer domain non-prod  | Add or remove domains for non-production environments                    |
| administer domain prod      | Add or remove domains for the production environment                     |
| administer ssl prod         | Add or remove SSL certificates for the production environment            |
| administer ssl non-prod     | Add or remove SSL certificates for the non-production environments       |
| reboot server               | Reboot server                                                            |
| resize server               | Resize server                                                            |
| suspend server              | Suspend server                                                           |
| configure server            | Configure server                                                         |
| download logs non-prod      | Download logs for non-production environments                            |
| download logs prod          | Download logs for the production environment                             |
| add database                | Add a database                                                           |
| remove database             | Remove a database                                                        |
| view database connection    | View database connection details (username, password, or hostname)       |
| download db backup non-prod | Download database backups for non-production environments                |
| download db backup prod     | Download database backups for the production environment                 |
| create db backup non-prod   | Create database backups for non-production environments                  |
| create db backup prod       | Create database backups for the production environment                   |
| restore db backup non-prod  | Restore database backups for non-production environments                 |
| restore db backup prod      | Restore database backups for the production environment                  |
| administer team             | Add or remove a user of a team                                           |
| access cloud api            | Access the Cloud API                                                     |
| administer cron non-prod    | Modify cron tasks for non-production environments                        |
| administer cron prod        | Modify cron tasks for the production environment                         |
| search limit increase       | Increase the search index limit for a subscription                       |
| search schema edit          | Edit the search schema for a subscription                                |
| create support ticket       | Create a support ticket                                                  |
| edit any support ticket     | View and edit any support tickets for a subscription                     |
| administer ssh keys         | Manage SSH keys                                                          |
| view build plans            | View Build plans                                                         |
| edit build plans            | Edit Build plans                                                         |
| run build plans             | Run Build plans                                                          |
| add ssh key to git          | Add SSH key to git repository                                            |
| add ssh key to non-prod     | Add SSH key to non-production environments                               |
| add ssh key to prod         | Add SSH key to the production environment                                |
| view remote administration  | View Remote Administration                                               |
| edit remote administration  | Edit Remote Administration                                               |
+-----------------------------+--------------------------------------------------------------------------+
LIST;

        return [
            [
                ['permissions:list'],
                $permissions . PHP_EOL
            ]
        ];
    }
}
