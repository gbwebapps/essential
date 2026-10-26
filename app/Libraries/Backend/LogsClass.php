<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

/**
 * Classe di supporto (utility/presentazione) per la gestione dell'interfaccia della sezione Logs (Log applicativi).
 * 
 * Centralizza e isola la logica di configurazione per l'iniezione dinamica degli asset (CSS/JS) necessari 
 * alla consultazione e al filtraggio dei file di log generati dal framework. Prepara payload strutturati che vengono 
 * elaborati in fase di rendering dal BackendController, mantenendo il controller primario privo di logiche visive.
 */
class LogsClass 
{
	/**
	 * Genera l'array di configurazione per l'iniezione dinamica degli script JavaScript nella vista principale (Index).
	 * 
	 * Predispone il caricamento della libreria Flatpickr, essenziale per la gestione avanzata 
	 * dei filtri temporali (es. selezione della data del log da visualizzare). Interroga dinamicamente le configurazioni 
	 * di sistema per determinare la lingua attiva e accoda in modo ordinato il relativo file di localizzazione.
	 *
	 * @return array Insieme strutturato degli asset JS da iniettare nel layout
	 */
	public function getJsIndex(): array
	{
		$lang = service('request')->getLocale();
		$locale = ($lang === 'en' ? 'default' : $lang);
		
	    return [
	        ['id' => 'flatpickr-js', 'path' => 'assets/vendor/flatpickr/js/flatpickr.min.js', 'position' => 'before', 'target' => 'logs-js'], 
	        ['id' => $locale . '-js', 'path' => 'assets/vendor/flatpickr/js/' . $locale . '.js', 'position' => 'after', 'target' => 'flatpickr-js']
	    ];
	}

	/**
	 * Restituisce l'array di configurazione per il caricamento condizionale dei fogli di stile (CSS).
	 * 
	 * Inietta il foglio di stile base necessario al corretto rendering del calendario (Flatpickr) 
	 * impiegato per il filtraggio temporale, ottimizzando le performance limitandone il caricamento alla sola schermata dedicata.
	 *
	 * @return array Insieme strutturato degli asset CSS da iniettare nel layout
	 */
	public function getCssIndex(): array
	{
	    return [
	        ['id' => 'flatpickr-css', 'path' => 'assets/vendor/flatpickr/css/flatpickr.min.css', 'position' => 'before', 'target' => 'backend-css']
	    ];
	}
}
