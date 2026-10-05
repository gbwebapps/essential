<?php declare(strict_types = 1);

namespace Config;

use CodeIgniter\Test\CIUnitTestCase;

class FiltersTest extends CIUnitTestCase
{
    public function testRequiredFrameworkFiltersRemainConfigured(): void
    {
        $config = new Filters();

        $this->assertSame(['forcehttps', 'pagecache'], $config->required['before']);
        $this->assertSame(['pagecache', 'performance', 'toolbar'], $config->required['after']);
    }

    public function testGlobalRequestProtectionIsConfigured(): void
    {
        $config = new Filters();

        $this->assertSame(['csrf', 'language'], $config->globals['before']);
        $this->assertSame([], $config->globals['after']);
        $this->assertArrayHasKey('csrf', $config->aliases);
        $this->assertArrayHasKey('language', $config->aliases);
    }

    public function testEveryFilterAliasReferencesAnExistingClass(): void
    {
        $config = new Filters();

        foreach ($config->aliases as $alias => $classes):
            foreach ((array) $classes as $class):
                $this->assertTrue(class_exists($class), $alias . ': ' . $class);
            endforeach;
        endforeach;
    }

    public function testNoMethodOrPatternFiltersAreConfiguredOutsideRoutes(): void
    {
        $config = new Filters();

        $this->assertSame([], $config->methods);
        $this->assertSame([], $config->filters);
    }
}
