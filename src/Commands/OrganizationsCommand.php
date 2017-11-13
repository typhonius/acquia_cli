<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\ApplicationResponse;
use AcquiaCloudApi\Response\MemberResponse;
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
     * @alias org:list
     */
    public function showOrganizations()
    {
        $organizations = $this->cloudapi->organizations();

        $table = new Table($this->output());
        $table->setHeaders(['UUID', 'Organization', 'Owner', 'Subs', 'Admins', 'Users', 'Teams', 'Roles']);
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
     * @alias org:apps
     */
    public function organizationApplications($organizationUuid)
    {
        $applications = $this->cloudapi->organizationApplications($organizationUuid);

        $this->say("Applications in organisation: ${organizationUuid}");
        $table = new Table($this->output());
        $table->setHeaders(['UUID', 'Name', 'Type', 'Hosting ID']);
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
     * Shows teams within an organization.
     *
     * @param string $organizationUuid
     *
     * @command organization:teams
     * @alias org:teams
     */
    public function organizationTeams($organizationUuid)
    {
        $teams = $this->cloudapi->organizationTeams($organizationUuid);

        $this->say("Teams in organisation: ${organizationUuid}");
        $table = new Table($this->output());
        $table->setHeaders(['UUID', 'Name']);
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

    /**
     * Shows all members.
     *
     * @param string $organizationUuid
     *
     * @command organization:members
     * @alias org:members
     */
    public function members($organizationUuid)
    {
        $members = $this->cloudapi->members($organizationUuid);

        $this->say("Members in organisation: ${organizationUuid}");
        $table = new Table($this->output());
        $table->setHeaders(['UUID', 'Username', 'Mail', 'Teams(s)']);
        foreach ($members as $member) {
            $teamList = array_map(function ($team) {
                return $team->name;
            }, $member->teams->getArrayCopy());
            $teamString = implode(',', $teamList);
            /** @var MemberResponse $permission */
            $table
                ->addRows([
                    [
                        $member->uuid,
                        $member->username,
                        $member->mail,
                        $teamString,
                    ],
                ]);
        }

        $table->render();
    }
}
