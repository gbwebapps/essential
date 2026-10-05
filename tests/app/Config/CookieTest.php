<?php declare(strict_types = 1);

namespace Config;

use CodeIgniter\Test\CIUnitTestCase;

class CookieTest extends CIUnitTestCase
{
    public function testSecurityRelevantDefaultsAreConfigured(): void
    {
        $config = new Cookie();

        $this->assertSame('', $config->prefix);
        $this->assertSame(0, $config->expires);
        $this->assertSame('/', $config->path);
        $this->assertSame('', $config->domain);
        $this->assertFalse($config->secure);
        $this->assertTrue($config->httponly);
        $this->assertSame('Lax', $config->samesite);
        $this->assertFalse($config->raw);
        $this->assertContains($config->samesite, ['', 'Lax', 'None', 'Strict']);
        $this->assertFalse($config->samesite === 'None' && ! $config->secure);
    }

    public function testEnvironmentValuesOverrideDefaultsAndPreserveTypes(): void
    {
        $keys = ['cookie.secure', 'cookie.expires'];
        $original = [];

        foreach ($keys as $key):
            $original[$key] = [array_key_exists($key, $_ENV), $_ENV[$key] ?? null];
        endforeach;

        try {
            $_ENV['cookie.secure'] = 'true';
            $_ENV['cookie.expires'] = '3600';

            $config = new Cookie();

            $this->assertTrue($config->secure);
            $this->assertSame(3600, $config->expires);
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
