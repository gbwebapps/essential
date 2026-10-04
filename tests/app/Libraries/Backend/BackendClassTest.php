<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class BackendClassTest extends CIUnitTestCase
{
	public function testGetOrderedAssetsReturnsCoreWhenCustomIsEmpty()
    {
        $core
            =
            [
                [
                    'id'
                    =>
                    'core-js',
                    'path'
                    =>
                    'core.js'
                ]
            ];

        $custom
            =
            [];

        $classClass
            =
            \App\Libraries\Backend\BackendClass::class;

        $reflection
            =
            new \ReflectionClass(
                $classClass
            );

        $instance
            =
            $reflection
            ->newInstanceWithoutConstructor();

        $result
            =
            $instance
            ->getOrderedAssets(
                $core,
                $custom
            );

        $this
            ->assertSame(
                $core,
                $result
            );
    }

    public function testGetOrderedAssetsInsertsBeforeTarget()
    {
        $core
            =
            [
                [
                    'id'
                    =>
                    'target-js',
                    'path'
                    =>
                    'target.js'
                ]
            ];

        $custom
            =
            [
                [
                    'id'
                    =>
                    'custom-js',
                    'path'
                    =>
                    'custom.js',
                    'position'
                    =>
                    'before',
                    'target'
                    =>
                    'target-js'
                ]
            ];

        $classClass
            =
            \App\Libraries\Backend\BackendClass::class;

        $reflection
            =
            new \ReflectionClass(
                $classClass
            );

        $instance
            =
            $reflection
            ->newInstanceWithoutConstructor();

        $result
            =
            $instance
            ->getOrderedAssets(
                $core,
                $custom
            );

        $this
            ->assertSame(
                'custom-js',
                $result[0]['id']
            );

        $this
            ->assertSame(
                'target-js',
                $result[1]['id']
            );
    }

    public function testGetOrderedAssetsInsertsAfterTarget()
    {
        $core
            =
            [
                [
                    'id'
                    =>
                    'target-js',
                    'path'
                    =>
                    'target.js'
                ]
            ];

        $custom
            =
            [
                [
                    'id'
                    =>
                    'custom-js',
                    'path'
                    =>
                    'custom.js',
                    'position'
                    =>
                    'after',
                    'target'
                    =>
                    'target-js'
                ]
            ];

        $classClass
            =
            \App\Libraries\Backend\BackendClass::class;

        $reflection
            =
            new \ReflectionClass(
                $classClass
            );

        $instance
            =
            $reflection
            ->newInstanceWithoutConstructor();

        $result
            =
            $instance
            ->getOrderedAssets(
                $core,
                $custom
            );

        $this
            ->assertSame(
                'target-js',
                $result[0]['id']
            );

        $this
            ->assertSame(
                'custom-js',
                $result[1]['id']
            );
    }

    public function testGetOrderedAssetsAppendsWhenTargetNotFound()
    {
        $core
            =
            [
                [
                    'id'
                    =>
                    'core-js',
                    'path'
                    =>
                    'core.js'
                ]
            ];

        $custom
            =
            [
                [
                    'id'
                    =>
                    'custom-js',
                    'path'
                    =>
                    'custom.js',
                    'position'
                    =>
                    'after',
                    'target'
                    =>
                    'non-existent'
                ]
            ];

        $classClass
            =
            \App\Libraries\Backend\BackendClass::class;

        $reflection
            =
            new \ReflectionClass(
                $classClass
            );

        $instance
            =
            $reflection
            ->newInstanceWithoutConstructor();

        $result
            =
            $instance
            ->getOrderedAssets(
                $core,
                $custom
            );

        $this
            ->assertSame(
                'core-js',
                $result[0]['id']
            );

        $this
            ->assertSame(
                'custom-js',
                $result[1]['id']
            );
    }
}