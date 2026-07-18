<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Models\Backend\AuditsModel;

/**
 * Componente di logica applicativa dedicato alla gestione e configurazione della audits di backend.
 *
 * Questa classe orchestra la visualizzazione della pannellatura principale, occupandosi di strutturare
 * i menu contestuali, le barre di navigazione secondarie e i requisiti di caricamento degli asset
 * per i widget grafici e i pannelli informativi riassuntivi del sistema.
 */
class AuditsClass 
{
	/**
	 * Modello di persistenza dedicato all'estrazione delle metriche e dei dati aggregati per la audits.
	 *
	 * @var AuditsModel
	 */
	protected AuditsModel $auditsModel;

	/**
	 * Inizializza il componente iniettando il modello necessario alla raccolta dei dati statistici.
	 *
	 * @param AuditsModel $auditsModel Istanza del modello per le query analitiche sul database.
	 */
	public function __construct(AuditsModel $auditsModel) 
	{
		$this->auditsModel = $auditsModel;
	}

	/**
	 * Genera l'elenco delle opzioni contestuali e dei filtri rapidi per la schermata principale della audits.
	 *
	 * Fornisce un array di configurazione per l'interfaccia utente, permettendo di attivare funzionalità
	 * di sfoltimento o impostazione dei dati statistici visualizzati.
	 *
	 * @return array
	 */
	/*public function getOptionsIndex()
	{
		return 
		[
		    ['label' => lang('backend/audits.options.first'),  'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/audits.options.second'), 'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/audits.options.thirst'),  'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		];
	}*/

	/* ------------------------------------------------------------------------------------------------- */

	/**
	 * Costruisce la barra di navigazione secondaria per la sezione index della audits.
	 *
	 * Definisce i collegamenti rapidi completi di icone e traduzioni per saltare tra i vari pannelli
	 * di monitoraggio del pannello di controllo (es. performance, log o statistiche).
	 *
	 * @return array
	 */
	/*public function getLinksBarIndex()
	{
		return 
		[
            ['icon' => '<i class="fa-solid fa-tachometer"></i>', 'label' => lang('backend/audits.options.performances'), 'route' => 'backend/audits'],
        ];
	}*/

	/* ------------------------------------------------------------------------------------------------- */

	/**
	 * Restituisce l'elenco dei file JavaScript specifici richiesti per il corretto funzionamento della audits.
	 *
	 * Mappa i file script necessari alla renderizzazione dei grafici, delle tabelle informative o
	 * dei widget dinamici, impostando la priorità e il target di iniezione nel DOM.
	 *
	 * @return array
	 */
	public function getJsIndex(): array
	{
	    return [
	        ['id' => 'flatpickr-js', 'path' => 'assets/vendor/flatpickr/js/flatpickr.min.js', 'position' => 'before', 'target' => 'audits-js'], 
	        ['id' => 'it-js', 'path' => 'assets/vendor/flatpickr/js/it.js', 'position' => 'after', 'target' => 'flatpickr-js']
	    ];
	}

	/* ------------------------------------------------------------------------------------------------- */

	/**
	 * Restituisce l'elenco dei fogli di stile CSS specifici per l'interfaccia della audits.
	 *
	 * Configura i file di stile necessari alla resa grafica personalizzata dei widget e dei layout
	 * di riepilogo dati nel pannello principale.
	 *
	 * @return array
	 */
	public function getCssIndex(): array
	{
	    return [
	        ['id' => 'flatpickr-css', 'path' => 'assets/vendor/flatpickr/css/flatpickr.min.css', 'position' => 'before', 'target' => 'backend-css']
	    ];
	}
}
