<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Models\Backend\AuditsModel;

/**
 * Classe di supporto (utility/presentazione) per la gestione dell'interfaccia della sezione Audits (Log di sistema).
 * 
 * Centralizza e isola la logica di configurazione per l'iniezione dinamica degli asset (CSS/JS) necessari 
 * alla consultazione e al filtraggio degli eventi di sistema. Prepara payload strutturati che vengono 
 * elaborati in fase di rendering dal BackendController, mantenendo il controller primario privo di logiche visive.
 */
class AuditsClass 
{
	/**
	 * @var AuditsModel Istanza del modello di riferimento, a disposizione per eventuali interrogazioni 
	 * propedeutiche alla costruzione dell'interfaccia o per la lettura delle opzioni di tracciamento.
	 */
	protected AuditsModel $auditsModel;

	/**
	 * Inizializza la libreria di supporto iniettando il modello associato alla sezione degli audit log.
	 *
	 * @param AuditsModel $auditsModel Il modello preposto alla gestione e all'estrazione dei log di sistema
	 */
	public function __construct(AuditsModel $auditsModel) 
	{
		$this->auditsModel = $auditsModel;
	}

	/* ------------------------------------------------------------------------------------------------- */

	/**
	 * Genera l'array di configurazione per l'iniezione dinamica degli script JavaScript nella vista principale (Index).
	 * 
	 * Predispone il caricamento della libreria Flatpickr, essenziale per la gestione avanzata 
	 * dei filtri temporali nella tabella dei log. Interroga dinamicamente le configurazioni di sistema 
	 * per determinare la lingua attiva e accoda in modo ordinato il relativo file di localizzazione.
	 *
	 * @return array Insieme strutturato degli asset JS da iniettare nel layout
	 */
	public function getJsIndex(): array
	{
		$locale = setting('Backend\General')->language;
		
	    return [
	        ['id' => 'flatpickr-js', 'path' => 'assets/vendor/flatpickr/js/flatpickr.min.js', 'position' => 'before', 'target' => 'audits-js'], 
	        ['id' => $locale . '-js', 'path' => 'assets/vendor/flatpickr/js/' . $locale . '.js', 'position' => 'after', 'target' => 'flatpickr-js']
	    ];
	}

	/* ------------------------------------------------------------------------------------------------- */

	/**
	 * Restituisce l'array di configurazione per il caricamento condizionale dei fogli di stile (CSS).
	 * 
	 * Inietta il foglio di stile base necessario al corretto rendering del calendario (Flatpickr) 
	 * utilizzato per il filtraggio delle date, limitandone il caricamento alla sola pagina in cui è richiesto.
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
