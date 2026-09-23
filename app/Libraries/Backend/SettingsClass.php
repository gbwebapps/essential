<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Models\Backend\SettingsModel;

class SettingsClass 
{
	protected SettingsModel $settingsModel;

	public function __construct(SettingsModel $settingsModel) 
	{
		$this->settingsModel = $settingsModel;
	}

	public function getJsIndex(): array
	{
	    return [
	        ['id' => 'tom-select-js', 'path' => 'assets/vendor/tom-select/js/tom-select.complete.min.js', 'position' => 'before', 'target' => 'settings-js']
	    ];
	}

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
	        'en' => lang('backend/settings.languages.english'),
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
