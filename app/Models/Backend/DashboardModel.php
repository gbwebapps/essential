<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

/*
 * Modello dedicato alla gestione dei dati per la schermata di Dashboard.
 * 
 * Estende il BackendModel di base. E' il file predisposto 
 * per ospitare tutte le query specifiche necessarie a popolare la pagina principale 
 * del pannello di controllo (ad esempio: recupero di statistiche globali, conteggi totali, 
 * widget di riepilogo o dati per i grafici).
 */
class DashboardModel extends BackendModel
{
	/*
	 * Metodo di inizializzazione nativo di CodeIgniter 4.
	 * 
	 * Viene eseguito automaticamente quando il modello viene istanziato. 
	 * Richiama l'inizializzazione della classe genitore (BackendModel) per assicurarsi 
	 * che le impostazioni di base (come il caricamento degli helper globali) vengano 
	 * eseguite correttamente prima di usare questo modello.
	 */
	protected function initModel(): void 
	{
		parent::initModel();
	}
}