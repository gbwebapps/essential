<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use CodeIgniter\Test\CIUnitTestCase;

class MessagesClassTest extends CIUnitTestCase
{
    public function testGetLinksBarIndexReturnsExpectedArray(): void
    {
        $modelMock = $this->createMock(\App\Models\Backend\MessagesModel::class);
        $messagesClass = new \App\Libraries\Backend\MessagesClass(new $modelMock);

        $expected = [
            ['icon' => '<i class="fa-solid fa-list"></i>', 'label' => lang('backend/messages.linksBar.showAll'), 'route' => 'backend/messages/showAll'],
        ];

        $this->assertSame($expected, $messagesClass->getLinksBarIndex());
    }

    public function testGetLinksBarShowAllReturnsExpectedArray(): void
    {
        $modelMock = $this->createMock(\App\Models\Backend\MessagesModel::class);
        $messagesClass = new \App\Libraries\Backend\MessagesClass(new $modelMock);

        $expected = [
            ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => lang('backend/messages.linksBar.index'), 'route' => 'backend/messages'],
        ];

        $this->assertSame($expected, $messagesClass->getLinksBarShowAll());
    }

    public function testGetLinksBarShowReturnsExpectedArray(): void
    {
        $modelMock = $this->createMock(\App\Models\Backend\MessagesModel::class);
        $messagesClass = new \App\Libraries\Backend\MessagesClass(new $modelMock);

        $expected = [
            ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => lang('backend/messages.linksBar.index'), 'route' => 'backend/messages'],
            ['icon' => '<i class="fa-solid fa-list"></i>', 'label' => lang('backend/messages.linksBar.showAll'), 'route' => 'backend/messages/showAll'],
        ];

        $this->assertSame($expected, $messagesClass->getLinksBarShow());
    }
}