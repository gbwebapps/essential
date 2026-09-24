<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Models\Backend\SettingsModel;

/**
 * Classe di supporto (utility/presentazione) dedicata alla gestione dell'interfaccia delle Impostazioni (Settings).
 * 
 * Centralizza la logica di configurazione visiva, predisponendo l'iniezione dinamica di librerie esterne 
 * (es. Tom Select) e fornendo dizionari di dati strutturati (fusi orari, formati data, lingue supportate) 
 * indispensabili per popolare correttamente i selettori (dropdown) del pannello di controllo.
 */
class SettingsClass 
{
	/**
	 * @var SettingsModel Istanza del modello di riferimento, a disposizione per estrarre 
	 * o confrontare parametri di configurazione globali propedeutici alla costruzione dell'interfaccia.
	 */
	protected SettingsModel $settingsModel;

	/**
	 * Inizializza la libreria di supporto iniettando il modello associato alle impostazioni di sistema.
	 *
	 * @param SettingsModel $settingsModel Il modello preposto alla gestione e all'elaborazione dei parametri globali
	 */
	public function __construct(SettingsModel $settingsModel) 
	{
		$this->settingsModel = $settingsModel;
	}

	/**
	 * Genera l'array di configurazione per l'iniezione dinamica degli script JavaScript nella vista principale (Index).
	 * 
	 * Predispone il caricamento della libreria Tom Select, essenziale per potenziare i selettori `<select>` nativi 
	 * trasformandoli in interfacce avanzate con capacità di ricerca rapida, ideali per gestire moli di dati 
	 * corpose come l'elenco dei fusi orari mondiali.
	 *
	 * @return array Insieme strutturato degli asset JS da iniettare nel layout
	 */
	public function getJsIndex(): array
	{
	    return [
	        ['id' => 'tom-select-js', 'path' => 'assets/vendor/tom-select/js/tom-select.complete.min.js', 'position' => 'before', 'target' => 'settings-js']
	    ];
	}

	/**
	 * Restituisce l'array di configurazione per il caricamento condizionale dei fogli di stile (CSS).
	 * 
	 * Inietta il foglio di stile specifico (in variante Bootstrap 5) per il corretto rendering visuale 
	 * del componente Tom Select, ottimizzando le performance limitandone l'inclusione alla sola schermata dedicata.
	 *
	 * @return array Insieme strutturato degli asset CSS da iniettare nel layout
	 */
	public function getCssIndex(): array
	{
	    return [
	        ['id' => 'tom-select-css', 'path' => 'assets/vendor/tom-select/css/tom-select.bootstrap5.min.css', 'position' => 'before', 'target' => 'backend-css']
	    ];
	}

	/**
	 * Recupera la lista completa e aggiornata dei fusi orari (timezones) supportati a livello server.
	 * 
	 * Sfrutta la funzione nativa di PHP per estrarre l'elenco standard degli identificatori di fuso orario, 
	 * fornendo il dataset primario necessario ad alimentare il relativo selettore nelle impostazioni di sistema.
	 *
	 * @return array Array indicizzato contenente tutti gli identificatori validi per i fusi orari (es. 'Europe/Rome')
	 */
	public function getTimezones()
	{
	    return timezone_identifiers_list();  
	}

	/**
	 * Costruisce il dizionario delle lingue ufficialmente supportate dal pannello di amministrazione.
	 * 
	 * Mappa i codici standard ISO a due lettere (chiavi) alle rispettive etichette tradotte (valori), permettendo 
	 * la generazione dinamica del selettore di localizzazione con stringhe sempre pertinenti all'idioma attuale dell'operatore.
	 *
	 * @return array Array associativo [codice_lingua => etichetta_localizzata]
	 */
	public function getLanguages()
	{
	    return [
	        'it' => lang('backend/settings.languages.italian'),
	        'en' => lang('backend/settings.languages.english'),
	        'es' => lang('backend/settings.languages.spanish'),
	        'fr' => lang('backend/settings.languages.franch'),
	        'de' => lang('backend/settings.languages.german'),
	        'zh' => lang('backend/settings.languages.chinese')
	    ]; 
	}

	/**
	 * Definisce il set di formati data/ora (basati sui pattern ICU) disponibili per la configurazione globale.
	 * 
	 * Fornisce un catalogo strutturato di formati standard (europei estesi/compatti, anglosassoni con meridiem e ISO tecnico) 
	 * mappati sulle rispettive descrizioni descrittive localizzate. Questi pattern detteranno l'output visivo 
	 * dei marcatori temporali nell'intero applicativo.
	 *
	 * @return array Array associativo [pattern_icu => descrizione_localizzata]
	 */
	public function getDateFormats(): array
    {
        return [
            /* Formati Europei / Internazionali (Giorno-Mese-Anno) */
            'd MMMM yyyy HH:mm:ss'  => lang('backend/settings.dateFormats.extended'),
            'dd/MM/yyyy HH:mm'      => lang('backend/settings.dateFormats.compact'),

            /* Formati Anglosassoni USA (Mese-Giorno-Anno, 12h) */
            'MMMM d yyyy h:mm:ss a' => lang('backend/settings.dateFormats.usExtended'),
            'MM/dd/yyyy h:mm a'     => lang('backend/settings.dateFormats.usCompact'),

            /* Formato Asiatico / Tecnico (Anno-Mese-Giorno) */
            'yyyy-MM-dd HH:mm:ss'   => lang('backend/settings.dateFormats.isoStandard')
        ]; 
    }
}
