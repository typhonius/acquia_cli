<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\InsightCountResponse;
use AcquiaCloudApi\Response\InsightResponse;
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
     * @param string $environment
     *
     * @command insights:info
     */
    public function acquiaInsightsInfo($uuid, $environment = null)
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

        $this->say('Status: ' . $insight->status);

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
