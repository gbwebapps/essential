<?php declare(strict_types = 1);

namespace Config;

use CodeIgniter\Session\Handlers\FileHandler;
use CodeIgniter\Test\CIUnitTestCase;
use ReflectionClass;

class SessionTest extends CIUnitTestCase
{
    public function testFileDefaultsAreValidWithoutEnvironmentOverrides(): void
    {
        $defaults = (new ReflectionClass(Session::class))->getDefaultProperties();

        $this->assertSame(FileHandler::class, $defaults['driver']);
        $this->assertSame('ci_session', $defaults['cookieName']);
        $this->assertSame(7200, $defaults['expiration']);
        $this->assertSame(WRITEPATH . 'session', $defaults['savePath']);
        $this->assertFalse($defaults['matchIP']);
        $this->assertSame(300, $defaults['timeToUpdate']);
        $this->assertFalse($defaults['regenerateDestroy']);
        $this->assertNull($defaults['DBGroup']);
        $this->assertSame(100_000, $defaults['lockRetryInterval']);
        $this->assertSame(300, $defaults['lockMaxRetries']);
        $this->assertTrue(is_subclass_of($defaults['driver'], \CodeIgniter\Session\Handlers\BaseHandler::class));
    }

    public function testEnvironmentValuesOverrideSessionDefaultsAndPreserveTypes(): void
    {
        $keys = ['session.driver', 'session.savePath', 'session.expiration', 'session.matchIP'];
        $original = [];

        foreach ($keys as $key):
            $original[$key] = [array_key_exists($key, $_ENV), $_ENV[$key] ?? null];
        endforeach;

        try {
            $_ENV['session.driver'] = \CodeIgniter\Session\Handlers\DatabaseHandler::class;
            $_ENV['session.savePath'] = 'phpunit_sessions';
            $_ENV['session.expiration'] = '0';
            $_ENV['session.matchIP'] = 'true';

            $config = new Session();

            $this->assertSame(\CodeIgniter\Session\Handlers\DatabaseHandler::class, $config->driver);
            $this->assertSame('phpunit_sessions', $config->savePath);
            $this->assertSame(0, $config->expiration);
            $this->assertTrue($config->matchIP);
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

    public function testEffectiveSessionConfigurationIsCompatibleWithSelectedDriver(): void
    {
        $config = new Session();

        $this->assertTrue(is_subclass_of($config->driver, \CodeIgniter\Session\Handlers\BaseHandler::class));
        $this->assertGreaterThanOrEqual(0, $config->expiration);
        $this->assertNotSame('', trim($config->savePath));

        if ($config->driver === \CodeIgniter\Session\Handlers\DatabaseHandler::class):
            $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_]+$/', $config->savePath);
        elseif ($config->driver === FileHandler::class):
            $this->assertTrue(path_is_absolute($config->savePath));
        endif;
    }
}
