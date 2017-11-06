<?php

namespace AcquiaCli\Commands;

use Symfony\Component\Console\Helper\Table;

/**
 * Class TasksCommand
 * @package AcquiaCli\Commands
 */
class TasksCommand extends AcquiaCommand
{

    /**
     * Gets all tasks associated with a site.
     *
     * @param string $uuid
     * @param int    $limit  The maximum number of items to return.
     * @param string $filter
     * @param string $sort   Sortable by: 'name', 'title', 'created', 'completed', 'started'.
     * A leading "~" in the field indicates the field should be sorted in a descending order.
     *
     * @command task:list
     * @alias t:l
     */
    public function acquiaTasks($uuid, $limit = 100, $filter = null, $sort = '~created')
    {

        // Allows for limits and sort criteria.
        str_replace('~', '-', $sort);
        $this->cloudapi->addQuery('limit', $limit);
        $this->cloudapi->addQuery('sort', $sort);
        if (null !== $filter) {
            $this->cloudapi->addQuery('filter', "name=${filter}");
        }
        $tasks = $this->cloudapi->tasks($uuid);
        $this->cloudapi->clearQuery();

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['ID', 'Created', 'Name', 'Status', 'User']);

        $tz = $this->extraConfig['timezone'];
        $format = $this->extraConfig['format'];
        $timezone = new \DateTimeZone($tz);

        foreach ($tasks as $task) {
            $createdDate = new \DateTime($task->createdAt);
            $createdDate->setTimezone($timezone);

            $table
                ->addRows([
                    [
                        $task->uuid,
                        $createdDate->format($format),
                        $task->name,
                        $task->status,
                        $task->user->mail,
                    ],
                ]);
        }

        $table->render();
    }

    /**
     * Gets detailed information about a specific task
     *
     * @param string $uuid
     * @param string $taskUuid
     *
     * @command task:info
     * @alias t:i
     * @throws \Exception
     */
    public function acquiaTask($uuid, $taskUuid)
    {

        $tz = $this->extraConfig['timezone'];
        $format = $this->extraConfig['format'];

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
