<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Endpoints\Notifications;
use AcquiaCloudApi\Endpoints\Organizations;
use Symfony\Component\Console\Helper\Table;

/**
 * Class TasksCommand
 * @package AcquiaCli\Commands
 */
class NotificationsCommand extends AcquiaCommand
{

    protected $notificationsAdapter;

    public function __construct()
    {
        parent::__construct();

        $this->notificationsAdapter = new Notifications($this->getCloudApi());
    }

    /**
     * Gets all notifications associated with a site.
     *
     * @param string $uuid
     * @param int    $limit  The maximum number of items to return.
     * @param string $filter
     * @param string $sort   Sortable by: 'name', 'title', 'created', 'completed', 'started'.
     * A leading "~" in the field indicates the field should be sorted in a descending order.
     *
     * @command notification:list
     * @alias n:l
     */
    public function notificationList($uuid, $limit = 50, $filter = null, $sort = '~created_at')
    {

        // Allows for limits and sort criteria.
        $sort = str_replace('~', '-', $sort);
        $this->cloudapi->addQuery('limit', $limit);
        $this->cloudapi->addQuery('sort', $sort);
        if (null !== $filter) {
            $this->cloudapi->addQuery('filter', "name=${filter}");
        }

        $notifications = $this->notificationsAdapter->getAll($uuid);
        $this->cloudapi->clearQuery();

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['UUID', 'Created', 'Name', 'Status']);

        $extraConfig = $this->cloudapiService->getExtraConfig();

        $tz = $extraConfig['timezone'];
        $format = $extraConfig['format'];
        $timezone = new \DateTimeZone($tz);

        foreach ($notifications as $notification) {
            $createdDate = new \DateTime($notification->created_at);
            $createdDate->setTimezone($timezone);

            $table
                ->addRows([
                    [
                        $notification->uuid,
                        $createdDate->format($format),
                        $notification->label,
                        $notification->status,
                    ],
                ]);
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
     * @alias n:i
     * @throws \Exception
     */
    public function notificationInfo($uuid, $notificationUuid)
    {

        $extraConfig = $this->cloudapiService->getExtraConfig();
        $tz = $extraConfig['timezone'];
        $format = $extraConfig['format'];
        $timezone = new \DateTimeZone($tz);

        $notification = $this->notificationsAdapter->get($notificationUuid);

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
