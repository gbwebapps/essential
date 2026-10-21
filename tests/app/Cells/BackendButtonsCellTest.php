<?php declare(strict_types = 1);

namespace App\Cells;

use App\Cells\BackendButtonsCell;

use CodeIgniter\Test\CIUnitTestCase;

class BackendButtonsCellTest extends CIUnitTestCase
{
    public function testRenderReturnsEmptyStringForInvalidAction(): void
    {
        $cell = new BackendButtonsCell();

        $result = $cell->render('inventory_items', 'invalid');

        $this->assertSame('', $result);
    }

    public function testRenderReturnsHtmlForValidAction(): void
    {
        $cell = new BackendButtonsCell();

        $result = $cell->render('inventory_items', 'add');

        $this->assertStringContainsString('<form method="post" id="add-reset"', $result);
        $this->assertStringContainsString('class="btn&#x20;app-btn-warning&#x20;btn-sm"', $result);
        $this->assertStringContainsString('class="fa-solid fa-refresh"', $result);
        $this->assertStringContainsString('class="btn&#x20;app-btn-success&#x20;btn-sm"', $result);
        $this->assertStringContainsString('form="inventory_items-add"', $result);
        $this->assertStringContainsString('class="fa-solid fa-floppy-disk"', $result);
    }

    public function testRenderReturnsShowButtonsWithExpectedIdentifiers(): void
    {
        $result = (new BackendButtonsCell())->render('inventory_items', 'show');

        $this->assertStringContainsString('id="show-print-button"', $result);
        $this->assertStringContainsString('id="show-export-button"', $result);
        $this->assertStringNotContainsString('<form', $result);
    }

    public function testRenderEscapesControllerInFormAttribute(): void
    {
        $result = (new BackendButtonsCell())->render('inventory_items" onmouseover="alert(1)', 'add');

        $this->assertStringNotContainsString('form="inventory_items" onmouseover=', $result);
        $this->assertStringContainsString('form="inventory_items&quot;&#x20;onmouseover&#x3D;&quot;alert&#x28;1&#x29;-add"', $result);
    }

    public function testGetButtonConfigReturnsAddConfiguration(): void
    {
        $cell = new BackendButtonsCell();

        $result = $this->invokeGetButtonConfig($cell, 'inventory_items', 'add');

        $this->assertSame('add-reset', $result['id_output']);
        $this->assertSame('<i class="fa-solid fa-refresh"></i>', $result['icon_left']);
        $this->assertSame('btn app-btn-warning btn-sm', $result['btn_left']);
        $this->assertSame('<i class="fa-solid fa-floppy-disk"></i>', $result['icon_right']);
        $this->assertSame('btn app-btn-success btn-sm', $result['btn_right']);
    }

    public function testGetButtonConfigReturnsEditConfiguration(): void
    {
        $cell = new BackendButtonsCell();

        $result = $this->invokeGetButtonConfig($cell, 'inventory_items', 'edit');

        $this->assertSame('edit-refresh', $result['id_output']);
        $this->assertSame('<i class="fa-solid fa-refresh"></i>', $result['icon_left']);
        $this->assertSame('btn app-btn-warning btn-sm', $result['btn_left']);
        $this->assertSame('<i class="fa-solid fa-floppy-disk"></i>', $result['icon_right']);
        $this->assertSame('btn app-btn-success btn-sm', $result['btn_right']);
    }

    public function testGetButtonConfigReturnsShowConfiguration(): void
    {
        $cell = new BackendButtonsCell();

        $result = $this->invokeGetButtonConfig($cell, 'inventory_items', 'show');

        $this->assertSame('show-print-button', $result['id_left']);
        $this->assertSame('show-export-button', $result['id_right']);
        $this->assertSame('<i class="fa-solid fa-print"></i>', $result['icon_left']);
        $this->assertSame('<i class="fa-solid fa-file-export"></i>', $result['icon_right']);
        $this->assertSame('', $result['message_left']);
        $this->assertSame('', $result['message_right']);
    }

    public function testGetButtonConfigReturnsEditAccountConfiguration(): void
    {
        $cell = new BackendButtonsCell();

        $result = $this->invokeGetButtonConfig($cell, 'account', 'edit_account');

        $this->assertSame('edit-refresh', $result['id_output']);
        $this->assertSame('btn app-btn-warning btn-sm', $result['btn_left']);
        $this->assertSame('<i class="fa-solid fa-refresh"></i>', $result['icon_left']);
        $this->assertSame('btn app-btn-success btn-sm', $result['btn_right']);
        $this->assertSame('<i class="fa-solid fa-floppy-disk"></i>', $result['icon_right']);
    }

    private function invokeGetButtonConfig(BackendButtonsCell $cell, string $controller, string $action): array
    {
        $reflection = new \ReflectionMethod($cell, 'getButtonConfig');
        $reflection->setAccessible(true);

        return $reflection->invoke($cell, $controller, $action);
    }
}
