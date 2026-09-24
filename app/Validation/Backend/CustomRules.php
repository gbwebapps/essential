<?php declare(strict_types = 1);

namespace App\Validation\Backend;

/**
 * Classe globale per le regole di validazione personalizzate (Custom Rules) del Backend.
 * 
 * Raggruppa i metodi di controllo trasversali che estendono o sovrascrivono 
 * le regole native del framework CodeIgniter 4, adattando la validazione dei dati 
 * alle specifiche logiche architetturali dell'applicativo (come il Soft Delete).
 */
class CustomRules
{
    /**
     * Valida un indirizzo email supportando nativamente i formati offuscati dal sistema.
     * 
     * Questa regola è progettata per coesistere con la logica di cestinamento (Soft Delete). 
     * Se rileva che l'email è stata alterata con il marcatore di sistema (es. '.deleted.1691234567'), 
     * utilizza un'espressione regolare per estrarre l'indirizzo originale pulito e lo valida. 
     * In assenza del marcatore, esegue il classico controllo formale tramite i filtri nativi di PHP. 
     * Impedisce che i record cestinati generino errori di validazione durante le fasi di lettura o ripristino.
     *
     * @param string $str La stringa contenente l'indirizzo email da esaminare
     * @return bool True se il formato dell'email (pulita o originale) è valido, false altrimenti
     */
    public function valid_email(string $str): bool
    {
        /* Se l'email contiene il suffisso di soft delete seguito da timestamp */
        if (preg_match('/^(.*?)\.deleted\.\d{10}$/', $str, $matches)):
            $baseEmail = $matches[1];
            return (bool) filter_var($baseEmail, FILTER_VALIDATE_EMAIL);
        endif;

        /* Altrimenti esegue la normale validazione email */
        return (bool) filter_var($str, FILTER_VALIDATE_EMAIL);
    }
}