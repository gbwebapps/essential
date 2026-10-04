<?php declare(strict_types = 1);

namespace App\Helpers;

use App\Models\Backend\SettingsModel;
use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;

class SettingsHelperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        helper('settings');
    }

    protected function tearDown(): void
    {
        Factories::reset();

        parent::tearDown();
    }

    public function testSettingReturnsRequestedValue(): void
    {
        $model = $this->createMock(SettingsModel::class);

        $model->expects($this->once())
            ->method('getSettings')
            ->with('Backend\General')
            ->willReturn([
                'language' => 'it',
                'timezone' => 'Europe/Rome'
            ]);

        Factories::injectMock(
            'models',
            SettingsModel::class,
            $model
        );

        $result = setting('Backend\General', 'language');

        $this->assertSame('it', $result);
    }

    public function testSettingReturnsNullWhenRequestedKeyDoesNotExist(): void
    {
        $model = $this->createMock(SettingsModel::class);

        $model->expects($this->once())
            ->method('getSettings')
            ->with('Backend\General')
            ->willReturn([
                'language' => 'it'
            ]);

        Factories::injectMock(
            'models',
            SettingsModel::class,
            $model
        );

        $result = setting('Backend\General', 'missing');

        $this->assertNull($result);
    }

    public function testSettingReturnsSettingsAsObjectWhenKeyIsNull(): void
    {
        $model = $this->createMock(SettingsModel::class);

        $model->expects($this->once())
            ->method('getSettings')
            ->with('Backend\General')
            ->willReturn([
                'language' => 'it',
                'timezone' => 'Europe/Rome'
            ]);

        Factories::injectMock(
            'models',
            SettingsModel::class,
            $model
        );

        $result = setting('Backend\General');

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame('it', $result->language);
        $this->assertSame('Europe/Rome', $result->timezone);
    }
}