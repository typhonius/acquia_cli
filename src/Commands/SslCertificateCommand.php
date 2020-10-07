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
     * Install an SSL certificate
     *
     * @param string $uuid
     * @param string $environment
     * @param string $label
     * @param string $cert The Certificate file path
     * @param string $key The Key file path
     * @param null|string $ca The Chain file path
     * @option enable Enable certification after creation.
     * @command ssl:create
     */
    public function sslCertificateCreate(
        SslCertificates $certificatesAdapter,
        $uuid,
        $environment,
        $label,
        $cert,
        $key,
        $ca = null,
        $options = ['enable']
    ) {
        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);

        if ($this->confirm('Are you sure you want to install this new SSL certificate?')) {
            $this->say(sprintf('Installing new certificate %s on %s environment', $label, $environment->label));

            if (!file_exists($cert) || !is_readable($cert)) {
                throw new \Exception(sprintf('Cannot open %s file', $cert));
            }
            $cert = strval(file_get_contents($cert));

            if (!file_exists($key) || !is_readable($key)) {
                throw new \Exception(sprintf('Cannot open %s file', $key));
            }
            $key = strval(file_get_contents($key));

            if ($ca !== null) {
                if (!file_exists($ca) || !is_readable($ca)) {
                    throw new \Exception(sprintf('Cannot open %s ca file', $ca));
                }
                $ca = strval(file_get_contents($ca));
            }

            $response = $certificatesAdapter->create(
                $environment->uuid,
                $label,
                $cert,
                $key,
                $ca
            );

            $this->waitForNotification($response);

            if ($options['enable']) {
                $certificates = $certificatesAdapter->getAll($environment->uuid);
                foreach ($certificates as $certificate) {
                    /**
                     * @var SslCertificateResponse $certificate
                     */
                    if ($certificate->label === $label && !$certificate->flags->active) {
                        $this->say(sprintf('Enabling certificate %s on %s environment', $certificate->label, $environment->label));
                        $response = $certificatesAdapter->enable($environment->uuid, $certificate->id);
                        $this->waitForNotification($response);
                    }elseif ($certificate->flags->active){
                        // Make sure all the others certificates are disabled
                        $this->say(sprintf('Disabling certificate %s on %s environment', $certificate->label, $environment->label));
                        $response = $certificatesAdapter->disable($environment->uuid, $certificate->id);
                        $this->waitForNotification($response);
                    }
                }
            }
        }
    }
}
