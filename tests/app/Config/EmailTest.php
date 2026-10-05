<?php declare(strict_types = 1);

namespace Config;

use CodeIgniter\Test\CIUnitTestCase;

class EmailTest extends CIUnitTestCase
{
    public function testApplicationEmailConfigBridgesBackendConfig(): void
    {
        $config = new Email();

        $this->assertInstanceOf(\Config\Backend\Email::class, $config);
        $this->assertSame('smtp', $config->protocol);
        $this->assertSame('LOGIN', $config->SMTPAuthMethod);
        $this->assertSame(2525, $config->SMTPPort);
        $this->assertSame(5, $config->SMTPTimeout);
        $this->assertSame('tls', $config->SMTPCrypto);
        $this->assertSame('html', $config->mailType);
        $this->assertSame('UTF-8', $config->charset);
        $this->assertSame("\r\n", $config->CRLF);
        $this->assertSame("\r\n", $config->newline);
    }

    public function testEnvironmentValuesReachTheBridgeWithTheCorrectType(): void
    {
        $key = 'email.SMTPPort';
        $exists = array_key_exists($key, $_ENV);
        $original = $_ENV[$key] ?? null;

        try {
            $_ENV[$key] = '2465';

            $this->assertSame(2465, (new Email())->SMTPPort);
        } finally {
            if ($exists):
                $_ENV[$key] = $original;
            else:
                unset($_ENV[$key]);
            endif;
        }
    }
}
