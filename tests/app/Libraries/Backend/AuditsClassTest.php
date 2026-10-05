<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;
use Tests\Support\Libraries\MocksSettings;

class AuditsClassTest extends CIUnitTestCase
{
	use MocksSettings;

	protected function setUp(): void
    {
        parent::setUp();
        $this->mockSettings(['Backend\General' => ['language' => 'it']]);
    }

	protected function tearDown(): void
    {
        $this->resetSettingsMocks();
        parent::tearDown();
    }

	public function testGetJsIndexReturnsCorrectConfigurationWithLocale()
    {
        $class
            =
            \App\Libraries\Backend\AuditsClass::class;

        $modelMock = $this->createMock(\App\Models\Backend\AuditsModel::class);

        $instance
            =
            new $class(new $modelMock);

        $result
            =
            $instance
            ->getJsIndex();

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
                'it-js',
                $result[1]['id']
            );

        $this
            ->assertSame(
                'assets/vendor/flatpickr/js/it.js',
                $result[1]['path']
            );
    }

    public function testGetCssIndexReturnsCorrectConfiguration()
    {
        $class
            =
            \App\Libraries\Backend\AuditsClass::class;

        $modelMock = $this->createMock(\App\Models\Backend\AuditsModel::class);

        $instance
            =
            new $class(new $modelMock);

        $result
            =
            $instance
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
