<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Models\Backend\AdminsModel;

/**
 * Componente di logica applicativa per la gestione dei profili amministrativi del backend.
 *
 * Questa classe centralizza la configurazione dinamica dell'interfaccia utente per la sezione
 * amministratori. Si occupa di strutturare i menu di opzioni contestuali, le barre di navigazione
 * secondarie (linksBar) e l'iniezione degli asset specifici per le diverse viste operative.
 */
class AdminsClass 
{
	/**
	 * Modello di persistenza dedicato alla gestione e interrogazione dei dati degli amministratori.
	 *
	 * @var AdminsModel
	 */
	protected AdminsModel $adminsModel;

	/**
	 * Inizializza il componente iniettando il modello necessario alla gestione dei dati.
	 *
	 * @param AdminsModel $adminsModel Istanza del modello per le operazioni sul database.
	 */
	public function __construct(AdminsModel $adminsModel) 
	{
		$this->adminsModel = $adminsModel;
	}

	/**
	 * Genera l'elenco delle opzioni contestuali per la vista principale (index) degli amministratori.
	 *
	 * Ritorna un array di configurazione per la visualizzazione di azioni rapide o filtri dedicati
	 * alla dashboard principale della sezione.
	 *
	 * @return array
	 */
	public function getOptionsIndex()
	{
		return 
		[
		    ['label' => lang('backend/admins.options.first'),  'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/admins.options.second'), 'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/admins.options.thirst'),  'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		];
	}

	/**
	 * Genera l'elenco delle opzioni contestuali per la vista globale (showAll) degli amministratori.
	 *
	 * Fornisce i parametri di configurazione per i controlli e le azioni di massa eseguibili
	 * sull'elenco completo degli utenti amministrativi.
	 *
	 * @return array
	 */
	public function getOptionsShowAll()
	{
		return 
		[
		    ['label' => lang('backend/admins.options.first'),  'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/admins.options.second'), 'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/admins.options.thirst'),  'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		];
	}

	/**
	 * Genera l'elenco delle opzioni contestuali utilizzabili all'interno della vista di creazione (add).
	 *
	 * Definisce le azioni secondarie disponibili per l'operatore durante la fase di inserimento
	 * di un nuovo profilo amministrativo.
	 *
	 * @return array
	 */
	public function getOptionsAdd()
	{
		return 
		[
		    ['label' => lang('backend/admins.options.first'),  'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/admins.options.second'), 'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/admins.options.thirst'),  'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		];
	}

	/**
	 * Genera l'elenco delle opzioni contestuali utilizzabili all'interno della vista di modifica (edit).
	 *
	 * Stabilisce le scorciatoie o i comandi ausiliari accessibili durante l'aggiornamento
	 * delle informazioni di un amministratore esistente.
	 *
	 * @return array
	 */
	public function getOptionsEdit()
	{
		return 
		[
		    ['label' => lang('backend/admins.options.first'),  'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/admins.options.second'), 'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/admins.options.thirst'),  'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		];
	}

	/**
	 * Genera l'elenco delle opzioni contestuali per la vista di dettaglio singolo (show).
	 *
	 * Restituisce i collegamenti operativi pertinenti alla visualizzazione della scheda anagrafica
	 * e tecnica di uno specifico amministratore.
	 *
	 * @return array
	 */
	public function getOptionsShow()
	{
		return 
		[
		    ['label' => lang('backend/admins.options.first'),  'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/admins.options.second'), 'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/admins.options.thirst'),  'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		];
	}

	/* ------------------------------------------------------------------------------------------------- */

	/**
	 * Costruisce la barra di navigazione secondaria per la sezione principale index.
	 *
	 * Restituisce i pulsanti d'azione completi di icone Font Awesome e traduzioni per muoversi
	 * verso la visualizzazione tabellare o la creazione.
	 *
	 * @return array
	 */
	public function getLinksBarIndex()
	{
		return 
		[
            ['icon' => '<i class="fa-solid fa-users"></i>', 'label' => lang('backend/admins.linksBar.showAll'), 'route' => 'backend/admins/showAll'],
            ['icon' => '<i class="fa-solid fa-user-plus"></i>', 'label' => lang('backend/admins.linksBar.add'), 'route' => 'backend/admins/add'], 
        ];
	}

