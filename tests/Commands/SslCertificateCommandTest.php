<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\SslCertificateCommand;

class SslCertificateCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp(): void
    {
        $this->setupCommandTester(SslCertificateCommand::class);
    }

    /**
     * @dataProvider sslCertificateProvider
     */
    public function testSslCertificateInfo($command, $arguments, $expected)
    {
        list($actualResponse, $statusCode) = $this->executeCommand($command, $arguments);
        $this->assertSame($expected, $actualResponse);
    }

    public function sslCertificateProvider()
    {
        $sslCertificatesPath = dirname(__DIR__) . "/Fixtures/SslCertificates";

        $listResponse = <<<LIST
+----+--------------------+-----------------+--------------------------+--------+
| ID |       Label        |     Domains     |         Expires          | Active |
+----+--------------------+-----------------+--------------------------+--------+
| 7  |                    |   example.com   | 2022-03-28T00:12:34-0400 |   ✓    |
|    |                    | www.example.com |                          |        |
| 3  | Test Certificate 1 |   example.com   | 2022-03-28T00:12:34-0400 |   ✓    |
|    |                    | www.example.com |                          |        |
| 4  | Test Certificate 2 |   example.com   | 2022-03-28T00:12:34-0400 |        |
|    |                    | www.example.com |                          |        |
+----+--------------------+-----------------+--------------------------+--------+
LIST;

        $infoResponse = <<<INFO
Certificate                
                                             
-----BEGIN CERTIFICATE-----...-----END CERTIFICATE-----
                                             
                       CA                    
                                             
-----BEGIN CERTIFICATE-----...-----END CERTIFICATE-----
                                             
                  Private Key                
                                             
-----BEGIN RSA PRIVATE KEY-----...-----END RSA PRIVATE KEY-----
INFO;

        return [
            [
                'ssl:list',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev'],
                $listResponse
            ],
            [
                'ssl:info',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'certificateId' => '1234'],
                $infoResponse,
            ],
            [
                'ssl:enable',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'certificateId' => '1234'],
                '>  Activating certificate on Dev environment.',
            ],
            [
                'ssl:disable',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'certificateId' => '1234'],
                '>  Disabling certificate on Dev environment.',
            ],
            [
                'ssl:create',
                [
                    'uuid' => 'devcloud:devcloud2',
                    'environment' => 'dev',
                    'label' => 'Test Certificate 2',
                    'certificate' => $sslCertificatesPath . '/cert.pem',
                    'key' => $sslCertificatesPath . '/key.pem',
                    'ca' => $sslCertificatesPath . '/ca.pem',
                    '--activate' => true
                ],
                '>  Installing new certificate Test Certificate 2 on Dev environment.' . PHP_EOL .
                '>  Activating certificate Test Certificate 2 on Dev environment.'
            ],
            [
                'ssl:create',
                [
                    'uuid' => 'devcloud:devcloud2',
                    'environment' => 'dev',
                    'label' => 'Test Certificate 2',
                    'certificate' => $sslCertificatesPath . '/cert.pem',
                    'key' => $sslCertificatesPath . '/key.pem'
                ],
                '>  Installing new certificate Test Certificate 2 on Dev environment.',
            ],
            [
                'ssl:create',
                [
                    'uuid' => 'devcloud:devcloud2',
                    'environment' => 'dev',
                    'label' => 'Test Certificate 2',
                    'certificate' => '/nopath/cert.pem',
                    'key' => $sslCertificatesPath . '/key.pem'
                ],
                ' [error]  Cannot open certificate file at /nopath/cert.pem. ',
            ],
            [
                'ssl:create',
                [
                    'uuid' => 'devcloud:devcloud2',
                    'environment' => 'dev',
                    'label' => 'Test Certificate 2',
                    'certificate' => $sslCertificatesPath . '/cert.pem',
                    'key' => '/nopath/key.pem'
                ],
                ' [error]  Cannot open key file at /nopath/key.pem. ',
            ],
            [
                'ssl:create',
                [
                    'uuid' => 'devcloud:devcloud2',
                    'environment' => 'dev',
                    'label' => 'Test Certificate 2',
                    'certificate' => $sslCertificatesPath . '/cert.pem',
                    'key' => $sslCertificatesPath . '/key.pem',
                    'ca' => '/nopath/ca.pem'
                ],
                ' [error]  Cannot open ca file at /nopath/ca.pem. ',
            ]
        ];
    }
}
