<?php declare(strict_types = 1); 

namespace App\Libraries\Backend;

/**
 * Servizio centralizzato per la gestione e l'invio di comunicazioni e-mail transazionali dal backend.
 * 
 * Astrae l'interazione con il servizio e-mail nativo del framework, standardizzando il rendering 
 * dei template HTML, la localizzazione dinamica dell'oggetto e la registrazione avanzata nei log 
 * in caso di fallimenti del protocollo SMTP.
 */
class EmailService
{
    /**
     * Compila e trasmette un'e-mail contenente un token operativo (es. attivazione account o reset password).
     * 
     * Il metodo elabora dinamicamente il template HTML in base al modulo chiamante, applicando 
     * l'escaping preventivo sui dati anagrafici dell'utente per ragioni di sicurezza. Formatta l'oggetto 
     * localizzato iniettando nome e cognome e affida il payload al servizio di spedizione, 
     * intercettando eventuali anomalie per facilitare il debugging.
     *
     * @param object $row Oggetto contenente i dati anagrafici dell'utente destinatario (richiede firstname, lastname, email)
     * @param string $rawToken Il token crittografico temporaneo in formato in chiaro da iniettare nel corpo del messaggio
     * @param string $module Il namespace del modulo operativo (es. 'admins', 'auth') per risolvere il percorso fisico della vista
     * @param string $template Il nome del file (senza estensione) contenente il layout HTML parziale dell'e-mail
     * @param string $subjectLangKey La chiave di localizzazione per estrarre l'oggetto del messaggio (compatibile con sprintf)
     * @return bool Esito della spedizione: true se il server SMTP ha accettato il messaggio, false in caso di errore
     */
    public function sendActivationEmail(object $row, string $rawToken, string $module, string $template, string $subjectLangKey): bool
    {
        /* Compilazione della vista Email */
        $emailData = [
            'firstname' => esc($row->firstname),
            'lastname'  => esc($row->lastname),
            'email'     => esc($row->email),
            'token'     => $rawToken
        ];
        
        $emailHTML = view('backend/' . $module . '/partials/email/' . $template, $emailData);

        /* Configurazione e invio email */
        $emailService = \Config\Services::email();
        $emailService->setTo(esc($row->email));
        
        /* Uso la chiave lingua dinamica */
        $emailService->setSubject(sprintf(lang($subjectLangKey), esc($row->firstname), esc($row->lastname)));
        $emailService->setMessage($emailHTML);

        /* Restituisce true se inviata, false in caso di errore */
        if ( ! $emailService->send()):
            log_message('error', 'Errore SMTP: ' . $emailService->printDebugger(['headers']));
            return false;
        endif;

        return true;
    }
}