<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\PermissionResponse;
use AcquiaCloudApi\Response\RoleResponse;
use Symfony\Component\Console\Helper\Table;

/**
 * Class TeamsCommand
 * @package AcquiaCli\Commands
 */
class TeamsCommand extends AcquiaCommand
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
     * Invites a user to a team.
     *
     * @param string $teamUuid
     * @param string $email    The email address for the user that needs to be invited.
     * @param string $roles    A comma separated list of roles that a user should be invited to.
     *
     * @command team:invite
     */
    public function teamInvite($teamUuid, $email, $roles)
    {
        $rolesArray = explode(',', $roles);
        $this->cloudapi->teamInvite($teamUuid, $email, $rolesArray);
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
     * @alias perm:list
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
     * Adds a new role to an organization.
     *
     * @param string      $organizationUuid
     * @param string      $name             A human readable role name e.g. 'Release Managers'
     * @param string      $permissions      A comma separated list of permissions a role should have e.g. 'administer domain non-prod,administer ssh keys,deploy to non-prod'
     * @param null|string $description      A human readable description of the role e.g. 'For non-technical users to create releases'
     *
     * @command role:add
     */
    public function addRole($organizationUuid, $name, $permissions, $description = null)
    {
        $permissionsArray = explode(',', $permissions);
        $this->cloudapi->organizationRoleCreate($organizationUuid, $name, $permissionsArray, $description);
    }

    /**
     * Deletes a role.
     *
     * @param string $roleUuid
     *
     * @command role:delete
     */
    public function deleteRole($roleUuid)
    {
        $this->cloudapi->roleRemove($roleUuid);
    }

    /**
     * Updates the permissions for a role.
     *
     * @param string $roleUuid
     * @param string $permissions A comma separated list of permissions a role should have e.g. 'administer domain non-prod,administer ssh keys,deploy to non-prod'
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
