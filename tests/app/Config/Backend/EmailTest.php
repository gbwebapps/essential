<?php declare(strict_types = 1);

namespace Config\Backend;

use CodeIgniter\Test\CIUnitTestCase;

use Config\Backend\Email;

class EmailTest extends CIUnitTestCase
{
    public function testConfigurationCanBeInstantiated(): void
    {
        $config = new Email();

        $this->assertInstanceOf(Email::class, $config);
        $this->assertSame('essential@essential.com', $config->fromEmail);
        $this->assertSame('Essential', $config->fromName);
        $this->assertSame('', $config->recipients);
        $this->assertSame('CodeIgniter', $config->userAgent);
        $this->assertSame('smtp', $config->protocol);
        $this->assertSame('/usr/sbin/sendmail', $config->mailPath);
        $this->assertSame('sandbox.smtp.mailtrap.io', $config->SMTPHost);
        $this->assertSame('LOGIN', $config->SMTPAuthMethod);
        $this->assertSame(2525, $config->SMTPPort);
        $this->assertSame(5, $config->SMTPTimeout);
        $this->assertFalse($config->SMTPKeepAlive);
        $this->assertSame('tls', $config->SMTPCrypto);
        $this->assertTrue($config->wordWrap);
        $this->assertSame(76, $config->wrapChars);
        $this->assertSame('html', $config->mailType);
        $this->assertSame('UTF-8', $config->charset);
        $this->assertFalse($config->validate);
        $this->assertSame(3, $config->priority);
        $this->assertSame("\r\n", $config->CRLF);
        $this->assertSame("\r\n", $config->newline);
        $this->assertFalse($config->BCCBatchMode);
        $this->assertSame(200, $config->BCCBatchSize);
        $this->assertFalse($config->DSN);
    }

    public function testTransportConfigurationUsesSupportedValues(): void
    {
        $config = new Email();

        $this->assertContains($config->protocol, ['mail', 'sendmail', 'smtp']);
        $this->assertContains($config->SMTPCrypto, ['', 'tls', 'ssl']);
        $this->assertGreaterThan(0, $config->SMTPPort);
        $this->assertLessThanOrEqual(65535, $config->SMTPPort);
        $this->assertGreaterThan(0, $config->SMTPTimeout);
        $this->assertGreaterThanOrEqual(1, $config->priority);
        $this->assertLessThanOrEqual(5, $config->priority);
    }
}
