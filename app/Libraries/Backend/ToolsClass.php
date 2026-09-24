<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Models\Backend\ToolsModel;

/**
 * Classe di supporto (utility/presentazione) dedicata alla gestione dell'interfaccia della sezione Strumenti (Tools).
 * 
 * Centralizza e isola la logica di configurazione per l'iniezione dinamica degli asset (CSS/JS) 
 * necessari al funzionamento delle schermate operative e delle utilità di sistema. Prepara payload 
 * strutturati che verranno successivamente elaborati in fase di rendering dal BackendController.
 */
class ToolsClass 
{
	/**
	 * Istanza del modello di riferimento, a disposizione per eventuali interrogazioni 
	 * propedeutiche alla costruzione dell'interfaccia o all'esecuzione degli strumenti di sistema.
	 * @var ToolsModel 
	 */
	protected ToolsModel $toolsModel;

	/**
	 * Inizializza la libreria di supporto iniettando il modello associato alla sezione Tools.
	 *
	 * @param ToolsModel $toolsModel Il modello preposto alla gestione logica e operativa degli strumenti di amministrazione
	 */
	public function __construct(ToolsModel $toolsModel) 
	{
		$this->toolsModel = $toolsModel;
	}

	/**
	 * Genera l'array di configurazione per l'iniezione dinamica degli script JavaScript nella vista principale (Index).
	 * 
	 * Predispone il caricamento della libreria Flatpickr, utile per la gestione avanzata di eventuali input 
	 * o filtri temporali presenti negli strumenti. Interroga dinamicamente le configurazioni globali 
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

	/**
	 * Restituisce l'array di configurazione per il caricamento condizionale dei fogli di stile (CSS).
	 * 
	 * Inietta il foglio di stile base necessario al corretto rendering visivo del componente Flatpickr, 
	 * ottimizzando il caricamento della pagina circoscrivendo la risorsa unicamente dove richiesta.
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
