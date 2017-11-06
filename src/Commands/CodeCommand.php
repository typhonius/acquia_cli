<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Response\EnvironmentsResponse;
use Symfony\Component\Console\Helper\Table;

/**
 * Class CodeCommand
 * @package AcquiaCli\Commands
 */
class CodeCommand extends AcquiaCommand
{

    /**
     * Gets all code branches and tags associated with an application.
     *
     * @param string $uuid
     * @param string $match
     *
     * @command code:list
     */
    public function code($uuid, $match = null)
    {
        if (null !== $match) {
            $this->cloudapi->addQuery('filter', "name=@*${match}*");
        }
        $code = $this->cloudapi->code($uuid);
        $this->cloudapi->clearQuery();

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['Name', 'Tag']);

        foreach ($code as $branch) {
            $tag = $branch->flags->tag ? '✅' : '';
            $table
                ->addRows([
                    [
                        $branch->name,
                        $tag,
                    ],
                ]);
        }

        $table->render();
    }
}
