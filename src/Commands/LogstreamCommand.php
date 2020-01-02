<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Endpoints\Logs;
use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaLogstream\LogstreamManager;
use Robo\Common\InputAwareTrait;
use Robo\Common\OutputAwareTrait;

/**
 * Class LogstreamCommand
 * @package AcquiaCli\Commands
 */
class LogstreamCommand extends AcquiaCommand
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
    public function streamLogs($uuid, EnvironmentResponse $environment, $opts = ['colourise|c' => false, 'logtypes|t' => [], 'servers|s' => []])
    {
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
}
