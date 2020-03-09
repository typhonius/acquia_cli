<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\OrganizationResponse;
use AcquiaCloudApi\Response\PermissionResponse;
use AcquiaCloudApi\Response\RoleResponse;
use Symfony\Component\Console\Helper\Table;
use AcquiaCloudApi\Endpoints\Teams;
use AcquiaCloudApi\Endpoints\Permissions;
use AcquiaCloudApi\Endpoints\Roles;

/**
 * Class TeamsCommand
 * @package AcquiaCli\Commands
 */
class TeamsCommand extends AcquiaCommand
{

    /**
     * Creates a new team within an organization.
     *
     * @param string $organization
     * @param string $name
     *
     * @command team:create
     */
    public function teamCreate(Teams $teamsAdapter, $organization, $name)
    {
        $organization = $this->cloudapiService->getOrganization($organization);
        $this->say('Creating new team.');
        $teamsAdapter->create($organization->uuid, $name);
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
    public function teamInvite(Teams $teamsAdapter, $teamUuid, $email, $roles)
    {
        $rolesArray = explode(',', $roles);
        $this->say(sprintf('Inviting %s to team.', $email));
        $teamsAdapter->invite($teamUuid, $email, $rolesArray);
    }

    /**
     * Assigns an application to a team.
     *
     * @param string $uuid
     * @param string $teamUuid
     *
     * @command team:addapplication
     * @alias team:addapp
     */
    public function teamAddApplication(Teams $teamsAdapter, $uuid, $teamUuid)
    {
        $this->say("Adding application to team.");
        $teamsAdapter->addApplication($teamUuid, $uuid);
    }

    /**
     * Displays all permissions available on the Acquia Cloud.
     *
     * @command permissions:list
     * @aliases perm:list
     */
    public function showPermissions(Permissions $permissionsAdapter)
    {
        $permissions = $permissionsAdapter->get();

        $table = new Table($this->output());
        $table->setHeaders(['Name', 'Label']);
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
     * @param string      $organization
     * @param string      $name             A human readable role name e.g. 'Release Managers'
     * @param string      $permissions      A comma separated list of permissions a role should have
     * e.g. 'administer domain non-prod,administer ssh keys,deploy to non-prod'
     * @param null|string $description      A human readable description of the role
     * e.g. 'For non-technical users to create releases'
     *
     * @command role:add
     */
    public function addRole(Roles $rolesAdapter, $organization, $name, $permissions, $description = null)
    {
        $organization = $this->cloudapiService->getOrganization($organization);
        $permissionsArray = explode(',', $permissions);
        $this->say(sprintf('Creating new role (%s) and adding it to organisation.', $name));
        $rolesAdapter->create($organization->uuid, $name, $permissionsArray, $description);
    }

    /**
     * Deletes a role.
     *
     * @param string $roleUuid
     *
     * @command role:delete
     */
    public function deleteRole(Roles $rolesAdapter, $roleUuid)
    {
        if ($this->confirm('Are you sure you want to remove this role?')) {
            $this->say('Deleting role');
            $rolesAdapter->delete($roleUuid);
        }
    }

    /**
     * Updates the permissions for a role.
     *
     * @param string $roleUuid
     * @param string $permissions A comma separated list of permissions a role should have
     * e.g. 'administer domain non-prod,administer ssh keys,deploy to non-prod'
     *
     * @command role:update:permissions
     */
    public function roleUpdatePermissions(Roles $rolesAdapter, $roleUuid, $permissions)
    {
        $permissionsArray = explode(',', $permissions);
        $this->say('Updating role permissions');
        $rolesAdapter->update($roleUuid, $permissionsArray);
    }

    /**
     * Shows all roles within an organization.
     *
     * @param OrganizationResponse $organization
     *
     * @command role:list
     */
    public function showRoles(Roles $rolesAdapter, Permissions $permissionsAdapter, $organization)
    {
        $organization = $this->cloudapiService->getOrganization($organization);

        $organizationUuid = $organization->uuid;
        $roles = $rolesAdapter->getAll($organizationUuid);

        $permissions = $permissionsAdapter->get();

        $roleList = array_map(function ($role) {
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
                        $permissionsMatrix[] = '✓';
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
