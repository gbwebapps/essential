<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class SettingsClassTest extends CIUnitTestCase
{
	public function testGetJsIndexReturnsCorrectConfiguration()
    {
        $class
            =
            \App\Libraries\Backend\SettingsClass::class;

        $modelMock = $this->createMock(\App\Models\Backend\SettingsModel::class);

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
                1,
                $result
            );

        $this
            ->assertSame(
                'tom-select-js',
                $result[0]['id']
            );

        $this
            ->assertSame(
                'assets/vendor/tom-select/js/tom-select.complete.min.js',
                $result[0]['path']
            );
    }

    public function testGetCssIndexReturnsCorrectConfiguration()
    {
        $class
            =
            \App\Libraries\Backend\SettingsClass::class;

        $modelMock = $this->createMock(\App\Models\Backend\SettingsModel::class);

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
                'tom-select-css',
                $result[0]['id']
            );

        $this
            ->assertSame(
                'assets/vendor/tom-select/css/tom-select.bootstrap5.min.css',
                $result[0]['path']
            );
    }

    public function testGetTimezonesReturnsValidList()
    {
        $class
            =
            \App\Libraries\Backend\SettingsClass::class;

        $modelMock = $this->createMock(\App\Models\Backend\SettingsModel::class);

        $instance
            =
            new $class(new $modelMock);

        $result
            =
            $instance
            ->getTimezones();

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertContains(
                'Europe/Rome',
                $result
            );

        $this
            ->assertSame(
                timezone_identifiers_list(),
                $result
            );
    }

    public function testGetLanguagesReturnsExpectedArray()
    {
        $class
            =
            \App\Libraries\Backend\SettingsClass::class;

        $modelMock = $this->createMock(\App\Models\Backend\SettingsModel::class);

        $instance
            =
            new $class(new $modelMock);

        $result
            =
            $instance
            ->getLanguages();

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertArrayHasKey(
                'it',
                $result
            );

        $this
            ->assertArrayHasKey(
                'en',
                $result
            );

        $this
            ->assertSame(
                ['it', 'en', 'es', 'fr', 'de', 'zh'],
                array_keys($result)
            );
    }

    public function testGetDateFormatsReturnsExpectedArray()
    {
        $class
            =
            \App\Libraries\Backend\SettingsClass::class;

        $modelMock = $this->createMock(\App\Models\Backend\SettingsModel::class);

        $instance
            =
            new $class(new $modelMock);

        $result
            =
            $instance
            ->getDateFormats();

        $this
            ->assertIsArray(
                $result
            );

        $this
            ->assertArrayHasKey(
                'yyyy-MM-dd HH:mm:ss',
                $result
            );

        $this
            ->assertArrayHasKey(
                'dd/MM/yyyy HH:mm',
                $result
            );

        $this
            ->assertSame(
                [
                    'd MMMM yyyy HH:mm:ss',
                    'dd/MM/yyyy HH:mm',
                    'MMMM d yyyy h:mm:ss a',
                    'MM/dd/yyyy h:mm a',
                    'yyyy-MM-dd HH:mm:ss'
                ],
                array_keys($result)
            );
    }
}
