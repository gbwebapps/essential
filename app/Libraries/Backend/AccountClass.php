<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Models\Backend\AccountModel;

/**
 * Classe per la gestione delle logiche di business relative agli account.
 */
class AccountClass 
{
	/**
	 * Istanza del modello per la gestione dei dati degli account.
	 *
	 * @var AccountModel
	 */
	protected AccountModel $accountModel;

	/**
	 * Costruttore della classe.
	 *
	 * @param AccountModel $accountModel Istanza del modello account.
	 */
	public function __construct(AccountModel $accountModel) 
	{
		$this->accountModel = $accountModel;
	}

	/**
	 * Ritorna l'array di configurazione per i file JavaScript della pagina index.
	 *
	 * @return array
	 */
	/*public function getJsIndex()
	{
		return [
	        ['id' => 'datatables-js', 'path' => 'assets/js/backend/datatables.js', 'position' => 'before', 'target' => 'backend-js']
	    ];
	}*/

	/* ------------------------------------------------------------------------------------------------- */

	/**
	 * Ritorna l'array di configurazione per i file CSS della pagina index.
	 *
	 * @return array
	 */
	/*public function getCssIndex()
	{
		return [
		    ['id' => 'datatables-css', 'path' => 'assets/css/backend/datatables.css', 'position' => 'before', 'target' => 'backend-css']
		];
	}*/
}
