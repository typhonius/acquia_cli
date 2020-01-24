<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\InsightCountResponse;
use AcquiaCloudApi\Response\InsightResponse;
use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Endpoints\Insights;
use Symfony\Component\Console\Helper\Table;

/**
 * Class InsightsCommand
 * @package AcquiaCli\Commands
 */
class InsightsCommand extends AcquiaCommand
{

    /**
     * Shows Insights information for specified applications.
     *
     * @param string $uuid
     * @param EnvironmentResponse $environment
     *
     * @command insights:info
     */
    public function insightsInfo($uuid, $environment = null)
    {
        $insightsAdapter = new Insights($this->cloudapi);

        if (null === $environment) {
            $insights = $insightsAdapter->getAll($uuid);
        } else {
            $insights = $insightsAdapter->getEnvironment($environment->uuid);
        }
        foreach ($insights as $insight) {
            /** @var InsightResponse $insight */

            $this->renderInsightInfo($insight);
        }
    }

    /**
     * Shows insights alerts for specified applications.
     *
     * @param string $siteId
     * @option failed Whether to only show failed insight checks.
     *
     * @command insights:alerts:list
     */
    public function insightsAlertsList($siteId, $options = ['failed' => null])
    {
        $insightsAdapter = new Insights($this->cloudapi);
        $alerts = $insightsAdapter->getAllAlerts($siteId);

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['UUID', 'Description', 'Failed', 'Resolved', 'Ignored']);
        $table->setColumnStyle(2, 'center-align');
        $table->setColumnStyle(3, 'center-align');
        $table->setColumnStyle(4, 'center-align');

        foreach ($alerts as $alert) {
            /** @var InsightModuleResponse $module */

            if ($options['failed'] && !$alert->failed_value) {
                continue;
            }

            $table
                ->addRows([
                    [
                        $alert->uuid,
                        $alert->name,
                        $alert->failed_value ? '✓' : '',
                        $alert->flags->resolved ? '✓' : '',
                        $alert->flags->ignored ? '✓' : '',
                    ],
                ]);
        }

        $table->render();
    }

    /**
     * Shows insights alerts for specified applications.
     *
     * @param string $siteId
     * @param string $alertUuid
     *
     * @command insights:alerts:get
     */
    public function insightsAlertsGet($siteId, $alertUuid)
    {
        $insightsAdapter = new Insights($this->cloudapi);
        $alert = $insightsAdapter->getAlert($siteId, $alertUuid);

        $this->say(sprintf('UUID: %s', $alert->uuid));
        $this->say(sprintf('Name: %s', $alert->name));
        $this->say(sprintf('Message: %s', filter_var($alert->message, FILTER_SANITIZE_STRING)));
    }

    /**
     * Shows insights alerts for specified applications.
     *
     * @param string $siteId
     * @option enabled Whether to only show enabled modules.
     * @option upgradeable Whether to only show modules that need an upgrade.
     *
     * @command insights:modules
     */
    public function insightsModules($siteId, $options = ['enabled' => null, 'upgradeable' => null])
    {
        $insightsAdapter = new Insights($this->cloudapi);
        $modules = $insightsAdapter->getModules($siteId);

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['Name', 'Version', 'Enabled', 'Upgradeable']);
        $table->setColumnStyle(2, 'center-align');
        $table->setColumnStyle(3, 'center-align');

        foreach ($modules as $module) {
            /** @var InsightModuleResponse $module */

            if ($options['enabled'] && !$module->flags->enabled) {
                continue;
            }
            if ($options['upgradeable'] && array_search('upgradeable', $module->tags, true) === false) {
                continue;
            }

            $table
                ->addRows([
                    [
                        $module->name,
                        $module->version,
                        $module->flags->enabled ? '✓' : '',
                        array_search('upgradeable', $module->tags, true) !== false ? '✓' : '',
                    ],
                ]);
        }

        $table->render();
    }

    /**
     * @param InsightResponse $insight
     */
    private function renderInsightInfo(InsightResponse $insight)
    {
        $title = $insight->label . ' (' . $insight->hostname . ')';
        $score = $insight->scores->insight;

        if ($score >= 85) {
            $colour = 'green';
        } elseif ($score >= 65) {
            $colour = 'yellow';
        } else {
            $colour = 'red';
        }
        $char = $this->decorationCharacter(' ', '➜');
        $format = "${char}  <fg=white;bg=${colour};options=bold>%s</fg=white;bg=${colour};options=bold>";
        $this->formattedOutput("${title} Score: " . $score, 20, $format);

        $this->say(sprintf('Site ID: %s', $insight->uuid));
        $this->say(sprintf('Status: %s', $insight->status));

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['Type', 'Pass', 'Fail', 'Ignored', 'Total', '%']);
        foreach ($insight->counts as $type => $count) {
            /** @var InsightCountResponse $count */
            $table
                ->addRows([
                    [
                        ucwords(str_replace('_', ' ', $type)),
                        $count->pass,
                        $count->fail,
                        $count->ignored,
                        $count->total,
                        $count->percent,
                    ],
                ]);
        }

        $table->render();
    }
}
