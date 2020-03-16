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
                ['ssl:info', 'devcloud:devcloud2', 'dev', 1234],
                $infoResponse . PHP_EOL,
            ]
        ];
    }
}
