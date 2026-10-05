<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;
use Tests\Support\Libraries\MocksSettings;

class LogsClassTest extends CIUnitTestCase
{
	use MocksSettings;

	protected function setUp(): void
    {
        parent::setUp();
        $this->mockSettings(['Backend\General' => ['language' => 'en']]);
    }

	protected function tearDown(): void
    {
        $this->resetSettingsMocks();
        parent::tearDown();
    }

	public function testGetJsIndexReturnsCorrectConfigurationWithLocale()
    {
        $modelMock = $this->createMock(\App\Models\Backend\LogsModel::class);

        $result = (new \App\Libraries\Backend\LogsClass(new $modelMock))->getJsIndex();

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertCount(
                2,
                $result
            );

        $this
            ->assertSame(
                'flatpickr-js',
                $result[0]['id']
            );

        $this
            ->assertSame(
                'en-js',
                $result[1]['id']
            );

        $this
            ->assertSame(
                'assets/vendor/flatpickr/js/en.js',
                $result[1]['path']
            );
    }

    public function testGetCssIndexReturnsCorrectConfiguration()
    {
        $modelMock = $this->createMock(\App\Models\Backend\LogsModel::class);

        $result = (new \App\Libraries\Backend\LogsClass(new $modelMock))->getJsIndex();

        $result
            =
            (
                new \App\Libraries\Backend\LogsClass(new $modelMock)
            )
            ->getCssIndex();

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertCount(
                1,
                $result
            );

        $this
            ->assertSame(
                'flatpickr-css',
                $result[0]['id']
            );

        $this
            ->assertSame(
                'assets/vendor/flatpickr/css/flatpickr.min.css',
                $result[0]['path']
            );
    }
}
