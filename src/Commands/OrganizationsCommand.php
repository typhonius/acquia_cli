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
 *
 * @package AcquiaCli\Commands
 */
class OrganizationsCommand extends AcquiaCommand
{

    /**
     * Shows a list of all organizations.
     *
     * @command organization:list
     * @aliases org:list,o:l
     */
    public function showOrganizations(Organizations $organizationsAdapter)
    {
        $organizations = $organizationsAdapter->getAll();

        $table = new Table($this->output());
        $table->setHeaders(['UUID', 'Organization', 'Owner', 'Subs', 'Admins', 'Users', 'Teams', 'Roles']);
        foreach ($organizations as $organization) {
            /**
             * @var OrganizationResponse $permission
             */
            $table
                ->addRows(
                    [
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
                    ]
                );
        }

        $table->render();
    }

    /**
     * Shows a list of all applications within an organization.
     *
     * @param string $organization
     *
     * @command organization:applications
     * @aliases org:apps,o:a
     */
    public function organizationApplications(Organizations $organizationsAdapter, $organization)
    {
        $organization = $this->cloudapiService->getOrganization($organization);
        $applications = $organizationsAdapter->getApplications($organization->uuid);

        $this->say(sprintf('Applications in organisation: %s', $organization->uuid));
        $table = new Table($this->output());
        $table->setHeaders(['UUID', 'Name', 'Type', 'Hosting ID']);
        foreach ($applications as $application) {
            /**
             * @var ApplicationResponse $application
             */
            $table
                ->addRows(
                    [
                    [
                        $application->uuid,
                        $application->name,
                        $application->hosting->type,
                        $application->hosting->id,
                    ],
                    ]
                );
        }

        $table->render();
    }

    /**
     * Shows teams within an organization.
     *
     * @param string $organization
     *
     * @command organization:teams
     * @aliases org:teams,o:t
     */
    public function organizationTeams(Organizations $organizationsAdapter, $organization)
    {
        $organization = $this->cloudapiService->getOrganization($organization);
        $teams = $organizationsAdapter->getTeams($organization->uuid);

        $this->say(sprintf('Teams in organisation: %s', $organization->uuid));
        $table = new Table($this->output());
        $table->setHeaders(['UUID', 'Name']);
        foreach ($teams as $team) {
            /**
             * @var TeamResponse $team
             */
            $table
                ->addRows(
                    [
                    [
                        $team->uuid,
                        $team->name,
                    ],
                    ]
                );
        }

        $table->render();
    }

    /**
     * Shows all members.
     *
     * @param string $organization
     *
     * @command organization:members
     * @aliases org:members,o:m
     */
    public function members(Organizations $organizationsAdapter, $organization)
    {
        $organization = $this->cloudapiService->getOrganization($organization);
        $organizationUuid = $organization->uuid;
        $admins = $organizationsAdapter->getAdmins($organization->uuid);
        $members = $organizationsAdapter->getMembers($organization->uuid);

        $this->say(sprintf('Members in organisation: %s', $organization->uuid));
        $table = new Table($this->output());
        $table
            ->setHeaders(['UUID', 'Username', 'Mail', 'Teams(s)'])
            ->setColumnStyle(0, 'center-align')
            ->setRows(
                [
                [new TableCell('Organisation Administrators', ['colspan' => 4])],
                new TableSeparator(),
                ]
            );

        foreach ($admins as $admin) {
            /**
             * @var MemberResponse $admin
             */
            $table
                ->addRows(
                    [
                    [
                        $admin->uuid,
                        $admin->username,
                        $admin->mail,
                        'admin'
                    ],
                    ]
                );
        }

        $table
        ->addRows(
            [
            new TableSeparator(),
            [new TableCell('Organisation Members', ['colspan' => 4])],
            new TableSeparator(),
            ]
        );

        foreach ($members as $member) {
            /**
             * @var MemberResponse $member
             */
            $teamList = array_map(
                function ($team) {
                    return $team->name;
                },
                $member->teams->getArrayCopy()
            );
            $teamString = implode(',', $teamList);
            $table
                ->addRows(
                    [
                    [
                        $member->uuid,
                        $member->username,
                        $member->mail,
                        $teamString,
                    ],
                    ]
                );
        }

        $table->render();
    }
}
