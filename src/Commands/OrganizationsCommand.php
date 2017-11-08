<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\ApplicationResponse;
use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Response\EnvironmentsResponse;
use AcquiaCloudApi\Response\OrganizationResponse;
use AcquiaCloudApi\Response\TeamResponse;
use Symfony\Component\Console\Helper\Table;

/**
 * Class OrganizationsCommand
 * @package AcquiaCli\Commands
 */
class OrganizationsCommand extends AcquiaCommand
{

    /**
     * Shows a list of all organizations.
     *
     * @command organization:list
     */
    public function showOrganizations()
    {
        $organizations = $this->cloudapi->organizations();

        $table = new Table($this->output());
        $table->setHeaders(array('UUID', 'Organization', 'Owner', 'Subs', 'Admins', 'Users', 'Teams', 'Roles'));
        foreach ($organizations as $organization) {
            /** @var OrganizationResponse $permission */
            $table
                ->addRows([
                    [
                        $organization->uuid,
                        $organization->name,
                        $organization->owner->username,
                        $organization->subscriptions_total,
                        $organization->admins_total,
                        $organization->users_total,
                        $organization->teams_total,
                        $organization->roles_total,
                    ],
                ]);
        }

        $table->render();
    }

    /**
     * Shows a list of all applications within an organization.
     *
     * @param string $organizationUuid
     *
     * @command organization:applications
     */
    public function organizationApplications($organizationUuid)
    {
        $applications = $this->cloudapi->organizationApplications($organizationUuid);

        $this->say("Applications in organisation: ${organizationUuid}");
        $table = new Table($this->output());
        $table->setHeaders(array('UUID', 'Name', 'Type', 'Hosting ID'));
        foreach ($applications as $application) {
            /** @var ApplicationResponse $permission */
            $table
                ->addRows([
                    [
                        $application->uuid,
                        $application->name,
                        $application->hosting->type,
                        $application->hosting->id,
                    ],
                ]);
        }

        $table->render();
    }

    /**
     * @param string $organizationUuid
     *
     * @command organization:teams
     */
    public function organizationTeams($organizationUuid)
    {
        $teams = $this->cloudapi->organizationTeams($organizationUuid);

        $this->say("Teams in organisation: ${organizationUuid}");
        $table = new Table($this->output());
        $table->setHeaders(array('UUID', 'Name'));
        foreach ($teams as $team) {
            /** @var TeamResponse $permission */
            $table
                ->addRows([
                    [
                        $team->uuid,
                        $team->name,
                    ],
                ]);
        }

        $table->render();
    }

}
