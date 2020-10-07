<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Endpoints\SslCertificates;
use AcquiaCloudApi\Response\SslCertificateResponse;
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

        if ($this->confirm('Are you sure you want to activate this SSL certificate? Activating this certificate will deactivate all other non-legacy certificates.')) {
            $this->say(sprintf('Activating certificate on %s environment.', $environment->label));
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
            $this->say(sprintf('Disabling certificate on %s environment.', $environment->label));
            $response = $certificatesAdapter->disable($environment->uuid, $certificateId);
            $this->waitForNotification($response);
        }
    }

    /**
     * Install an SSL certificate
     *
     * @param string $uuid
     * @param string $environment
     * @param string $label
     * @param string $certificate The path to the certificate file.
     * @param string $key The path to the private key file.
     * @param null|string $ca The path to the certificate authority file.
     * @option activate Enable certification after creation.
     * @command ssl:create
     */
    public function sslCertificateCreate(
        SslCertificates $certificatesAdapter,
        $uuid,
        $environment,
        $label,
        $certificate,
        $key,
        $ca = null,
        $options = ['activate']
    ) {
        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);

        $confirmMessage = 'Are you sure you want to install this new SSL certificate? (It will not be activated unless the --activate option is passed).';
        if ($options['activate']) {
            $confirmMessage = 'Are you sure you want to install and activate this new SSL certificate? Activating this certificate will deactivate all other non-legacy certificates.';
        }
        if ($this->confirm($confirmMessage)) {
            if (!file_exists($certificate) || !is_readable($certificate)) {
                throw new \Exception(sprintf('Cannot open certificate file at %s.', $certificate));
            }
            $certificate = strval(file_get_contents($certificate));

            if (!file_exists($key) || !is_readable($key)) {
                throw new \Exception(sprintf('Cannot open key file at %s.', $key));
            }
            $key = strval(file_get_contents($key));

            if ($ca !== null) {
                if (!file_exists($ca) || !is_readable($ca)) {
                    throw new \Exception(sprintf('Cannot open ca file at %s.', $ca));
                }
                $ca = strval(file_get_contents($ca));
            }

            $this->say(sprintf('Installing new certificate %s on %s environment.', $label, $environment->label));

            $response = $certificatesAdapter->create(
                $environment->uuid,
                $label,
                $certificate,
                $key,
                $ca
            );

            $this->waitForNotification($response);

            if ($options['activate']) {
                $certificates = $certificatesAdapter->getAll($environment->uuid);
                foreach ($certificates as $installedCertificate) {
                    /**
                     * @var SslCertificateResponse $certificate
                     */
                    if ($installedCertificate->label === $label && !$installedCertificate->flags->active) {
                        $this->say(sprintf('Activating certificate %s on %s environment.', $installedCertificate->label, $environment->label));
                        $response = $certificatesAdapter->enable($environment->uuid, $installedCertificate->id);
                        $this->waitForNotification($response);
                    }
                }
            }
        }
    }
}
