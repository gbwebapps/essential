<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Models\Backend\AdminsModel;

/**
 * Classe di supporto (utility/presentazione) per la gestione dell'interfaccia della sezione Amministratori.
 * 
 * Centralizza la logica di preparazione dei componenti grafici contestuali (barre di navigazione, 
 * iniezione mirata di asset CSS/JS). Struttura i payload che verranno successivamente elaborati 
 * e stampati a video dal metodo render() del BackendController, mantenendo il controller di sezione snello.
 */
class AdminsClass 
{
	/**
	 * @var AdminsModel Istanza del modello di riferimento, a disposizione per estrarre 
	 * informazioni dal database utili alla generazione dinamica degli elementi dell'interfaccia.
	 */
	protected AdminsModel $adminsModel;

	/**
	 * Inizializza la libreria di supporto iniettando il modello associato alla sezione.
	 *
	 * @param AdminsModel $adminsModel Il modello preposto alla gestione dei dati degli amministratori
	 */
	public function __construct(AdminsModel $adminsModel) 
	{
		$this->adminsModel = $adminsModel;
	}

	/* ------------------------------------------------------------------------------------------------- */

	/**
	 * Genera la configurazione della barra di navigazione contestuale per la vista ad elenco (Show All).
	 * 
	 * Espone le azioni globali disponibili in questa schermata, come il pulsante per 
	 * l'inserimento di un nuovo utente, formattando le relative icone, etichette localizzate e rotte.
	 *
	 * @return array Struttura dati per la renderizzazione della links bar
	 */
	public function getLinksBarShowAll(): array
	{
		return 
		[
		    // ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => lang('backend/admins.linksBar.index'), 'route' => 'backend/admins'],
		    ['icon' => '<i class="fa-solid fa-user-plus"></i>', 'label' => lang('backend/admins.linksBar.add'), 'route' => 'backend/admins/add'], 
		];
	}

	/**
	 * Genera la configurazione della barra di navigazione per l'interfaccia di creazione (Add).
	 * 
	 * Fornisce all'operatore i collegamenti di ritorno o le azioni pertinenti 
	 * durante la fase di inserimento di un nuovo amministratore.
	 *
	 * @return array Struttura dati per la renderizzazione della links bar
	 */
	public function getLinksBarAdd(): array
	{
		return 
		[
            // ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => lang('backend/admins.linksBar.index'), 'route' => 'backend/admins'],
            ['icon' => '<i class="fa-solid fa-users"></i>', 'label' => lang('backend/admins.linksBar.showAll'), 'route' => 'backend/admins/showAll'], 
        ];
	}

	/**
	 * Costruisce la barra di navigazione dinamica per l'interfaccia di modifica (Edit).
	 * 
	 * Inietta dinamicamente l'UUID dell'amministratore in lavorazione per generare link contestuali 
	 * (es. passaggio rapido alla vista di dettaglio). Implementa un meccanismo di sicurezza che 
	 * restituisce un array vuoto qualora l'identificatore risulti mancante, prevenendo errori di routing.
	 *
	 * @param string|null $uuid L'identificatore univoco dell'amministratore in fase di modifica
	 * @return array Struttura dati popolata con i link contestuali, o array vuoto come fallback
	 */
	public function getLinksBarEdit(?string $uuid = null): array
	{
		/* Se non c'è l'uuid, restituiamo un array senza i link specifici o gestiamo l'errore */
		if ( ! $uuid) return [];

		return 
		[
            // ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => lang('backend/admins.linksBar.index'), 'route' => 'backend/admins'],
            ['icon' => '<i class="fa-solid fa-users"></i>', 'label' => lang('backend/admins.linksBar.showAll'), 'route' => 'backend/admins/showAll'],
            ['icon' => '<i class="fa-solid fa-user-plus"></i>', 'label' => lang('backend/admins.linksBar.add'), 'route' => 'backend/admins/add'],
            ['icon' => '<i class="fa-solid fa-user"></i>', 'label' => lang('backend/admins.linksBar.show'), 'route' => "backend/admins/show/{$uuid}"],
        ];
	}

