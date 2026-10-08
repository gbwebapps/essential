<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Libraries\Backend\TokensClass;
use App\Models\Backend\TokensModel;
use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Libraries\MocksSettings;

class TokensClassTest extends CIUnitTestCase
{
    use MocksSettings;

    protected function setUp(): void
    {
        parent::setUp();
        Factories::reset();
        helper('settings');
        $this->mockSettings(['Backend\General' => ['language' => 'it']]);
    }

    protected function tearDown(): void
    {
        $this->resetSettingsMocks();
        parent::tearDown();
    }

    public function testGetJsIndexReturnsExpectedArray(): void
    {
        $tokensModel = $this->createMock(TokensModel::class);
        $tokenClass = new TokensClass($tokensModel);
        $expected = [
            ['id' => 'flatpickr-js', 'path' => 'assets/vendor/flatpickr/js/flatpickr.min.js', 'position' => 'before', 'target' => 'tokens-js'],
            ['id' => 'it-js', 'path' => 'assets/vendor/flatpickr/js/it.js', 'position' => 'after', 'target' => 'flatpickr-js'],
        ];

        $this->assertSame($expected, $tokenClass->getJsIndex());
    }

    public function testGetCssIndexReturnsExpectedArray(): void
    {
        $tokensModel = $this->createMock(TokensModel::class);
        $tokenClass = new TokensClass($tokensModel);
        $expected = [
            ['id' => 'flatpickr-css', 'path' => 'assets/vendor/flatpickr/css/flatpickr.min.css', 'position' => 'before', 'target' => 'backend-css'],
        ];

        $this->assertSame($expected, $tokenClass->getCssIndex());
    }
}
