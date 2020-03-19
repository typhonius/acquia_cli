<?php

namespace AcquiaCli\Commands;

use AcquiaCli\Cli\Config;
use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Endpoints\Notifications;
use AcquiaCloudApi\Endpoints\Organizations;
use Symfony\Component\Console\Helper\Table;

/**
 * Class TasksCommand
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
     * @aliases n:l
     */
    public function notificationList(
        Config $config,
        Client $client,
        Notifications $notificationsAdapter,
        $uuid,
        $limit = 50,
        $filter = null,
        $sort = '~created_at'
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

        foreach ($notifications as $notification) {
            $createdDate = new \DateTime($notification->created_at);
            $createdDate->setTimezone($timezone);

            $table
                ->addRows(
                    [
                    [
                        $notification->uuid,
                        $createdDate->format($format),
                        $notification->label,
                        $notification->status,
                    ],
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
