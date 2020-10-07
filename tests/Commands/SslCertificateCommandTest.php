<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class SslCertificateCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider sslCertificateProvider
     */
    public function testSslCertificateInfo($command, $expected)
    {
        $actualResponse = $this->execute($command);
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
                ['ssl:list', 'devcloud:devcloud2', 'dev'],
                $listResponse . PHP_EOL
            ],
            [
                ['ssl:info', 'devcloud:devcloud2', 'dev', '1234'],
                $infoResponse . PHP_EOL,
            ],
            [
                ['ssl:enable', 'devcloud:devcloud2', 'dev', '1234'],
                '>  Activating certificate on Dev environment.' . PHP_EOL,
            ],
            [
                ['ssl:disable', 'devcloud:devcloud2', 'dev', '1234'],
                '>  Disabling certificate on Dev environment.' . PHP_EOL,
            ],
            [
                ['ssl:create',
                    'devcloud:devcloud2',
                    'dev',
                    'Test Certificate 2',
                    $sslCertificatesPath . '/cert.pem',
                    $sslCertificatesPath . '/key.pem',
                    $sslCertificatesPath . '/ca.pem',
                    '--activate'],
                '>  Installing new certificate Test Certificate 2 on Dev environment.' . PHP_EOL .
                '>  Activating certificate Test Certificate 2 on Dev environment.' . PHP_EOL
            ],
            [
                ['ssl:create',
                    'devcloud:devcloud2',
                    'dev',
                    'Test Certificate 2',
                    $sslCertificatesPath . '/cert.pem',
                    $sslCertificatesPath . '/key.pem'],
                '>  Installing new certificate Test Certificate 2 on Dev environment.' . PHP_EOL,
            ],
            [
                ['ssl:create',
                    'devcloud:devcloud2',
                    'dev',
                    'Test Certificate 2',
                    '/nopath/cert.pem',
                    $sslCertificatesPath . '/key.pem'],
                ' [error]  Cannot open certificate file at /nopath/cert.pem. ' . PHP_EOL,
            ],
            [
                ['ssl:create',
                    'devcloud:devcloud2',
                    'dev',
                    'Test Certificate 2',
                    $sslCertificatesPath . '/cert.pem',
                    '/nopath/key.pem'],
                ' [error]  Cannot open key file at /nopath/key.pem. ' . PHP_EOL,
            ],
            [
                ['ssl:create',
                    'devcloud:devcloud2',
                    'dev',
                    'Test Certificate 2',
                    $sslCertificatesPath . '/cert.pem',
                    $sslCertificatesPath . '/key.pem',
                    '/nopath/ca.pem'],
                ' [error]  Cannot open ca file at /nopath/ca.pem. ' . PHP_EOL,
            ]
        ];
    }
}
