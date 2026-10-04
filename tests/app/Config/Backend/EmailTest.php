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
        $this->assertSame('smtp', $config->protocol);
        $this->assertSame(2525, $config->SMTPPort);
        $this->assertSame('tls', $config->SMTPCrypto);
        $this->assertSame('html', $config->mailType);
        $this->assertSame('UTF-8', $config->charset);
    }
}