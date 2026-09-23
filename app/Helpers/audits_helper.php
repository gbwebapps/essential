<?php declare(strict_types = 1);

/**
 * Helper globale per la registrazione e il tracciamento delle attività di sistema (audit log).
 * 
 * Centralizza e semplifica l'inserimento di eventi operativi all'interno del database, 
 * rendendo la funzione di logging rapidamente richiamabile da qualsiasi controller, libreria o filtro del framework.
 */

use App\Models\Backend\AuditsModel;

if ( ! function_exists('log_admin_activity')) :

    /**
     * Registra in modo persistente sul database un'operazione o un evento generato da un amministratore.
     * 
     * Agisce come un wrapper globale attorno ad AuditsModel, nascondendo la logica di istanziazione. 
     * Garantisce la tracciabilità e il monitoraggio delle azioni critiche (modifiche, eliminazioni, accessi) 
     * compiute all'interno dell'area riservata.
     *
     * @param string $action Chiave identificativa dell'operazione eseguita (es. 'UPDATE', 'DELETE', 'LOGIN')
     * @param string $section Il modulo o il perimetro applicativo interessato dall'evento (es. 'admins', 'settings')
     * @param string $details Descrizione testuale estesa e contestuale dell'operazione registrata
     * @param object|null $currentAdmin L'oggetto che rappresenta l'amministratore esecutore. Se omesso, la logica sottostante tenterà l'identificazione automatica
     * @return bool Esito dell'operazione: true se il record di audit è stato memorizzato con successo, false altrimenti
     */
    function log_admin_activity(string $action, string $section, string $details, ?object $currentAdmin = null): bool 
    {
        /* Istanziamo il modello che contiene la logica fisica di scrittura con la query nativa */
        $auditsModel = model(AuditsModel::class);
        
        return $auditsModel->logActivity($action, $section, $details, $currentAdmin);
    }
endif;