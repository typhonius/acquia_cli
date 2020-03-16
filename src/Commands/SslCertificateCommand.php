<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Endpoints\SslCertificates;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SslCertificateCommand
 *
 * @package AcquiaCli\Commands
 */
class SslCertificateCommand extends AcquiaCommand
{

    /**
     * Lists SSL Certificates.
     *
     * @param string $uuid
     * @param string $environment
     *
     * @command ssl:list
     */
    public function sslCertificateList(
        OutputInterface $output,
        SslCertificates $certificatesAdapter,
        $uuid,
        $environment
    ) {
        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);
        $certificates = $certificatesAdapter->getAll($environment->uuid);

        $table = new Table($output);
        $table->setHeaders(['ID', 'Label', 'Domains', 'Expires', 'Active']);
        $table->setColumnStyle(1, 'center-align');
        $table->setColumnStyle(2, 'center-align');
        $table->setColumnStyle(3, 'center-align');
        $table->setColumnStyle(4, 'center-align');

        foreach ($certificates as $certificate) {
            /**
             * @var SslCertificateResponse $certificate
             */
            $table
                ->addRows(
                    [
                    [
                        $certificate->id,
                        $certificate->label,
                        implode($certificate->domains, "\n"),
                        $certificate->expires_at,
                        $certificate->flags->active ? '✓' : '',
                    ],
                    ]
                );
        }

        $table->render();
    }

    /**
     * Gets information about an SSL certificate.
     *
     * @param string $uuid
     * @param string $environment
     * @param int    $certificateId
     *
     * @command ssl:info
     */
    public function sslCertificateInfo(
        OutputInterface $output,
        SslCertificates $certificatesAdapter,
        $uuid,
        $environment,
        $certificateId
    ) {
        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);
        $certificate = $certificatesAdapter->get($environment->uuid, $certificateId);

        $this->yell('Certificate');
        $this->writeln($certificate->certificate);
        $this->yell('CA');
        $this->writeln($certificate->ca);
        $this->yell('Private Key');
        $this->writeln($certificate->private_key);
    }
}
