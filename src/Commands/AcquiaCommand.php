<?php

namespace AcquiaCli\Commands;

use Acquia\Cloud\Api\Response\Task;
use Guzzle\Http\Exception\ServerErrorResponseException;
use Robo\Tasks;
use Robo\Robo;
use Acquia\Cloud\Api\CloudApiClient;
use Exception;

/**
 * Class AcquiaCommand
 * @package AcquiaCli\Commands
 */
abstract class AcquiaCommand extends Tasks
{
    /** @var CloudApiClient $cloudapi */
    protected $cloudapi;

    /** Additional configuration */
    protected $extraConfig;

    /**
     * AcquiaCommand constructor.
     */
    public function __construct()
    {
        $extraConfig = Robo::config()->get('extraconfig');
        $this->extraConfig = $extraConfig;

        $acquia = Robo::config()->get('acquia');
        $cloudapi = CloudApiClient::factory(array(
            'username' => $acquia['mail'],
            'password' => $acquia['pass'],
        ));

        $this->cloudapi = $cloudapi;
    }

    /**
     * @string $site
     * @param Task $task
     * @return bool
     * @throws Exception
     * @throws ServerErrorResponseException
     */
    protected function waitForTask($site, Task $task)
    {
        $taskId = $task->id();
        $complete = false;
        $cloudApiFailures = 0;

        while ($complete === false) {
            try {
                $this->say('Waiting for task to complete...');
                $task = $this->cloudapi->task($site, $taskId);
                if ($task->completed()) {
                    if ($task->state() !== 'done') {
                        throw new Exception('Acquia task failed.');
                    }
                    $complete = true;
                    break;
                } else {
                    sleep(5);
                }
            } catch (Exception $e) {
                if ($e instanceof ServerErrorResponseException) {
                    if ($e->getCode() == 503) {
                        $cloudApiFailures++;
                        if ($cloudApiFailures >= 5) {
                            echo 'Caught exception: ',  $e->getMessage(), "\n";
                            exit(1);
                        }
                    }
                } else {
                    echo 'Caught exception: ',  $e->getMessage(), "\n";
                    exit(1);
                }
            }
        }

        return true;
    }
}

