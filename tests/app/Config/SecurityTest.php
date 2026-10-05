<?php declare(strict_types = 1);

namespace Config;

use CodeIgniter\Test\CIUnitTestCase;

class SecurityTest extends CIUnitTestCase
{
    public function testCsrfUsesSessionProtectionWithStrictDefaults(): void
    {
        $config = new Security();

        $this->assertSame('session', $config->csrfProtection);
        $this->assertTrue($config->tokenRandomize);
        $this->assertSame('csrf_token_essential', $config->tokenName);
        $this->assertSame('X-CSRF-TOKEN', $config->headerName);
        $this->assertSame('csrf_cookie_essential', $config->cookieName);
        $this->assertSame(7200, $config->expires);
        $this->assertTrue($config->regenerate);
        $this->assertSame(ENVIRONMENT === 'production', $config->redirect);
    }

    public function testEnvironmentValuesOverrideSecurityDefaultsAndPreserveTypes(): void
    {
        $keys = ['security.tokenRandomize', 'security.expires'];
        $original = [];

        foreach ($keys as $key):
            $original[$key] = [array_key_exists($key, $_ENV), $_ENV[$key] ?? null];
        endforeach;

        try {
            $_ENV['security.tokenRandomize'] = 'false';
            $_ENV['security.expires'] = '900';

            $config = new Security();

            $this->assertFalse($config->tokenRandomize);
            $this->assertSame(900, $config->expires);
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
