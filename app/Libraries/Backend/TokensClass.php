<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Models\Backend\TokensModel;

class TokensClass 
{
	protected TokensModel $tokensModel;

	public function __construct(TokensModel $tokensModel) 
	{
		$this->tokensModel = $tokensModel;
	}

	public function getJsIndex(): array
	{
	    return [
	        ['id' => 'flatpickr-js', 'path' => 'assets/vendor/flatpickr/js/flatpickr.min.js', 'position' => 'before', 'target' => 'tokens-js'], 
	        ['id' => 'it-js', 'path' => 'assets/vendor/flatpickr/js/it.js', 'position' => 'after', 'target' => 'flatpickr-js']
	    ];
	}

	public function getCssIndex(): array
	{
	    return [
	        ['id' => 'flatpickr-css', 'path' => 'assets/vendor/flatpickr/css/flatpickr.min.css', 'position' => 'before', 'target' => 'backend-css']
	    ];
	}
}
