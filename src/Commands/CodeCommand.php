<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\BranchResponse;
use Symfony\Component\Console\Helper\Table;
use AcquiaCloudApi\Endpoints\Code;

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
     * @param string $match A string to filter out specific code branches with.
     *
     * @command branch:list
     * @alias code:list
     */
    public function code($uuid, $match = null)
    {
        if (null !== $match) {
            $this->cloudapi->addQuery('filter', "name=@*${match}*");
        }
        $codeAdapter = new Code($this->cloudapi);
        $branches = $codeAdapter->getAll($uuid);
        $this->cloudapi->clearQuery();

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['Name', 'Tag']);

        foreach ($branches as $branch) {
            /** @var BranchResponse $branch */
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
