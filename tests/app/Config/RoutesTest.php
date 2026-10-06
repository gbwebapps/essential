<?php declare(strict_types = 1);

namespace Config;

use CodeIgniter\Router\RouteCollection;
use CodeIgniter\Test\CIUnitTestCase;

class RoutesTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        Services::reset();

        parent::tearDown();
    }

    public function testCustomRoutePlaceholdersAreStrict(): void
    {
        $placeholders = $this->routes()->getPlaceholders();

        $this->assertSame('[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}', $placeholders['uuid']);
        $this->assertSame('[0-9a-f]{32}', $placeholders['token']);
    }

    public function testPublicHomeRoutePointsToExistingFrontendController(): void
    {
        [$routes, $options] = $this->routeTable('GET');

        $this->assertArrayHasKey('/', $routes);
        $this->assertSame('\App\Controllers\Frontend\HomeController::index', $routes['/']);
        $this->assertSame([], $options['/'] ?? []);
    }

    public function testBackendRoutesRequireAuthenticationExceptGuestEndpoints(): void
    {
        foreach (['GET', 'POST'] as $method):
            [$routes, $options] = $this->routeTable($method);

            foreach ($routes as $route => $handler):
                if ( ! str_starts_with($route, 'backend') || $route === 'backend'):
                    continue;
                endif;

                $filters = $options[$route]['filter'] ?? [];

                $isAuthRoute = $route === 'backend/auth' || str_starts_with($route, 'backend/auth/');

                if ($isAuthRoute && $route !== 'backend/auth/logout'):
                    $this->assertContains('guest', $filters, $method . ' ' . $route);
                else:
                    $this->assertContains('authorization', $filters, $method . ' ' . $route);
                endif;
            endforeach;
        endforeach;
    }

    public function testAdministrativeRoutesRequireSuperAdmin(): void
    {
        $prefixes = [
            'backend/settings',
            'backend/tools',
            'backend/groups',
            'backend/audits',
            'backend/tokens',
            'backend/logs',
			'backend/admins',
			'backend/export',
			'backend/import',
        ];

        foreach (['GET', 'POST'] as $method):
            [$routes, $options] = $this->routeTable($method);

            foreach ($routes as $route => $handler):
                foreach ($prefixes as $prefix):
                    if ($route === $prefix || str_starts_with($route, $prefix . '/')):
                        $this->assertContains('superadmin', $options[$route]['filter'] ?? [], $method . ' ' . $route);
                    endif;
                endforeach;
            endforeach;
        endforeach;
    }

    public function testStateChangingRoutesAreNotExposedThroughGet(): void
    {
        [$getRoutes] = $this->routeTable('GET');
        [$postRoutes] = $this->routeTable('POST');
        $postOnlyRoutes = [
            'backend/account/deleteToken',
            'backend/settings/saveSettings',
            'backend/settings/deleteSettings',
            'backend/tokens/hardDelete',
            'backend/logs/hardDelete',
            'backend/admins/hardDelete',
            'backend/admins/softDelete',
            'backend/admins/restoreDelete',
            'backend/admins/changeStatus',
        ];

        foreach ($postOnlyRoutes as $route):
            $this->assertArrayHasKey($route, $postRoutes);
            $this->assertArrayNotHasKey($route, $getRoutes);
        endforeach;
    }

    public function testEveryControllerRouteReferencesAnExistingAction(): void
    {
        $invalidRoutes = [];

        foreach (['GET', 'POST'] as $method):
            [$routes] = $this->routeTable($method);

            foreach ($routes as $route => $handler):
                if ( ! is_string($handler) || ! str_contains($handler, '::')):
                    continue;
                endif;

                [$class, $action] = explode('::', $handler, 2);
                $action = explode('/', $action, 2)[0];

                if ( ! class_exists($class) || ! method_exists($class, $action)):
                    $invalidRoutes[] = $method . ' ' . $route . ' => ' . $handler;
                endif;
            endforeach;
        endforeach;

        $this->assertSame([], $invalidRoutes);
    }

    private function routes(): RouteCollection
    {
        $routes = Services::routes(false);
        $routes->loadRoutes();

        return $routes;
    }

    private function routeTable(string $method): array
    {
        $routes = $this->routes();

        return [
            $routes->getRoutes($method),
            $routes->getRoutesOptions(null, $method),
        ];
    }
}
