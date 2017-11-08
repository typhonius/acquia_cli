<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Response\EnvironmentsResponse;
use AcquiaCloudApi\Response\PermissionResponse;
use AcquiaCloudApi\Response\RoleResponse;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

/**
 * Class PermissionsCommand
 * @package AcquiaCli\Commands
 */
class PermissionsCommand extends AcquiaCommand
{

    /**
     * Creates a new team within an organization.
     *
     * @param string $organizationUuid
     * @param string $name
     *
     * @command team:create
     */
    public function teamCreate($organizationUuid, $name)
    {
        $this->cloudapi->teamCreate($organizationUuid, $name);
    }

    /**
     * Assigns an application to a team.
     *
     * @param string $teamUuid
     * @param string $applicationUuid
     *
     * @command team:addapp
     */
    public function teamAddApplication($teamUuid, $applicationUuid)
    {
        $this->cloudapi->teamAddApplication($teamUuid, $applicationUuid);
    }

    /**
     * Displays all permissions available on the Acquia Cloud.
     *
     * @command permissions:list
     */
    public function showPermissions()
    {
        $permissions = $this->cloudapi->permissions();

        $table = new Table($this->output());
        $table->setHeaders(array('Name', 'Label'));
        foreach ($permissions as $permission) {
            /** @var PermissionResponse $permission */
            $table
                ->addRows([
                    [
                        $permission->name,
                        $permission->label,
                    ],
                ]);
        }

        $table->render();
    }

    /**
     * @param string      $organizationUuid
     * @param string      $name
     * @param string      $permissions      A comma separated list of permissions.
     * @param null|string $description
     *
     * @command role:add
     */
    public function addRole($organizationUuid, $name, $permissions, $description = null)
    {
        $permissionsArray = explode(',', $permissions);
        $this->cloudapi->organizationRoleCreate($organizationUuid, $name, $permissionsArray, $description);
    }

    /**
     * @param string $roleUuid
     * @param string $permissions
     *
     * @command role:update:permissions
     */
    public function roleUpdatePermissions($roleUuid, $permissions)
    {
        $permissionsArray = explode(',', $permissions);
        $this->cloudapi->roleUpdatePermissions($roleUuid, $permissionsArray);
    }

    /**
     * Shows all roles within an organization.
     *
     * @param string $organizationUuid
     *
     * @command role:list
     */
    public function showRoles($organizationUuid)
    {

        $roles = $this->cloudapi->organizationRoles($organizationUuid);
        $permissions = $this->cloudapi->permissions();

        $roleList = array_map(function($role) {
            $this->say($role->name . ': ' . $role->uuid);
            return $role->name;
        }, $roles->getArrayCopy());

        array_unshift($roleList, 'Permission');

        $table = new Table($this->output());
        $table->setHeaders($roleList);

        foreach ($permissions as $permission) {
            /** @var PermissionResponse $permission */
            $roleHasPermission = false;
            $permissionsMatrix = [$permission->name];
            foreach ($roles as $role) {
                /** @var RoleResponse $role */
                foreach ($role->permissions as $rolePermission) {
                    if ($rolePermission->name == $permission->name) {
                        $permissionsMatrix[] = '✅';
                        $roleHasPermission = true;
                        continue;
                    }
                }
                if ($roleHasPermission === false) {
                    $permissionsMatrix[] = '';
                }
            }

            $table
                ->addRows([
                    $permissionsMatrix,

                ]);
        }

        $table->render();
    }
}
