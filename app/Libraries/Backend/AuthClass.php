<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Models\Backend\AuthModel;

/**
 * Classe di supporto (utility/presentazione) dedicata alla gestione dell'interfaccia di Autenticazione (Auth).
 * 
 * Fornisce l'infrastruttura di base per incapsulare la logica visiva e preparare eventuali dati 
 * strutturati, asset (CSS/JS) o configurazioni contestuali richieste dalle schermate di accesso, 
 * recupero password e verifica a due fattori (2FA), alleggerendo il controller preposto.
 */
class AuthClass 
{
	/**
	 * @var AuthModel Istanza del modello di riferimento, utilizzabile per estrarre 
	 * parametri di sicurezza o validare stati logici propedeutici alla costruzione dell'interfaccia.
	 */
	protected AuthModel $authModel;

	/**
	 * Inizializza la libreria di supporto iniettando il modello di autenticazione.
	 *
	 * @param AuthModel $authModel Il modello preposto alla gestione dei flussi di accesso e sicurezza
	 */
	public function __construct(AuthModel $authModel) 
	{
		$this->authModel = $authModel;
	}
	
}
