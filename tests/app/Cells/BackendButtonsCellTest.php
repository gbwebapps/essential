<?php declare(strict_types = 1);

namespace App\Cells;

use App\Cells\BackendButtonsCell;

use CodeIgniter\Test\CIUnitTestCase;

class BackendButtonsCellTest extends CIUnitTestCase
{
    public function testRenderReturnsEmptyStringForInvalidAction(): void
    {
        $cell = new BackendButtonsCell();

        $result = $cell->render('users', 'invalid');

        $this->assertSame('', $result);
    }

    public function testRenderReturnsHtmlForValidAction(): void
    {
        $cell = new BackendButtonsCell();

        $result = $cell->render('users', 'add');

        $this->assertIsString($result);
        $this->assertNotSame('', $result);
    }

    public function testGetButtonConfigReturnsAddConfiguration(): void
    {
        $cell = new BackendButtonsCell();

        $result = $this->invokeGetButtonConfig($cell, 'users', 'add');

        $this->assertSame('add-reset', $result['id_output']);
        $this->assertSame('<i class="fa-solid fa-refresh"></i>', $result['icon_left']);
        $this->assertSame('btn btn-warning text-dark btn-sm', $result['btn_left']);
        $this->assertSame('<i class="fa-solid fa-floppy-disk"></i>', $result['icon_right']);
        $this->assertSame('btn btn-success btn-sm', $result['btn_right']);
    }

    public function testGetButtonConfigReturnsEditConfiguration(): void
    {
        $cell = new BackendButtonsCell();

        $result = $this->invokeGetButtonConfig($cell, 'users', 'edit');

        $this->assertSame('edit-refresh', $result['id_output']);
        $this->assertSame('<i class="fa-solid fa-refresh"></i>', $result['icon_left']);
        $this->assertSame('btn btn-warning text-dark btn-sm', $result['btn_left']);
        $this->assertSame('<i class="fa-solid fa-floppy-disk"></i>', $result['icon_right']);
        $this->assertSame('btn btn-success btn-sm', $result['btn_right']);
    }

    public function testGetButtonConfigReturnsShowConfiguration(): void
    {
        $cell = new BackendButtonsCell();

        $result = $this->invokeGetButtonConfig($cell, 'users', 'show');

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
        $this->assertSame('btn btn-warning text-dark btn-sm', $result['btn_left']);
        $this->assertSame('<i class="fa-solid fa-refresh"></i>', $result['icon_left']);
        $this->assertSame('btn btn-success btn-sm', $result['btn_right']);
        $this->assertSame('<i class="fa-solid fa-floppy-disk"></i>', $result['icon_right']);
    }

    private function invokeGetButtonConfig(BackendButtonsCell $cell, string $controller, string $action): array
    {
        $reflection = new \ReflectionMethod($cell, 'getButtonConfig');
        $reflection->setAccessible(true);

        return $reflection->invoke($cell, $controller, $action);
    }
}