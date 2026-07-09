<?php declare(strict_types = 1); 

namespace App\Libraries\Backend;

/**
 * Class EmailService
 *
 * Libreria per la gestione e l'invio delle comunicazioni via email del sistema,
 * con supporto per la compilazione di viste dinamiche, localizzazione e tracciamento log degli errori SMTP.
 */
class EmailService
{
    /**
     * Compila e invia un'email transazionale (attivazione, reset o notifica) basata su un template specifico.
     *
     * @param object $row            Oggetto contenente i dati anagrafici del destinatario (firstname, lastname, email).
     * @param string $rawToken       Il token di sicurezza in chiaro da includere nel corpo del messaggio.
     * @param string $module         Nome del modulo per individuare il percorso corretto dei partial dell'email.
     * @param string $template       Il nome del file di template della vista da renderizzare.
     * @param string $subjectLangKey La chiave di lingua per comporre e tradurre dinamicamente l'oggetto dell'email.
     * @return bool True se l'invio SMTP va a buon fine, false in caso di errore (con contestuale scrittura nel log di sistema).
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