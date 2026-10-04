<?php declare(strict_types = 1);

namespace Config\Backend;

use CodeIgniter\Test\CIUnitTestCase;

use Config\Backend\General;

class GeneralTest extends CIUnitTestCase
{
    public function testConfigurationCanBeInstantiated(): void
    {
        $config = new General();

        $this->assertInstanceOf(General::class, $config);
        $this->assertSame('Europe/Rome', $config->timezone);
        $this->assertSame('en', $config->language);
        $this->assertSame('d MMMM yyyy HH:mm:ss', $config->dateFormat);
    }
}