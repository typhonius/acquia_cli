<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Endpoints\LogForwardingDestinations;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class LogForwardCommand
 *
 * @package AcquiaCli\Commands
 */
class LogForwardCommand extends AcquiaCommand
{

    /**
     * Lists Log Forwards.
     *
     * @param string $uuid
     * @param string $environment
     *
     * @command logforward:list
     * @aliases lf:list
     */
    public function logforwardList(
        OutputInterface $output,
        LogForwardingDestinations $logForwardAdapter,
        $uuid,
        $environment
    ) {
        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);
        $logForwards = $logForwardAdapter->getAll($environment->uuid);

        $table = new Table($output);
        $table->setHeaders(['UUID', 'Label', 'Address', 'Consumer', 'Active']);
        $table->setColumnStyle(1, 'center-align');
        $table->setColumnStyle(2, 'center-align');
        $table->setColumnStyle(3, 'center-align');
        $table->setColumnStyle(4, 'center-align');

        foreach ($logForwards as $logForward) {
            /**
             * @var LogForwardingDestinationResponse $logForward
             */
            $table
                ->addRows(
                    [
                    [
                        $logForward->uuid,
                        $logForward->label,
                        $logForward->address,
                        $logForward->consumer,
                        $logForward->status === 'active' ? '✓' : '',
                    ],
                    ]
                );
        }

        $table->render();
    }

    /**
     * Gets information about a Log ForwaRD.
     *
     * @param string $uuid
     * @param string $environment
     * @param int    $destinationId
     *
     * @command logforward:info
     * @aliases lf:info
     */
    public function logforwardInfo(
        OutputInterface $output,
        LogForwardingDestinations $logForwardAdapter,
        $uuid,
        $environment,
        $destinationId
    ) {
        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);
        $logForward = $logForwardAdapter->get($environment->uuid, $destinationId);

        $this->yell(sprintf('Log server address: %s', $logForward->address));
        $this->say(sprintf('Certificate: %s', $logForward->credentials->certificate->certificate));
        $this->say(sprintf('Expires at: %s', $logForward->credentials->certificate->expires_at));
        $this->say(sprintf('Token: %s', $logForward->credentials->token));
        $this->say(sprintf('Key: %s', $logForward->credentials->key));
        $this->say(sprintf('Sources: %s%s', "\n", implode($logForward->sources, "\n")));
        $this->say(sprintf('Health: %s', $logForward->health->summary));
    }
}
