<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class AuditsClassTest extends CIUnitTestCase
{
	public function testGetJsIndexReturnsCorrectConfigurationWithLocale()
    {
        $generalConfig
            =
            new \stdClass();

        $generalConfig
            ->language
            =
            'it';

        if (
            ! function_exists(
                'setting'
            )
        ):
            function setting(
                $key
            ) {
                $fallbackConf
                    =
                    new \stdClass();

                $fallbackConf
                    ->language
                    =
                    'it';

                return $fallbackConf;
            }
        endif;

        if (
            class_exists(
                '\CodeIgniter\Config\Factories'
            )
        ):
            \CodeIgniter\Config\Factories::injectMock(
                'config',
                'Backend\General',
                $generalConfig
            );
        endif;

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