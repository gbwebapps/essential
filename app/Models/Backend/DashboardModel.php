<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;
use Config\Backend\Dashboard;

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
	/**
	 * Riutilizza i conteggi autorizzati dei menu e richiede le analisi ai moduli di dominio.
	 * Nessun provider viene interrogato senza il permesso di consultare tutte le entità coinvolte.
	 *
	 * @return array{counts: array, rankings: array, totals: array}
	 */
	public function getOverview(?object $admin): array
	{
		$counts = model(MenuCountsModel::class)->getCounts($admin);
		$rankings = [];

		foreach (Dashboard::RANKINGS as $name => $definition):
			if (array_diff($definition['requires'], array_keys($counts)) !== []):
				continue;
			endif;

			try
			{
				$provider = model($definition['model']);
				$rankings[$name] = $provider->{$definition['method']}(Dashboard::RANKING_LIMIT);
			}
			catch (\Throwable $e)
			{
				log_message('error', 'Dashboard ' . $name . ': ' . $e->getMessage());
				$rankings[$name] = null;
			}
		endforeach;

		$totals = [];

		foreach (Dashboard::TOTALS as $name => $definition):
			if (array_diff($definition['requires'], array_keys($counts)) !== []):
				continue;
			endif;

			try
			{
				$provider = model($definition['model']);
				$totals[$name] = $provider->{$definition['method']}();
			}
			catch (\Throwable $e)
			{
				log_message('error', 'Dashboard ' . $name . ': ' . $e->getMessage());
				$totals[$name] = null;
			}
		endforeach;

		return ['counts' => $counts, 'rankings' => $rankings, 'totals' => $totals];
	}

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
