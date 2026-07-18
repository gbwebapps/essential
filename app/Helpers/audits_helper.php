<?php

use App\Models\Backend\AuditsModel;

if ( ! function_exists('log_admin_activity')) :
    /**
     * Helper globale per registrare un'azione nel registro delle attività (Audit Log) degli amministratori.
     * 
     * @param string $action L'azione compiuta (es. 'INSERT', 'UPDATE', 'DELETE')
     * @param string $section La sezione interessata (es. 'settings', 'users')
     * @param string|null $details Dettagli aggiuntivi, preferibilmente in formato JSON o testo descrittivo
     * @return bool Ritorna true se l'inserimento è andato a buon fine, altrimenti false
     */
    function log_admin_activity(string $action, string $section, ?string $details = null): bool 
    {
        /* Istanziamo il modello che contiene la logica fisica di scrittura con la query nativa */
        $auditsModel = model(AuditsModel::class);
        
        return $auditsModel->logActivity($action, $section, $details);
    }
endif;