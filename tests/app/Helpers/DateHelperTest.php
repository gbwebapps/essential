<?php declare(strict_types = 1);

namespace App\Helpers;

use App\Models\Backend\SettingsModel;
use CodeIgniter\Config\Factories;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use Tests\Support\Libraries\MocksSettings;

class DateHelperTest extends CIUnitTestCase
{
    use MocksSettings;

    protected function setUp(): void
    {
        parent::setUp();

        helper('date');
        helper('settings');

        $app = new App();
        $app->appTimezone = 'Europe/Rome';

        Factories::injectMock('config', App::class, $app);
        $this->mockSettings([
            'Backend\General' => [
                'timezone' => 'UTC',
                'language' => 'en',
                'dateFormat' => 'yyyy-MM-dd HH:mm:ss',
            ],
        ]);

        Services::request()->setLocale('en');
    }

    protected function tearDown(): void
    {
        Services::reset();
        $this->resetSettingsMocks();

        parent::tearDown();
    }

    public function testConvertDateReturnsEmptyStringForNull(): void
    {
        $this->assertSame('', convertDate(null));
    }

    public function testConvertDateReturnsEmptyStringForEmptyString(): void
    {
        $this->assertSame('', convertDate(''));
    }

    public function testConvertDateReturnsEmptyStringForWhitespace(): void
    {
        $this->assertSame('', convertDate('   '));
    }

    public function testConvertDateUsesExplicitFormat(): void
    {
        $result = convertDate(
            '2026-10-03 12:00:00',
            'yyyy-MM-dd HH:mm:ss'
        );

        $this->assertSame('2026-10-03 10:00:00', $result);
    }

    public function testConvertDateUsesConfiguredDefaultFormat(): void
    {
        $result = convertDate('2026-10-03 12:00:00');

        $this->assertSame('2026-10-03 10:00:00', $result);
    }

    public function testConvertDateUsesConfiguredLocale(): void
    {
        $result = convertDate(
            '2026-10-03 12:00:00',
            'EEEE d MMMM yyyy'
        );

        $this->assertSame('Saturday 3 October 2026', $result);
    }

    public function testConvertDateReturnsOriginalValueWhenParsingFails(): void
    {
        $result = convertDate(
            'invalid-date-value',
            'yyyy-MM-dd'
        );

        $this->assertSame('invalid-date-value', $result);
    }

    public function testConvertDateSupportsConversationalFormat(): void
    {
        $result = convertDate(
            '2026-10-03 12:00:00',
            'conversational'
        );

        $this->assertSame('Saturday 3 October 2026 at 10:00:00', $result);
    }

    public function testConvertDateUsesBuiltInFormatWhenConfiguredFormatIsMissing(): void
    {
        $this->mockSettings([
            'Backend\General' => [
                'timezone' => 'UTC',
                'language' => 'en',
            ],
        ]);

        $result = convertDate('2026-10-03 12:00:00');

        $this->assertSame('Saturday 3 October 2026 10:00:00', $result);
    }

    public function testConvertDateReturnsOriginalValueWhenSettingsFail(): void
    {
        $model = $this->createMock(SettingsModel::class);
        $model->method('getSettings')
            ->willThrowException(new \RuntimeException('Settings unavailable'));

        Factories::injectMock('models', SettingsModel::class, $model);

        $this->assertSame(
            '2026-10-03 12:00:00',
            convertDate('2026-10-03 12:00:00')
        );
    }
}
