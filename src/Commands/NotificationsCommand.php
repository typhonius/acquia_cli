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

        $notificationsAdapter = new Notifications($this->cloudapi);
        $notifications = $notificationsAdapter->getAll($uuid);

        $this->cloudapi->clearQuery();

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['UUID', 'Created', 'Name', 'Status']);

        $tz = $this->extraConfig['timezone'];
        $format = $this->extraConfig['format'];
        $timezone = new \DateTimeZone($tz);

        // // Get members to map to notifications below
        // $organizationUuid = $notifications[0]->context->organization->uuids[0];
        // $organizationsAdapter = new Organizations($this->cloudapi);
        
        // // $members = [];
        // // foreach ($organizationsAdapter->getMembers($organizationUuid) as $member) {
        // //     $members[$member->uuid] = $member->mail;
        // // }
        // // var_dump($members);
        // // die;

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
     * @param string $taskUuid
     *
     * @command task:info
     * @alias n:i
     * @throws \Exception
     */
    public function notificationInfo($uuid, $notificationUuid)
    {

        $tz = $this->extraConfig['timezone'];
        $format = $this->extraConfig['format'];

        $this->cloudapi->addQuery('limit', 100);
        $this->cloudapi->addQuery('sort', '~created');
        $tasks = $this->cloudapi->tasks($uuid);

        foreach ($tasks as $task) {
            if ($taskUuid === $task->uuid) {
                $timezone = new \DateTimeZone($tz);

                $createdDate = new \DateTime($task->createdAt);
                $startedDate = new \DateTime($task->startedAt);
                $completedDate = new \DateTime($task->completedAt);

                $createdDate->setTimezone($timezone);
                $startedDate->setTimezone($timezone);
                $completedDate->setTimezone($timezone);

                $this->say('ID: ' . $task->uuid);
                $this->say('Sender: ' . $task->user->mail);
                $this->say('Description: ' . htmlspecialchars_decode($task->description));
                $this->say('Status: ' . $task->status);
                $this->say('Created: ' . $createdDate->format($format));
                $this->say('Started: ' . $startedDate->format($format));
                $this->say('Completed: ' . $completedDate->format($format));

                return;
            }
        }
        throw new \Exception('Unable to find Task ID');
    }
}
