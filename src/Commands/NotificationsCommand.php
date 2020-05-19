<?php

namespace AcquiaCli\Commands;

use AcquiaCli\Cli\Config;
use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Endpoints\Notifications;
use AcquiaCloudApi\Endpoints\Applications;
use AcquiaCloudApi\Endpoints\Organizations;
use Symfony\Component\Console\Helper\Table;
use AcquiaCloudApi\Exception\ApiErrorException;

/**
 * Class NotificationsCommand
 *
 * @package AcquiaCli\Commands
 */
class NotificationsCommand extends AcquiaCommand
{

    /**
     * Gets all notifications associated with a site.
     *
     * @param string $uuid
     * @param int    $limit  The maximum number of items to return.
     * @param string $filter
     * @param string $sort   Sortable by: 'name', 'title', 'created', 'completed', 'started'.
     *                       A leading "~" in the field indicates the field should be sorted in a descending order.
     *
     * @command notification:list
     * @option full Whether to show more details in the notication list (slower).
     * @aliases n:l
     */
    public function notificationList(
        Config $config,
        Client $client,
        Applications $applicationsAdapter,
        Organizations $organizationsAdapter,
        Notifications $notificationsAdapter,
        $uuid,
        $limit = 50,
        $filter = null,
        $sort = '~created_at',
        $options = ['full']
    ) {

        // Allows for limits and sort criteria.
        $sort = str_replace('~', '-', $sort);
        $client->addQuery('limit', $limit);
        $client->addQuery('sort', $sort);
        if (null !== $filter) {
            $client->addQuery('filter', "name=${filter}");
        }

        $notifications = $notificationsAdapter->getAll($uuid);
        $client->clearQuery();

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['UUID', 'Created', 'Name', 'Status']);

        $extraConfig = $config->get('extraconfig');
        $tz = $extraConfig['timezone'];
        $format = $extraConfig['format'];
        $timezone = new \DateTimeZone($tz);


        if ($options['full']) {
            $table->setHeaders(['UUID', 'User', 'Created', 'Name', 'Status']);

            $application = $applicationsAdapter->get($uuid);
            $orgUuid = $application->organization->uuid;
    
            $admins = $organizationsAdapter->getAdmins($orgUuid);
            $members = $organizationsAdapter->getMembers($orgUuid);
    
            $users = $admins->getArrayCopy() + $members->getArrayCopy();

            $uuids = array_reduce($users, function ($result, $member) {
                $result[$member->uuid] = $member->mail;
                return $result;
            }, []);
        }

        foreach ($notifications as $notification) {
            $createdDate = new \DateTime($notification->created_at);
            $createdDate->setTimezone($timezone);

            $rows = [
                    $notification->uuid,
                    $createdDate->format($format),
                    $notification->label,
                    $notification->status,
            ];

            if ($options['full']) {
                $author = $notification->context->author->uuid;
                array_splice($rows, 1, 0, $uuids[$author]);
            }

            $table
                ->addRows(
                    [
                        $rows
                    ]
                );
        }

        $table->render();
    }

    /**
     * Gets detailed information about a specific notification
     *
     * @param string $uuid
     * @param string $notificationUuid
     *
     * @command notification:info
     * @aliases n:i
     * @throws  \Exception
     */
    public function notificationInfo(Config $config, Notifications $notificationsAdapter, $uuid, $notificationUuid)
    {

        $extraConfig = $config->get('extraconfig');
        $tz = $extraConfig['timezone'];
        $format = $extraConfig['format'];
        $timezone = new \DateTimeZone($tz);

        $notification = $notificationsAdapter->get($notificationUuid);

        $createdDate = new \DateTime($notification->created_at);
        $createdDate->setTimezone($timezone);
        $completedDate = new \DateTime($notification->completed_at);
        $completedDate->setTimezone($timezone);

        $this->say(sprintf('ID: %s', $notification->uuid));
        $this->say(sprintf('Event: %s', $notification->event));
        $this->say(sprintf('Description: %s', htmlspecialchars_decode($notification->description)));
        $this->say(sprintf('Status: %s', $notification->status));
        $this->say(sprintf('Created: %s', $createdDate->format($format)));
        $this->say(sprintf('Completed: %s', $completedDate->format($format)));
    }
}
