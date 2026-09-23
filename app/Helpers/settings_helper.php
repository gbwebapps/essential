<?php declare(strict_types = 1);

/**
 * Helper globale per il recupero unificato delle configurazioni di sistema (Settings).
 * 
 * Fornisce un punto di accesso centralizzato e rapido per la lettura dei parametri operativi 
 * dell'applicativo, gestendo in modo trasparente l'integrazione e la sovrascrittura tra i valori 
 * persistenti salvati nel database e i default strutturali definiti nei file di configurazione fisici.
 */

use App\Models\Backend\SettingsModel;

if ( ! function_exists('setting')) :

    /**
     * Estrae dinamicamente uno o più valori di configurazione associati a uno specifico ambiente (namespace).
     * 
     * Interroga il modello di riferimento sfruttando la sua cache in memoria per evitare query ridondanti. 
     * Se viene fornita una chiave specifica, restituisce il singolo valore corrispondente. In assenza di una chiave, 
     * converte l'intero array delle configurazioni in un oggetto anonimo, consentendo l'accesso ai dati 
     * tramite una comoda notazione a oggetti (es. setting('Backend\General')->language).
     *
     * @param string $namespace Lo spazio di nomi o l'identificativo del blocco di configurazione (es. 'Backend\General' o 'Backend\Auth')
     * @param string|null $key (Opzionale) La singola proprietà da recuperare. Se omessa, viene estrapolato e restituito l'intero ambiente
     * @return mixed|object|null Il valore scalare della chiave richiesta, l'intero blocco di configurazioni sotto forma di oggetto, oppure null se la chiave non è presente
     */
    function setting(string $namespace, ?string $key = null)
    {
        $settingsModel = model(SettingsModel::class);
        
        /* Estrae l'array unificato (DB + Default dei file Config) sfruttando la cache in-memory */
        $allSettings = $settingsModel->getSettings($namespace);

        if ($key !== null) :
            return $allSettings[$key] ?? null;
        endif;

        /* Converte l'array in un oggetto anonimo per permettere la sintassi ->property */
        return (object) $allSettings;
    }
endif;