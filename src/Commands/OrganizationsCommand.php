<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\ApplicationResponse;
use AcquiaCloudApi\Response\MemberResponse;
use AcquiaCloudApi\Response\OrganizationResponse;
use AcquiaCloudApi\Response\TeamResponse;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableCell;
use AcquiaCloudApi\Endpoints\Organizations;

/**
 * Class OrganizationsCommand
 * @package AcquiaCli\Commands
 */
class OrganizationsCommand extends AcquiaCommand
{

    protected $organizationsAdapter;

    public function __construct()
    {
        parent::__construct();

        $this->organizationsAdapter = new Organizations($this->getCloudApi());
    }

    /**
     * Shows a list of all organizations.
     *
     * @command organization:list
     * @aliases org:list,o:l
     */
    public function showOrganizations()
    {
        $organizations = $this->organizationsAdapter->getAll();

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
     * @param OrganizationResponse $organization
     *
     * @command organization:applications
     * @aliases org:apps,o:a
     */
    public function organizationApplications($organization)
    {
        $organizationUuid = $organization->uuid;
        $applications = $this->organizationsAdapter->getApplications($organizationUuid);

        $this->say("Applications in organisation: ${organizationUuid}");
        $table = new Table($this->output());
        $table->setHeaders(['UUID', 'Name', 'Type', 'Hosting ID']);
        foreach ($applications as $application) {
            /** @var ApplicationResponse $application */
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
     * @param OrganizationResponse $organization
     *
     * @command organization:teams
     * @aliases org:teams,o:t
     */
    public function organizationTeams($organization)
    {
        $organizationUuid = $organization->uuid;
        $teams = $this->organizationsAdapter->getTeams($organizationUuid);

        $this->say("Teams in organisation: ${organizationUuid}");
        $table = new Table($this->output());
        $table->setHeaders(['UUID', 'Name']);
        foreach ($teams as $team) {
            /** @var TeamResponse $team */
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
     * @param OrganizationResponse $organization
     *
     * @command organization:members
     * @aliases org:members,o:m
     */
    public function members($organization)
    {
        $organizationUuid = $organization->uuid;
        $admins = $this->organizationsAdapter->getAdmins($organizationUuid);
        $members = $this->organizationsAdapter->getMembers($organizationUuid);

        $this->say("Members in organisation: ${organizationUuid}");
        $table = new Table($this->output());
        $table
            ->setHeaders(['UUID', 'Username', 'Mail', 'Teams(s)'])
            ->setColumnStyle(0, 'center-align')
            ->setRows([
                [new TableCell('Organisation Administrators', ['colspan' => 4])],
                new TableSeparator(),
            ]);

        foreach ($admins as $admin) {
            /** @var MemberResponse $admin */
            $table
                ->addRows([
                    [
                        $admin->uuid,
                        $admin->username,
                        $admin->mail,
                        'admin'
                    ],
                ]);
        }

        $table
        ->addRows([
            new TableSeparator(),
            [new TableCell('Organisation Members', ['colspan' => 4])],
            new TableSeparator(),
        ]);

        foreach ($members as $member) {
            /** @var MemberResponse $member */
            $teamList = array_map(function ($team) {
                return $team->name;
            }, $member->teams->getArrayCopy());
            $teamString = implode(',', $teamList);
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
