<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Models\Backend\SettingsModel;

/**
 * Componente di logica applicativa dedicato alla gestione, configurazione e personalizzazione dei parametri di sistema.
 *
 * Questa classe accentra le strutture di controllo dell'interfaccia per la sezione impostazioni del backend.
 * Si occupa di definire dinamicamente i menu contestuali di opzioni, mappare le barre di navigazione
 * secondarie (linksBar) per il collegamento con gli strumenti di manutenzione (tools) e gestire
 * l'iniezione dei componenti d'asset necessari alla configurazione globale dell'applicazione.
 */
class SettingsClass 
{
	/**
	 * Modello di persistenza dedicato alla lettura, aggiornamento e archiviazione delle chiavi di configurazione sul database.
	 *
	 * @var SettingsModel
	 */
	protected SettingsModel $settingsModel;

	/**
	 * Inizializza il componente iniettando il modello necessario alla manipolazione dei parametri di configurazione.
	 *
	 * @param SettingsModel $settingsModel Istanza del modello per l'accesso e la scrittura delle impostazioni.
	 */
	public function __construct(SettingsModel $settingsModel) 
	{
		$this->settingsModel = $settingsModel;
	}

	/**
	 * Genera l'elenco delle opzioni contestuali e dei comandi di salvataggio per la schermata principale delle impostazioni.
	 *
	 * Restituisce un array di configurazione strutturato per popolare i controlli di interfaccia,
	 * permettendo la categorizzazione o il ripristino dei parametri globali di sistema.
	 *
	 * @return array
	 */
	public function getOptionsIndex()
	{
		return 
		[
		    ['label' => lang('backend/settings.options.first'),  'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/settings.options.second'), 'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		    ['label' => lang('backend/settings.options.thirst'),  'route' => '#', 'icon' => '', 'class' => '', 'id' => ''],
		];
	}

	/* ------------------------------------------------------------------------------------------------- */

	/**
	 * Costruisce la barra di navigazione secondaria per la sezione index delle impostazioni.
	 *
	 * Mappa i flussi operativi correlati alla manutenzione, inserendo link diretti provvisti di icone
	 * Font Awesome verso i pannelli di diagnostica o gli strumenti di amministrazione avanzati.
	 *
	 * @return array
	 */
	// public function getLinksBarIndex()
	// {
	// 	return 
	// 	[
    //         ['icon' => '<i class="fa-solid fa-screwdriver-wrench"></i>', 'label' => lang('backend/settings.linksBar.tools'), 'route' => 'backend/tools'],
    //     ];
	// }

	/* ------------------------------------------------------------------------------------------------- */

	/**
	 * Restituisce la configurazione dei file JavaScript specifici per la manipolazione dei moduli delle impostazioni.
	 *
	 * Definisce i percorsi e le dipendenze degli script necessari alla validazione dei campi in tempo reale,
	 * alla gestione delle interfacce dinamiche o al salvataggio asincrono dei parametri modificati.
	 *
	 * @return array
	 */
	public function getJsIndex(): array
	{
	    return [
	        ['id' => 'tom-select-js', 'path' => 'assets/vendor/tom-select/js/tom-select.complete.min.js', 'position' => 'before', 'target' => 'settings-js']
	    ];
	}

	/* ------------------------------------------------------------------------------------------------- */

	/**
	 * Restituisce la configurazione dei fogli di stile CSS specifici per la resa grafica del pannello impostazioni.
	 *
	 * Mappa i file grafici necessari alla corretta formattazione dei moduli di input, delle tabelle
	 * di configurazione e delle sezioni a schede (tabs) presenti nella vista di gestione.
	 *
	 * @return array
	 */
	public function getCssIndex(): array
	{
	    return [
	        ['id' => 'tom-select-css', 'path' => 'assets/vendor/tom-select/css/tom-select.bootstrap5.min.css', 'position' => 'before', 'target' => 'backend-css']
	    ];
	}

	public function getTimezones()
	{
	    return timezone_identifiers_list();  
	}

	public function getLanguages()
	{
	    return [
	        'it' => lang('backend/settings.languages.italian'),
	        'en-US' => lang('backend/settings.languages.englishUs'),
	        'en-GB' => lang('backend/settings.languages.englishUk'),
	        'es' => lang('backend/settings.languages.spanish'),
	        'fr' => lang('backend/settings.languages.franch'),
	        'de' => lang('backend/settings.languages.german'),
	        'zh' => lang('backend/settings.languages.chinese')
	    ]; 
	}

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
