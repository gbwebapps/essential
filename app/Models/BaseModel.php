<?php declare(strict_types = 1);

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Modello astratto di base per l'intero applicativo.
 * 
 * Rappresenta le fondamenta architetturali (Root Model) per tutti i modelli del sistema. 
 * Si occupa di centralizzare l'istanza di connessione al database, garantendo che 
 * ogni classe figlia erediti un accesso pronto e ottimizzato ai dati. Essendo una classe 
 * astratta, non può essere istanziata direttamente ma deve essere estesa (es. da BackendModel).
 */
abstract class BaseModel 
{
	/**
	 * Istanza condivisa della connessione primaria al database.
	 * 
	 * Viene inizializzata una singola volta e resa disponibile a tutte le classi derivate 
	 * per l'esecuzione di query e transazioni, ottimizzando le risorse del server.
	 *
	 * @var \CodeIgniter\Database\BaseConnection
	 */
	protected BaseConnection $db;

	/**
	 * Costruttore principale del modello.
	 * 
	 * Al momento dell'istanziazione di qualsiasi classe figlia, innesca automaticamente 
	 * il metodo di inizializzazione interno per predisporre le risorse di base (come il DB).
	 */
	public function __construct()
	{
		$this->initModel();
	}

	/**
	 * Inizializza le risorse strutturali del modello.
	 * 
	 * Legge la configurazione globale del database e stabilisce la connessione fisica, 
	 * valorizzando la proprietà protetta `$db`. Questo metodo è progettato per poter essere 
	 * esteso (con `parent::initModel()`) dalle classi figlie che necessitano di ulteriore 
	 * setup al momento del caricamento.
	 *
	 * @return void
	 */
	protected function initModel(): void 
	{
		$this->db = Database::connect();
	}
}