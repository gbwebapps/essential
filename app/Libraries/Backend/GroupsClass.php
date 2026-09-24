<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Models\Backend\GroupsModel;

/**
 * Classe di supporto (utility/presentazione) dedicata alla gestione dell'interfaccia dei Gruppi di amministrazione (ruoli e permessi).
 * 
 * Segue il pattern architetturale consolidato per incapsulare la logica visiva, 
 * predisponendo eventuali configurazioni contestuali, barre di navigazione o dipendenze 
 * aggiuntive (CSS/JS) richieste dalle schermate di profilazione, alleggerendo il controller primario.
 */
class GroupsClass 
{
	/**
	 * Istanza del modello di riferimento, a disposizione per estrarre 
	 * l'elenco dei ruoli, le logiche di profilazione e l'alberatura dei permessi necessari al popolamento dell'interfaccia.
	 * @var GroupsModel 
	 */
	protected GroupsModel $groupsModel;

	/**
	 * Inizializza la libreria di supporto iniettando il modello associato ai gruppi di sistema.
	 * $groupsModel Il modello preposto alla gestione e all'estrazione dei ruoli e dei permessi
	 *
	 * @param GroupsModel 
	 */
	public function __construct(GroupsModel $groupsModel) 
	{
		$this->groupsModel = $groupsModel;
	}

}
