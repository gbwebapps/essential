<?php declare(strict_types = 1);

namespace Config\Backend;

use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;

use Config\Backend\Menu;

class MenuTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        Services::reset();

        parent::tearDown();
    }

    public function testConstructorBuildsTopRightMenu(): void
    {
        $config = new Menu();

        $this->assertCount(7, $config->topRight);
        $this->assertSame('backend/admins/showAll', $config->topRight[0]['route']);
        $this->assertSame('admins', $config->topRight[0]['controller']);
        $this->assertSame('backend/auth/logout', $config->topRight[6]['route']);
    }

    public function testConstructorBuildsBottomLeftMenu(): void
    {
        $config = new Menu();

        $this->assertCount(3, $config->bottomLeft);
        $this->assertSame('backend/dashboard', $config->bottomLeft[0]['route']);
        $this->assertSame('backend/users/showAll', $config->bottomLeft[1]['route']);
        $this->assertSame('backend/messages/showAll', $config->bottomLeft[2]['route']);
    }

    public function testConstructorBuildsBottomRightMenu(): void
    {
        $config = new Menu();

        $this->assertCount(2, $config->bottomRight);
        $this->assertSame('backend/tools', $config->bottomRight[0]['route']);
        $this->assertSame('backend/settings', $config->bottomRight[1]['route']);
    }

    public function testEveryMenuItemHasACompleteUniqueAndRoutableDefinition(): void
    {
        $config = new Menu();
        $items = array_merge($config->topRight, $config->bottomLeft, $config->bottomRight);
        $routes = Services::routes(false);
        $routes->loadRoutes();
        $getRoutes = $routes->getRoutes('GET');
        $configuredRoutes = [];
        $controllers = [];

        foreach ($items as $item):
            $this->assertSame(['label', 'route', 'icon', 'controller'], array_keys($item));
            $this->assertNotSame('', $item['label']);
            $this->assertNotSame('', $item['icon']);
            $this->assertArrayHasKey($item['route'], $getRoutes, $item['route']);

            $configuredRoutes[] = $item['route'];
            $controllers[] = $item['controller'];
        endforeach;

        $this->assertSame($configuredRoutes, array_values(array_unique($configuredRoutes)));
        $this->assertSame($controllers, array_values(array_unique($controllers)));
    }
}