	/**
	 * Prepara la barra dei collegamenti per la vista di dettaglio del singolo profilo (Show).
	 * 
	 * Sfrutta l'UUID fornito per esporre azioni mirate sull'utente visualizzato (es. passaggio 
	 * rapido alla modalità di modifica), gestendo in modo silente l'assenza del parametro.
	 *
	 * @param string|null $uuid L'identificatore univoco dell'amministratore visualizzato
	 * @return array Struttura dati popolata con i link contestuali, o array vuoto come fallback
	 */
	public function getLinksBarShow(?string $uuid = null): array
	{
		/* Se non c'è l'uuid, restituiamo un array senza i link specifici o gestiamo l'errore */
		if ( ! $uuid) return [];

		return [
	        // ['icon' => '<i class="fa-solid fa-chart-simple"></i>', 'label' => lang('backend/admins.linksBar.index'), 'route' => 'backend/admins'],
	        ['icon' => '<i class="fa-solid fa-users"></i>', 'label' => lang('backend/admins.linksBar.showAll'), 'route' => 'backend/admins/showAll'],
	        ['icon' => '<i class="fa-solid fa-user-plus"></i>', 'label' => lang('backend/admins.linksBar.add'), 'route' => 'backend/admins/add'],
	        ['icon' => '<i class="fa-solid fa-user-pen"></i>', 'label' => lang('backend/admins.linksBar.edit'), 'route' => "backend/admins/edit/{$uuid}"],
	    ];
	}

	/* ------------------------------------------------------------------------------------------------- */

	/**
	 * Genera l'array di configurazione per l'iniezione dinamica degli script JavaScript nella vista ad elenco.
	 * 
	 * Configura il caricamento di librerie esterne necessarie alla pagina (es. Flatpickr per i filtri data). 
	 * Interroga le impostazioni globali del framework per determinare la lingua attiva e inietta 
	 * dinamicamente il file di localizzazione corrispondente, definendo le corrette dipendenze gerarchiche nel DOM.
	 *
	 * @return array Insieme strutturato degli asset JS da iniettare nel layout
	 */
	public function getJsShowAll(): array
	{
		$locale = setting('Backend\General')->language;
		
	    return [
	        ['id' => 'flatpickr-js', 'path' => 'assets/vendor/flatpickr/js/flatpickr.min.js', 'position' => 'before', 'target' => 'admins-js'], 
	        ['id' => $locale . '-js', 'path' => 'assets/vendor/flatpickr/js/' . $locale . '.js', 'position' => 'after', 'target' => 'flatpickr-js']
	    ];
	}

	/**
	 * Definisce gli asset JavaScript specifici da iniettare nella vista di dettaglio (Show).
	 * 
	 * Predispone il caricamento condizionale di librerie di utilità (es. html2pdf) necessarie 
	 * unicamente per le funzionalità operative presenti nel profilo del singolo amministratore.
	 *
	 * @return array Insieme strutturato degli asset JS da iniettare nel layout
	 */
	public function getJsShow(): array
	{
	    return [
			['id' => 'html2pdf-js', 'path' => 'assets/vendor/html2pdf/js/html2pdf.bundle.min.js', 'position' => 'before', 'target' => 'admins-js'],
	    ];
	}

	/* ------------------------------------------------------------------------------------------------- */

	/**
	 * Restituisce l'array di configurazione per il caricamento condizionale dei fogli di stile (CSS).
	 * 
	 * Applica i fogli di stile esterni richiesti dalla vista ad elenco (es. il tema base di Flatpickr), 
	 * ottimizzando il peso della pagina evitando di caricare queste risorse globalmente in tutto il backend.
	 *
	 * @return array Insieme strutturato degli asset CSS da iniettare nel layout
	 */
	public function getCssShowAll(): array
	{
	    return [
	        ['id' => 'flatpickr-css', 'path' => 'assets/vendor/flatpickr/css/flatpickr.min.css', 'position' => 'before', 'target' => 'backend-css']
	    ];
	}
}
