<?php declare(strict_types = 1); 

namespace App\Cells;

/**
 * 
 */
class BackendButtonsCell
{
    /**
     * @param  string
     * @param  string
     * @return [type]
     */
    public function render(string $controller, string $action): string
    {
        if ( ! in_array($action, ['add', 'edit', 'show', 'edit_account'])):
            return '';
        endif;

        /* Definiamo i parametri in base all'azione */
        $data = $this->getButtonConfig($controller, $action);
        $data['controller'] = $controller;
        $data['action'] = $action;

        return view('backend/cells/backendButtons', $data);
    }

    /**
     * @param  [type]
     * @param  [type]
     * @return [type]
     */
    private function getButtonConfig($controller, $action): array
    {
        switch($action):

            case 'add':
                return [
                    'id_output' => 'add-reset',
                    'text_left' => lang('backend/' . $controller . '.buttons.resetData'),
                    'icon_left' => '<i class="fa-solid fa-refresh"></i>',
                    'btn_left' => 'btn btn-warning text-dark btn-sm',
                    'message' => lang('backend/' . $controller . '.messages.areYouSureResetData'),
                    'text_right' => lang('backend/' . $controller . '.buttons.sendData'),
                    'icon_right' => '<i class="fa-solid fa-floppy-disk"></i>',
                    'btn_right' => 'btn btn-success btn-sm',
                ];

            case 'edit':
                return [
                    'id_output' => 'edit-refresh',
                    'text_left' => lang('backend/' . $controller . '.buttons.refreshData'),
                    'icon_left' => '<i class="fa-solid fa-refresh"></i>',
                    'btn_left' => 'btn btn-warning text-dark btn-sm',
                    'message' => lang('backend/' . $controller . '.messages.areYouSureRefreshData'),
                    'text_right' => lang('backend/' . $controller . '.buttons.sendData'),
                    'icon_right' => '<i class="fa-solid fa-floppy-disk"></i>',
                    'btn_right' => 'btn btn-success btn-sm',
                ];

            case 'show':
                return [
                    'id_left' => 'show-print-button',
                    'text_left' => lang('backend/' . $controller . '.buttons.print'),
                    'icon_left' => '<i class="fa-solid fa-print"></i>',
                    'btn_left' => 'btn btn-primary btn-sm',
                    'message_left' => '', 
                    'id_right' => 'show-export-button',
                    'text_right' => lang('backend/' . $controller . '.buttons.exportPdf'),
                    'icon_right' => '<i class="fa-solid fa-file-export"></i>',
                    'btn_right' => 'btn btn-primary btn-sm',
                    'message_right' => '', 
                ];

            case 'edit_account':
                return [
                    'id_output' => 'edit-refresh',
                    'text_left' => lang('backend/' . $controller . '.buttons.reloadData'),
                    'btn_left' => 'btn btn-warming text-dark btn-sm',
                    'icon_left' => '<i class="fa-solid fa-refresh"></i>',
                    'message' => lang('backend/' . $controller . '.messages.areYouSureRefreshData'),
                    'text_right' => lang('backend/' . $controller . '.buttons.sendData'),
                    'btn_right' => 'btn btn-success btn-sm',
                    'icon_right' => '<i class="fa-solid fa-floppy-disk"></i>',
                ];

        endswitch;
    }
}