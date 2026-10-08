<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Libraries\MocksSettings;

class LogsClassTest extends CIUnitTestCase
{
    use MocksSettings;

    protected function setUp(): void
    {
        parent::setUp();
        Factories::reset();
        helper('settings');
        $this->mockSettings(['Backend\General' => ['language' => 'en']]);
    }

    protected function tearDown(): void
    {
        $this->resetSettingsMocks();
        parent::tearDown();
    }

    public function testGetJsIndexReturnsCorrectConfigurationWithLocale(): void
    {
        $modelMock = $this->createMock(\App\Models\Backend\LogsModel::class);
        $result = (new \App\Libraries\Backend\LogsClass($modelMock))->getJsIndex();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertSame('flatpickr-js', $result[0]['id']);
        $this->assertSame('default-js', $result[1]['id']);
        $this->assertSame('assets/vendor/flatpickr/js/default.js', $result[1]['path']);
    }

    public function testGetCssIndexReturnsCorrectConfiguration(): void
    {
        $modelMock = $this->createMock(\App\Models\Backend\LogsModel::class);
        $result = (new \App\Libraries\Backend\LogsClass($modelMock))->getCssIndex();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame('flatpickr-css', $result[0]['id']);
        $this->assertSame('assets/vendor/flatpickr/css/flatpickr.min.css', $result[0]['path']);
    }
}
