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
                        implode("\n", $certificate->domains),
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

    /**
     * Enables an SSL certificate.
     *
     * @param string $uuid
     * @param string $environment
     * @param int    $certificateId
     *
     * @command ssl:enable
     */
    public function sslCertificateEnable(
        SslCertificates $certificatesAdapter,
        $uuid,
        $environment,
        $certificateId
    ) {
        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);

        if ($this->confirm('Are you sure you want to enable this SSL certificate?')) {
            $this->say(sprintf('Enabling certificate on %s environment', $environment->label));
            $response = $certificatesAdapter->enable($environment->uuid, $certificateId);
            $this->waitForNotification($response);
        }
    }

    /**
     * Disables an SSL certificate.
     *
     * @param string $uuid
     * @param string $environment
     * @param int    $certificateId
     *
     * @command ssl:disable
     */
    public function sslCertificateDisable(
        SslCertificates $certificatesAdapter,
        $uuid,
        $environment,
        $certificateId
    ) {
        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);

        if ($this->confirm('Are you sure you want to disable this SSL certificate?')) {
            $this->say(sprintf('Disabling certificate on %s environment', $environment->label));
            $response = $certificatesAdapter->disable($environment->uuid, $certificateId);
            $this->waitForNotification($response);
        }
    }

    /**
     * Install an SSL certificate.
     *
     * @param string $uuid
     * @param string $environment
     * @param string $label
     * @param string $cert
     * @param string $key
     * @param null|string $ca
     *
     * @command ssl:create
     */
    public function sslCertificateCreate(
        OutputInterface $output,
        SslCertificates $certificatesAdapter,
        $uuid,
        $environment,
        $label,
        $cert,
        $key,
        $ca = null
    ) {
        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);
        $this->say(sprintf('Installing new certificate (%s)', $label));
        $certificatesAdapter->create(
            $environment->uuid,
            $label,
            $cert,
            $key,
            $ca
        );
    }
}
