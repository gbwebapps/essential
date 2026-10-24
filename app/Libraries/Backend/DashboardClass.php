<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use App\Models\Backend\DashboardModel;
use Config\Backend\Dashboard;

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

	/** Carica solo nella dashboard gli stili dedicati ai riepiloghi e ai grafici. */
	public function getCssIndex(): array
	{
		return [['id' => 'dashboard-css', 'path' => 'assets/css/backend/dashboard.css']];
	}

	/** Formatta i conteggi secondo la lingua dell'interfaccia, senza decimali. */
	public function formatCount(int $count): string
	{
		return number_format($count, 0, '.', lang('backend/dashboard.formats.thousandsSeparator'));
	}

	/** Formatta gli importi decimali senza convertirli in float o supporre una valuta. */
	public function formatValue(string $value): string
	{
		[$integer, $decimals] = array_pad(explode('.', $value, 2), 2, '00');
		$grouped = preg_replace('/\B(?=(\d{3})+(?!\d))/', lang('backend/dashboard.formats.thousandsSeparator'), $integer);

		return $grouped . lang('backend/dashboard.formats.decimalSeparator') . str_pad(substr($decimals, 0, 2), 2, '0');
	}

	/** Prepara i quattro riepiloghi; quello degli amministratori è esclusivamente superadmin. */
	public function getCards(array $counts, ?object $admin): array
	{
		$cards = [];

		foreach (Dashboard::SECTIONS as $entity => $definition):
			if ( ! isset($counts[$entity]) || ($entity === 'admins' && (int) ($admin->superadmin ?? 0) !== 1)):
				continue;
			endif;

			$states = $counts[$entity];
			$total = $states['active'] + $states['suspended'] + $states['trashed'];
			$cards[$entity] = $definition + [
				'counts' => $states,
				'total' => $total,
				'activeEnd' => number_format($total > 0 ? $states['active'] / $total * 100 : 0, 4, '.', ''),
				'suspendedEnd' => number_format($total > 0 ? ($states['active'] + $states['suspended']) / $total * 100 : 0, 4, '.', ''),
			];
		endforeach;

		return $cards;
	}

	/** Calcola l'ampiezza relativa delle barre; non somma le associazioni molti-a-molti. */
	public function getRankings(array $rankings): array
	{
		$charts = [];

		foreach ($rankings as $entity => $rows):
			$definition = Dashboard::RANKINGS[$entity];
			$metric = $definition['metric'] ?? 'count';
			$maximum = $rows ? max(array_column($rows, $metric)) : 0;
			$items = [];

			foreach ($rows ?? [] as $row):
				$items[] = $row + ['share' => number_format($maximum > 0 ? $row[$metric] / $maximum * 100 : 0, 4, '.', '')];
			endforeach;

			$charts[$entity] = ['items' => $items, 'unavailable' => $rows === null, 'metric' => $metric, 'entity' => $definition['entity'] ?? $entity];
		endforeach;

		return $charts;
	}
}
