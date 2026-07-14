<?php declare(strict_types = 1);

use App\Models\Backend\SettingsModel;

if ( ! function_exists('setting')) :
    /**
     * Helper globale per recuperare le impostazioni unificate (DB + Config)
     * sotto forma di oggetto o tramite chiave specifica.
     *
     * @param string      $namespace Es. 'Backend\Auth'
     * @param string|null $key       Opzionale, una proprietà specifica (es. 'attemptsLimit')
     * @return mixed An object if $key is null, otherwise the specific value.
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