<?php declare(strict_types = 1);

namespace Config\Backend;

use CodeIgniter\Test\CIUnitTestCase;

use Config\Backend\Menu;

class MenuTest extends CIUnitTestCase
{
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
}