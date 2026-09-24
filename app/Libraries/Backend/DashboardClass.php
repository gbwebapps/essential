<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Models\Backend\DashboardModel;

/**
 * Classe di supporto (utility/presentazione) dedicata alla gestione dell'interfaccia della Dashboard.
 * 
 * Segue il pattern architetturale consolidato per incapsulare la logica visiva, 
 * predisponendo eventuali configurazioni contestuali, strutture per widget o dipendenze 
 * aggiuntive (CSS/JS) richieste dalla schermata di riepilogo globale, alleggerendo il controller primario.
 */
class DashboardClass 
{
	/**
	 * Istanza del modello di riferimento, a disposizione per aggregare 
	 * metriche, KPI, statistiche o dati di riepilogo necessari al popolamento dinamico dell'interfaccia.
	 * @var DashboardModel 
	 */
	protected DashboardModel $dashboardModel;

	/**
	 * Inizializza la libreria di supporto iniettando il modello associato alla dashboard.
	 *
	 * @param DashboardModel $dashboardModel Il modello preposto al recupero dei dati statistici e operativi generali
	 */
	public function __construct(DashboardModel $dashboardModel) 
	{
		$this->dashboardModel = $dashboardModel;
	}

}
