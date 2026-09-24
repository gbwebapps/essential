<?php declare(strict_types = 1);

namespace App\Validation\Backend;

/**
 * Classe dedicata alle regole di validazione personalizzate per il modulo Amministratori.
 * 
 * Estende il motore di validazione nativo di CodeIgniter 4, fornendo controlli di sicurezza 
 * specifici da applicare ai campi di testo e ai dati inviati tramite i form del pannello di controllo.
 */
class AdminsRules
{
    /**
     * Regola di sicurezza anti-injection per la convalida dei campi testuali liberi (es. note o descrizioni).
     * 
     * Analizza la stringa in ingresso tramite espressione regolare e la respinge (restituendo false) 
     * se individua caratteri potenzialmente pericolosi per il rendering a schermo. Nello specifico, 
     * blocca l'inserimento di parentesi angolari (< e >) utilizzate per i tag HTML e dei backtick (`), 
     * prevenendo sul nascere vulnerabilità di tipo XSS o alterazioni indesiderate del layout.
     *
     * @param string $str La stringa di testo sottomessa dall'utente da validare
     * @return bool True se la stringa è considerata pulita e sicura, false se contiene caratteri bloccati
     */
    public function safeText(string $str): bool
    {
        return ! preg_match('/[<>\x60]/', $str);
    }
}