	/**
	 * Costruisce la barra di navigazione secondaria per la sezione tabellare showAll.
	 *
	 * Fornisce i collegamenti di ritorno alla dashboard o di inoltro verso la creazione di un nuovo utente.
	 *
	 * @return array
	 */
	public function getLinksBarShowAll()
	{
		return 
		[
		    ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => lang('backend/admins.linksBar.index'), 'route' => 'backend/admins'],
		    ['icon' => '<i class="fa-solid fa-user-plus"></i>', 'label' => lang('backend/admins.linksBar.add'), 'route' => 'backend/admins/add'], 
		];
	}

	/**
	 * Costruisce la barra di navigazione secondaria per la schermata di creazione nuovo amministratore.
	 *
	 * Mette a disposizione i link rapidi per annullare l'operazione e ritornare alle liste generali.
	 *
	 * @return array
	 */
	public function getLinksBarAdd()
	{
		return 
		[
            ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => lang('backend/admins.linksBar.index'), 'route' => 'backend/admins'],
            ['icon' => '<i class="fa-solid fa-users"></i>', 'label' => lang('backend/admins.linksBar.showAll'), 'route' => 'backend/admins/showAll'], 
        ];
	}

	/**
	 * Costruisce la barra di navigazione secondaria per la schermata di modifica.
	 *
	 * Mappa i flussi di navigazione coerenti con l'entità in esame, permettendo il passaggio rapido
	 * alla visualizzazione della scheda specifica tramite il codice identificativo.
	 *
	 * @param string|null $uuid Identificativo univoco dell'amministratore in fase di modifica.
	 * @return array
	 */
	public function getLinksBarEdit(?string $uuid = null)
	{
		return 
		[
            ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => lang('backend/admins.linksBar.index'), 'route' => 'backend/admins'],
            ['icon' => '<i class="fa-solid fa-users"></i>', 'label' => lang('backend/admins.linksBar.showAll'), 'route' => 'backend/admins/showAll'],
            ['icon' => '<i class="fa-solid fa-user-plus"></i>', 'label' => lang('backend/admins.linksBar.add'), 'route' => 'backend/admins/add'],
            ['icon' => '<i class="fa-solid fa-user"></i>', 'label' => lang('backend/admins.linksBar.show'), 'route' => "backend/admins/show/{$uuid}"],
        ];
	}

	/**
	 * Costruisce la barra di navigazione secondaria per la scheda di dettaglio del singolo amministratore.
	 *
	 * Verifica la presenza dell'identificativo univoco e genera dinamicamente i link di ritorno
	 * o di transizione verso la schermata di modifica del profilo selezionato.
	 *
	 * @param string|null $uuid Identificativo univoco dell'amministratore da visualizzare.
	 * @return array
	 */
	public function getLinksBarShow(?string $uuid = null)
	{
		/* Se non c'è l'uuid, restituiamo un array senza i link specifici o gestiamo l'errore */
		if ( ! $uuid) return [];

		return [
	        ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => lang('backend/admins.linksBar.index'), 'route' => 'backend/admins'],
	        ['icon' => '<i class="fa-solid fa-users"></i>', 'label' => lang('backend/admins.linksBar.showAll'), 'route' => 'backend/admins/showAll'],
	        ['icon' => '<i class="fa-solid fa-user-plus"></i>', 'label' => lang('backend/admins.linksBar.add'), 'route' => 'backend/admins/add'],
	        ['icon' => '<i class="fa-solid fa-user-pen"></i>', 'label' => lang('backend/admins.linksBar.edit'), 'route' => "backend/admins/edit/{$uuid}"],
	    ];
	}

	/* ------------------------------------------------------------------------------------------------- */

	/**
	 * Restituisce l'elenco dei file JavaScript specifici richiesti dalla vista tabellare globale.
	 *
	 * Configura i percorsi e i punti di aggancio per i motori di gestione delle tabelle dati (DataTables)
	 * e delle relative estensioni per Bootstrap 5.
	 *
	 * @return array
	 */
	public function getJsShowAll(): array
	{
	    return [
	        // ['id' => 'jquery', 'path' => 'assets/vendor/jquery/jquery.min.js', 'isModule' => false, 'position' => 'before', 'target' => 'backend-js'],
	        // ['id' => 'datatables-js', 'path' => 'assets/vendor/datatables/js/dataTables.min.js', 'isModule' => false, 'position' => 'before', 'target' => 'backend-js'],
	        // ['id' => 'datatables-bs5-js', 'path' => 'assets/vendor/datatables/js/dataTables.bootstrap5.min.js', 'isModule' => false, 'position' => 'before', 'target' => 'backend-js'],
	    ];
	}

	/* ------------------------------------------------------------------------------------------------- */

	/**
	 * Restituisce l'elenco dei fogli di stile CSS specifici richiesti dalla vista tabellare globale.
	 *
	 * Definisce i file di stile necessari alla corretta resa grafica dell'interfaccia di impaginazione
	 * e ricerca della tabella amministratori.
	 *
	 * @return array
	 */
	public function getCssShowAll(): array
	{
	    return [
	        // ['id' => 'datatables-bs5-css', 'path' => 'assets/vendor/datatables/css/dataTables.bootstrap5.min.css', 'position' => 'before', 'target' => 'backend-css'],
	    ];
	}
}
