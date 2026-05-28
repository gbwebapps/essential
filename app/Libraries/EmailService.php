<?php declare(strict_types = 1); 

namespace App\Libraries;

class EmailService
{
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