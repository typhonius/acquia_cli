<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Endpoints\Logs;
use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaLogstream\LogstreamManager;
use Robo\Common\InputAwareTrait;
use Robo\Common\OutputAwareTrait;
use Symfony\Component\Console\Helper\Table;

/**
 * Class LogsCommand
 * @package AcquiaCli\Commands
 */
class LogsCommand extends AcquiaCommand
{

    use InputAwareTrait;
    use OutputAwareTrait;

    /**
     * Streams logs from an environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param array               $opts
     * @option $colourise Colourise the output
     * @option $logtypes  Filter to specific log types
     * @option $servers   Filter to specific servers
     *
     * @command log:stream
     */
    public function logStream(
        $uuid,
        EnvironmentResponse $environment,
        $opts = [
            'colourise|c' => false,
            'logtypes|t' => [],
            'servers|s' => []
        ]
    ) {
        $logsAdapter = new Logs($this->cloudapi);
        $stream = $logsAdapter->stream($environment->uuid);
        $params = $stream->logstream->params;
        $logstream = new LogstreamManager($this->input(), $this->output(), $params);
        if ($opts['colourise']) {
            $logstream->setColourise(true);
        }
        $logstream->setLogTypeFilter($opts['logtypes']);
        $logstream->setLogServerFilter($opts['servers']);
        $logstream->stream();
    }

    /**
     * Shows available log files.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     *
     * @command log:list
     */
    public function logList($uuid, EnvironmentResponse $environment)
    {
        $logsAdapter = new Logs($this->cloudapi);
        $logs = $logsAdapter->getAll($environment->uuid);

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['Type', 'Label', 'Available']);
        $table->setColumnStyle(2, 'center-align');
        foreach ($logs as $log) {
            $table
                ->addRows([
                    [
                        $log->type,
                        $log->label,
                        $log->flags->available ? '✓' : ' ',
                    ],
                ]);
        }
        $table->render();
    }

    /**
     * Creates a log snapshot.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param string              $logType
     *
     * @command log:snapshot
     */
    public function logSnapshot($uuid, $environment, $logType)
    {
        $logsAdapter = new Logs($this->cloudapi);
        $this->say(sprintf('Creating snapshot for %s in %s environment', $logType, $environment->label));
        $logsAdapter->snapshot($environment->uuid, $logType);
    }

    /**
     * Downloads a log file.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param string              $logType
     * @param string              $path
     *
     * @command log:download
     */
    public function logDownload($uuid, $environment, $logType, $path = null)
    {
        $logsAdapter = new Logs($this->cloudapi);
        $label = $environment->label;

        $envName = $environment->name;
        $backupName = "${envName}-${logType}";

        $log = $logsAdapter->download($environment->uuid, $logType);

        if (null === $path) {
            $location = tempnam(sys_get_temp_dir(), $backupName) . '.tar.gz';
        } else {
            // @TODO do we want to put in a tempnam here or allow
            // for full definition of the path?
            $location = $path . $backupName . ".tar.gz";
        }
        if (file_put_contents($location, $log, LOCK_EX)) {
            $this->say(sprintf('Log downloaded to %s', $location));
        } else {
            $this->say('Unable to download log.');
        }
    }
}
