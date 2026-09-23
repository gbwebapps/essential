<?php declare(strict_types = 1); 

namespace App\Cells;

/**
 * Gestisce la generazione dinamica dei pulsanti di interfaccia per le viste del pannello di controllo.
 */
class BackendButtonsCell
{
    /**
     * Renderizza il componente dei pulsanti in base al controller e all'azione corrente.
     *
     * @param string $controller Nome del controller di riferimento
     * @param string $action Azione corrente eseguita nel backend
     * @return string Restituisce l'HTML renderizzato della vista dei pulsanti o stringa vuota se l'azione non è ammessa
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
     * Restituisce la configurazione dei parametri e delle classi grafiche per i pulsanti in base all'azione richiesta.
     *
     * @param string $controller Nome del controller per la risoluzione delle stringhe localizzate
     * @param string $action Azione associata alla configurazione dei pulsanti
     * @return array Array associativo contenente gli attributi e i testi dei pulsanti
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