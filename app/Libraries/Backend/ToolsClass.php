<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Models\Backend\ToolsModel;

class ToolsClass 
{
	protected ToolsModel $toolsModel;

	public function __construct(ToolsModel $toolsModel) 
	{
		$this->toolsModel = $toolsModel;
	}

	public function getJsIndex(): array
	{
	    $locale = setting('Backend\General')->language;

	    return [
	        ['id' => 'flatpickr-js', 'path' => 'assets/vendor/flatpickr/js/flatpickr.min.js', 'position' => 'before', 'target' => 'audits-js'], 
	        ['id' => $locale . '-js', 'path' => 'assets/vendor/flatpickr/js/' . $locale . '.js', 'position' => 'after', 'target' => 'flatpickr-js']
	    ];
	}

	public function getCssIndex(): array
	{
	    return [
	        ['id' => 'flatpickr-css', 'path' => 'assets/vendor/flatpickr/css/flatpickr.min.css', 'position' => 'before', 'target' => 'backend-css']
	    ];
	}
}
