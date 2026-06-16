<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

class AccountModel extends BackendModel
{
	protected function initModel(): void 
	{
		parent::initModel();
	}

	/**
	 * Estrae l'elenco completo dei singoli privilegi espliciti assegnati all'amministratore.
	 *
	 * Interroga la tabella delle autorizzazioni per recuperare tutte le righe associate all'identificativo
	 * univoco fornito, permettendo l'analisi puntuale delle eccezioni al ruolo base.
	 *
	 * @param string $uuid Identificativo univoco dell'amministratore.
	 * @return array Lista dei record contenenti i permessi espliciti.
	 */
	public function getPermissions(string $uuid): array
	{
	    /* Estrazione permessi assegnati all'admin */
	    $sql = "select * from admins_permissions where admin_uuid = ?";
	    return $this->db->query($sql, [$uuid])->getResult();
	}

	/**
	 * Recupera lo storico e lo stato dei token di sessione, persistenza o attivazione emessi per l'utente.
	 *
	 * Esegue un'estrazione mirata sulla tabella dei token per raccogliere i dati di tracciamento ambientali
	 * quali gli indirizzi IP, gli User Agent e i relativi formati DATETIME di creazione e scadenza.
	 *
	 * @param string $uuid Identificativo univoco dell'amministratore.
	 * @return array Lista dei token associati all'anagrafica.
	 */
	public function getTokens(string $uuid): array
	{
	    /* Estrazione log dei tokens di sessione o reset */
	    $sql = "select * from admins_tokens where admin_uuid = ?";
	    return $this->db->query($sql, [$uuid])->getResult();
	}
}