<?php declare(strict_types = 1);

namespace Config;

use CodeIgniter\Test\CIUnitTestCase;
use ReflectionClass;

class AppTest extends CIUnitTestCase
{
    public function testProjectDefaultsAreConfigured(): void
    {
        $defaults = (new ReflectionClass(App::class))->getDefaultProperties();

        $this->assertSame('http://localhost:8080/', $defaults['baseURL']);
        $this->assertSame('', $defaults['indexPage']);
        $this->assertSame('REQUEST_URI', $defaults['uriProtocol']);
        $this->assertSame('a-z 0-9~%.:_\-', $defaults['permittedURIChars']);
        $this->assertSame('en', $defaults['defaultLocale']);
        $this->assertFalse($defaults['negotiateLocale']);
        $this->assertSame(['en', 'it', 'es', 'fr', 'de', 'zh'], $defaults['supportedLocales']);
        $this->assertSame('Europe/Rome', $defaults['appTimezone']);
        $this->assertSame('UTF-8', $defaults['charset']);
        $this->assertFalse($defaults['CSPEnabled']);
        $this->assertContains($defaults['defaultLocale'], $defaults['supportedLocales']);
    }

    public function testSupportedLocalesMatchApplicationLanguageDirectories(): void
    {
        $defaults = (new ReflectionClass(App::class))->getDefaultProperties();
        $directories = array_map(
            static fn(string $path): string => basename($path),
            glob(APPPATH . 'Language/*', GLOB_ONLYDIR) ?: []
        );

        sort($directories);
        sort($defaults['supportedLocales']);

        $this->assertSame($directories, $defaults['supportedLocales']);
    }

    public function testEnvironmentValuesOverrideDefaultsAndPreserveTypes(): void
    {
        $keys = ['app.baseURL', 'app.forceGlobalSecureRequests'];
        $original = [];

        foreach ($keys as $key):
            $original[$key] = [array_key_exists($key, $_ENV), $_ENV[$key] ?? null];
        endforeach;

        try {
            $_ENV['app.baseURL'] = 'https://config-test.example/';
            $_ENV['app.forceGlobalSecureRequests'] = 'true';

            $config = new App();

            $this->assertSame('https://config-test.example/', $config->baseURL);
            $this->assertTrue($config->forceGlobalSecureRequests);
        } finally {
            foreach ($original as $key => [$exists, $value]):
                if ($exists):
                    $_ENV[$key] = $value;
                else:
                    unset($_ENV[$key]);
                endif;
            endforeach;
        }
    }
}
