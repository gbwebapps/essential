<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Models\Backend\TokensModel;

/**
 * Classe di supporto (utility/presentazione) dedicata alla gestione dell'interfaccia dei Token (sessioni e accessi).
 * 
 * Centralizza e isola la logica di configurazione per l'iniezione dinamica degli asset (CSS/JS) 
 * necessari alla consultazione e al filtraggio dell'elenco dei token attivi o storici. 
 * Prepara i payload strutturati che verranno elaborati dal metodo render() del BackendController.
 */
class TokensClass 
{
	/**
	 * Istanza del modello di riferimento, a disposizione per eventuali interrogazioni 
	 * propedeutiche alla costruzione dell'interfaccia di gestione dei token di autenticazione.
	 * @var TokensModel 
	 */
	protected TokensModel $tokensModel;

	/**
	 * Inizializza la libreria di supporto iniettando il modello associato ai token.
	 * $tokensModel Il modello preposto alla gestione e all'estrazione dei token di sistema
	 *
	 * @param TokensModel 
	 */
	public function __construct(TokensModel $tokensModel) 
	{
		$this->tokensModel = $tokensModel;
	}

	/**
	 * Genera l'array di configurazione per l'iniezione dinamica degli script JavaScript nella vista principale (Index).
	 * 
	 * Predispone il caricamento della libreria Flatpickr, essenziale per la gestione dei filtri temporali 
	 * nella tabella riepilogativa. In questo contesto, accoda specificamente il file di localizzazione 
	 * per la lingua italiana, definendo l'esatta gerarchia e dipendenza di inserimento nel DOM.
	 *
	 * @return array Insieme strutturato degli asset JS da iniettare nel layout
	 */
	public function getJsIndex(): array
	{
	    return [
	        ['id' => 'flatpickr-js', 'path' => 'assets/vendor/flatpickr/js/flatpickr.min.js', 'position' => 'before', 'target' => 'tokens-js'], 
	        ['id' => 'it-js', 'path' => 'assets/vendor/flatpickr/js/it.js', 'position' => 'after', 'target' => 'flatpickr-js']
	    ];
	}

	/**
	 * Restituisce l'array di configurazione per il caricamento condizionale dei fogli di stile (CSS).
	 * 
	 * Inietta il foglio di stile base necessario al corretto rendering visuale del calendario (Flatpickr), 
	 * ottimizzando le performance generali limitandone l'inclusione alla sola schermata che ne fa uso.
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
