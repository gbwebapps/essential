<?php declare(strict_types = 1);

namespace Tests\App\Libraries\Backend;

use App\Libraries\Backend\InterfacePreferences;
use App\Libraries\Backend\InterfaceTranslations;
use App\Models\Backend\AuthModel;
use App\Models\Backend\SettingsModel;
use CodeIgniter\Config\Factories;
use CodeIgniter\Session\Session;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

class InterfacePreferencesTest extends CIUnitTestCase
{
    private array $values = [];

    protected function setUp(): void
    {
        parent::setUp();
        helper(['settings', 'interface']);
        $session = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->onlyMethods(['get', 'set', 'remove', 'has'])->getMock();
        $session->method('has')->willReturn(false);
        $session->method('get')->willReturnCallback(fn ($key) => $this->values[$key] ?? null);
        $session->method('set')->willReturnCallback(function ($key, $value): void { $this->values[$key] = $value; });
        $session->method('remove')->willReturnCallback(function ($key): void { unset($this->values[$key]); });
        Services::injectMock('session', $session);

        $model = $this->createMock(SettingsModel::class);
        $model->method('getSettings')->willReturn(['language' => 'en', 'theme' => 'grey']);
        $model->expects($this->never())->method('saveSettings');
        Factories::injectMock('models', SettingsModel::class, $model);
    }

    protected function tearDown(): void
    {
        Services::reset();
        Factories::reset();
        parent::tearDown();
    }

    public function testOverridesAreIndependentAndNeverSaveSettings(): void
    {
        $preferences = new InterfacePreferences();
        $this->assertSame('en', $preferences->language());
        $this->assertSame('grey', $preferences->theme());
        $preferences->set('language', 'zh');
        $preferences->set('theme', 'black');
        $this->assertSame('zh', $preferences->language());
        $this->assertSame('black', $preferences->theme());
        $preferences->clear();
        $this->assertSame('en', $preferences->language());
        $this->assertSame('grey', $preferences->theme());
    }

    public function testInvalidPreferenceDoesNotChangeTheSession(): void
    {
        $preferences = new InterfacePreferences();
        $preferences->set('theme', 'dark');
        try {
            $preferences->set('timezone', 'Europe/Rome');
            $this->fail('Unknown preferences must be rejected.');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame(['theme' => 'dark'], $this->values[InterfacePreferences::SESSION_KEY]);
        }
    }

    public function testAllSixCataloguesContainCoreAndModuleLabels(): void
    {
        $preferences = new InterfacePreferences();
        foreach (['en', 'it', 'es', 'fr', 'de', 'zh'] as $locale):
            $translations = $preferences->translations($locale);
            $this->assertArrayHasKey('backend/global.interface.themes', $translations);
            $this->assertArrayHasKey('backend/categories.labels.category', $translations);
            $this->assertNotSame('backend/categories.labels.category', $translations['backend/categories.labels.category']);
        endforeach;
    }

    public function testValidationMessageRetainsTheTranslatedField(): void
    {
        service('language')->setLocale('it');
        $label = lang('backend/categories.labels.category');
        $message = lang('Validation.required', ['field' => $label]);
        $bindings = InterfaceTranslations::messages(['errors' => ['category' => $message]]);
        $this->assertArrayHasKey($message, $bindings);
        $this->assertStringContainsString('Category', $bindings[$message]['variants']['en']);
        $this->assertStringNotContainsString('Categoria', $bindings[$message]['variants']['en']);
    }

    public function testTimeoutWithoutAnAuthenticationTokenStillClearsOverrides(): void
    {
        $this->values[InterfacePreferences::SESSION_KEY] = ['language' => 'de', 'theme' => 'dark'];
        $model = (new \ReflectionClass(AuthModel::class))->newInstanceWithoutConstructor();
        $model->logoutBySession('timeout');
        $this->assertArrayNotHasKey(InterfacePreferences::SESSION_KEY, $this->values);
    }

    public function testTemporaryLanguageIsConfinedToBackendPages(): void
    {
        $app = config('App');
        $originalBaseURL = $app->baseURL;
        $app->baseURL = 'https://example.test/essential/';
        $this->values[InterfacePreferences::SESSION_KEY] = ['language' => 'zh'];
        foreach (['/essential/backend/categories/add' => 'zh', '/essential/frontend' => 'en', '/essential/frontend/backend/page' => 'en', '/essential/backend/auth/login' => 'en'] as $path => $expected):
            $request = $this->createMock(\CodeIgniter\HTTP\IncomingRequest::class);
            $request->method('getPost')->willReturn(null);
            $request->method('getUri')->willReturn(new \CodeIgniter\HTTP\URI($path));
            $request->expects($this->atLeastOnce())->method('setLocale')->with($expected);
            Services::injectMock('request', $request);
            $filter = $this->getMockBuilder(\App\Filters\Backend\LanguageFilter::class)->onlyMethods(['getStoredLanguage'])->getMock();
            $filter->expects($expected === 'en' ? $this->once() : $this->never())->method('getStoredLanguage')->willReturn('en');
            $filter->before($request);
            $this->assertSame($expected, service('language')->getLocale());
        endforeach;
        $app->baseURL = $originalBaseURL;
    }
}
