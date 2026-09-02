<?php declare(strict_types = 1); 

namespace App\Cells;

/**
 * Class BackendButtonsCell
 *
 * View Cell dedicata alla generazione e alla configurazione dinamica dei pulsanti
 * di controllo (es. Salva, Ripristina, Stampa) all'interno dei form e dei moduli del Backend.
 */
class BackendButtonsCell
{
    /**
     * Elabora e restituisce il codice HTML dei pulsanti associati all'azione corrente.
     *
     * @param string $controller Nome del controller di riferimento per caricare le etichette del modulo.
     * @param string $action     L'azione in esecuzione (es. 'add', 'edit', 'show', 'edit_account').
     * @return string Il frammento HTML dei pulsanti renderizzati, oppure una stringa vuota se l'azione non è supportata.
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
     * Mappa e restituisce i parametri specifici (testi, icone, classi CSS e messaggi di conferma) per ciascun pulsante.
     *
     * @param string $controller Nome del controller per la gestione delle traduzioni dinamiche.
     * @param string $action     L'azione da cui ricavare il set di configurazione del pulsante.
     * @return array Matrice contenente le proprietà grafiche e i messaggi dei pulsanti sinistro e destro.
     */
    private function getButtonConfig($controller, $action): array
    {
        switch($action):

            case 'add':
                return [
                    'id_output' => 'add-reset',
                    'text_left' => lang('backend/' . $controller . '.buttons.resetData'),
                    'icon_left' => '<i class="fa-solid fa-refresh d-none d-sm-inline"></i>',
                    'btn_left' => 'btn btn-warning text-dark btn-sm me-2',
                    'message' => lang('backend/' . $controller . '.messages.areYouSureResetData'),
                    'text_right' => lang('backend/' . $controller . '.buttons.sendData'),
                    'icon_right' => '<i class="fa-solid fa-floppy-disk d-none d-sm-inline"></i>',
                    'btn_right' => 'btn btn-success btn-sm ms-2',
                ];

            case 'edit':
                return [
                    'id_output' => 'edit-refresh',
                    'text_left' => lang('backend/' . $controller . '.buttons.refreshData'),
                    'icon_left' => '<i class="fa-solid fa-refresh d-none d-sm-inline"></i>',
                    'btn_left' => 'btn btn-warning text-dark btn-sm me-2',
                    'message' => lang('backend/' . $controller . '.messages.areYouSureRefreshData'),
                    'text_right' => lang('backend/' . $controller . '.buttons.sendData'),
                    'icon_right' => '<i class="fa-solid fa-floppy-disk d-none d-sm-inline"></i>',
                    'btn_right' => 'btn btn-success btn-sm ms-2',
                ];

            case 'show':
                return [
                    'id_left' => 'show-print-button',
                    'text_left' => lang('backend/' . $controller . '.buttons.print'),
                    'icon_left' => '<i class="fa-solid fa-print d-none d-sm-inline"></i>',
                    'btn_left' => 'btn btn-primary btn-sm me-2',
                    'message_left' => '', 
                    'id_right' => 'show-export-button',
                    'text_right' => lang('backend/' . $controller . '.buttons.exportPdf'),
                    'icon_right' => '<i class="fa-solid fa-file-export d-none d-sm-inline"></i>',
                    'btn_right' => 'btn btn-primary btn-sm ms-2',
                    'message_right' => '', 
                ];

            case 'edit_account':
                return [
                    'id_output' => 'edit-refresh',
                    'text_left' => lang('backend/' . $controller . '.buttons.reloadData'),
                    'btn_left' => 'btn btn-warming text-dark btn-sm me-2',
                    'icon_left' => '<i class="fa-solid fa-refresh"></i>',
                    'message' => lang('backend/' . $controller . '.messages.areYouSureRefreshData'),
                    'text_right' => lang('backend/' . $controller . '.buttons.sendData'),
                    'btn_right' => 'btn btn-success btn-sm ms-2',
                    'icon_right' => '<i class="fa-solid fa-floppy-disk"></i>',
                ];

        endswitch;
    }
}