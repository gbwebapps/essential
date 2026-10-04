<?php declare(strict_types = 1);

namespace Config\Backend;

use CodeIgniter\Test\CIUnitTestCase;

use Config\Backend\Assets;

class AssetsTest extends CIUnitTestCase
{
    public function testGetCoreCssReturnsExpectedAssets(): void
    {
        $result = Assets::getCoreCss();

        $this->assertSame([
            ['id' => 'bootstrap-css','path' => 'assets/vendor/bootstrap/css/bootstrap.min.css'],
            ['id' => 'fontawesome','path' => 'assets/vendor/fontawesome/css/all.min.css'],
            ['id' => 'backend-css','path' => 'assets/css/backend/backend.css'],
        ], $result);
    }

    public function testGetCoreJsReturnsOnlyCoreAssetWhenControllerIsNull(): void
    {
        $result = Assets::getCoreJs();

        $this->assertSame([
            ['id' => 'bootstrap-js', 'path' => 'assets/vendor/bootstrap/js/bootstrap.bundle.min.js', 'isModule' => false],
        ], $result);
    }

    public function testGetCoreJsAddsControllerAsset(): void
    {
        $result = Assets::getCoreJs('users');

        $this->assertSame([
            ['id' => 'bootstrap-js', 'path' => 'assets/vendor/bootstrap/js/bootstrap.bundle.min.js', 'isModule' => false],
            ['id' => 'users-js', 'path' => 'assets/js/backend/users.js', 'isModule' => true],
        ], $result);
    }
}