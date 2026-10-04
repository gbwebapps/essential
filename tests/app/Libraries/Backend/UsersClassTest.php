<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use CodeIgniter\Test\CIUnitTestCase;

class UsersClassTest extends CIUnitTestCase
{
    public function testGetLinksBarIndexReturnsExpectedArray(): void
    {
        $modelMock = $this->createMock(\App\Models\Backend\UsersModel::class);
        $usersClass = new \App\Libraries\Backend\UsersClass(new $modelMock);

        $expected = [
            ['icon' => '<i class="fa-solid fa-list"></i>', 'label' => lang('backend/users.linksBar.showAll'), 'route' => 'backend/users/showAll'],
        ];

        $this->assertSame($expected, $usersClass->getLinksBarIndex());
    }

    public function testGetLinksBarShowAllReturnsExpectedArray(): void
    {
    	$modelMock = $this->createMock(\App\Models\Backend\UsersModel::class);
        $usersClass = new \App\Libraries\Backend\UsersClass(new $modelMock);

        $expected = [
            ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => lang('backend/users.linksBar.index'), 'route' => 'backend/users'],
        ];

        $this->assertSame($expected, $usersClass->getLinksBarShowAll());
    }

    public function testGetLinksBarShowReturnsExpectedArray(): void
    {
        $modelMock = $this->createMock(\App\Models\Backend\UsersModel::class);
        $usersClass = new \App\Libraries\Backend\UsersClass(new $modelMock);

        $expected = [
            ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => lang('backend/users.linksBar.index'), 'route' => 'backend/users'],
            ['icon' => '<i class="fa-solid fa-list"></i>', 'label' => lang('backend/users.linksBar.showAll'), 'route' => 'backend/users/showAll'],
        ];

        $this->assertSame($expected, $usersClass->getLinksBarShow());
    }
}