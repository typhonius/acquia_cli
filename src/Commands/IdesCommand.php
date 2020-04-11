<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Endpoints\Ides;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Class IdesCommand
 *
 * @package AcquiaCli\Commands
 */
class IdesCommand extends AcquiaCommand
{

    /**
     * Shows all IDEs.
     *
     * @param string $uuid
     *
     * @command ide:list
     */
    public function list(Ides $idesAdapter, $uuid)
    {
        $ides = $idesAdapter->getAll($uuid);
        $table = new Table($this->output());
        $table->setHeaders(['UUID', 'Label']);
        foreach ($ides as $ide) {
            $table
                ->addRows(
                    [
                    [
                        $ide->uuid,
                        $ide->label,
                    ]
                    ]
                );
        }
        $table->render();
    }

    /**
     * Creates an IDE.
     *
     * @param string $uuid
     * @param string $label
     *
     * @command ide:create
     * @aliases ide:add
     */
    public function create(Ides $idesAdapter, $uuid, $label)
    {
        $response = $idesAdapter->create($uuid, $label);
        $this->say(sprintf('Creating IDE (%s)', $label));
        $this->waitForNotification($response);
    }

    /**
     * De-provisions an IDE.
     *
     * @param string $ideUuid
     *
     * @command ide:delete
     * @aliases ide:remove
     */
    public function delete(Ides $idesAdapter, $ideUuid)
    {
        if ($this->confirm('Are you sure you want to delete this IDE?')) {
            $this->say(sprintf('Deleting IDE (%s)', $ideUuid));
            $response = $idesAdapter->delete($ideUuid);
            $this->waitForNotification($response);
        }
    }
}
