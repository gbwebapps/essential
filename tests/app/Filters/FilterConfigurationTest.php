<?php declare(strict_types = 1);

namespace App\Filters;

use App\Filters\Backend\AuthorizationFilter;
use App\Filters\Backend\GuestFilter;
use App\Filters\Backend\LanguageFilter;
use App\Filters\Backend\PermissionFilter;
use App\Filters\Backend\SuperAdminFilter;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;

class FilterConfigurationTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        Services::reset();

        parent::tearDown();
    }

    public function testApplicationFilterAliasesPointToExpectedClasses(): void
    {
        $filters = new \Config\Filters();

        $this->assertSame(LanguageFilter::class, $filters->aliases['language']);
        $this->assertSame(AuthorizationFilter::class, $filters->aliases['authorization']);
        $this->assertSame(GuestFilter::class, $filters->aliases['guest']);
        $this->assertSame(SuperAdminFilter::class, $filters->aliases['superadmin']);
        $this->assertSame(PermissionFilter::class, $filters->aliases['permission']);
        $this->assertContains('language', $filters->globals['before']);
    }

    public function testBrandsRoutesUseBrandsPermissions(): void
    {
        $options = $this->getGetRouteOptions();

        $this->assertContains('permission:brands_show_all', $options['backend/brands']['filter']);
        $this->assertContains('permission:brands_show_all', $options['backend/brands/showAll']['filter']);

        $showRoute = null;

        foreach (array_keys($options) as $route):
            if (str_starts_with($route, 'backend/brands/show/')):
                $showRoute = $route;
                break;
            endif;
        endforeach;

        $this->assertNotNull($showRoute);
        $this->assertContains('permission:brands_show', $options[$showRoute]['filter']);
    }

    public function testEveryPermissionUsedByGetRoutesExistsInPermissionCatalog(): void
    {
        $configuredPermissions = [];

        foreach ((new \Config\Backend\Permissions())->getPermissions() as $group):
            $configuredPermissions = array_merge($configuredPermissions, array_keys($group['perms']));
        endforeach;

        $routePermissions = [];

        foreach ($this->getGetRouteOptions() as $routeOptions):
            foreach ($routeOptions['filter'] ?? [] as $filter):
                if (str_starts_with($filter, 'permission:')):
                    $routePermissions[] = substr($filter, strlen('permission:'));
                endif;
            endforeach;
        endforeach;

        $this->assertNotEmpty($routePermissions);

        foreach ($routePermissions as $permission):
            $this->assertContains($permission, $configuredPermissions);
        endforeach;
    }

    private function getGetRouteOptions(): array
    {
        $routes = Services::routes(false);
        $routes->loadRoutes();

        return $routes->getRoutesOptions(null, 'GET');
    }
}
