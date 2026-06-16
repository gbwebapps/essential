<?php declare(strict_types = 1);

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Class BaseModel
 *
 * Modello di base astratto dell'applicazione.
 * Centralizza e istanzia la connessione al database principale per tutti i modelli di settore.
 */
abstract class BaseModel 
{
	/**
	 * Istanza della connessione al database nativa di CodeIgniter.
	 * 
	 * @var BaseConnection 
	 */
	protected BaseConnection $db;

	/**
	 * Costruttore del modello.
	 * Esegue l'inizializzazione della connessione al database al momento dell'istanza.
	 */
	public function __construct()
	{
		$this->initModel();
	}

	/**
	 * Stabilisce e assegna la connessione attiva con il database.
	 *
	 * @return void
	 */
	protected function initModel(): void 
	{
		$this->db = Database::connect();
	}
}