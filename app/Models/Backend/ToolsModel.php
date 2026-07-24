<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

class ToolsModel extends BackendModel
{
	protected array $manageAuditsAllowedFields = ['fromDate', 'fromTime', 'toDate', 'toTime']; 

	protected function initModel(): void 
	{
		parent::initModel();
	}

	/* Regole di validazione per i nuovi campi HTML5 */
	public function validateManageAuditsRules(): array
	{
		return [
			'fromDate' => [
				'label' => lang('backend/tools.labels.dateFrom'), 
				'rules' => ['required', 'valid_date[Y-m-d]'], 
			], 
			'fromTime' => [
				'label' => lang('backend/tools.labels.hour'), 
				'rules' => ['required', 'valid_date[H:i:s]'], 
			],
			'toDate' => [
				'label' => lang('backend/tools.labels.dateTo'), 
				'rules' => ['required', 'valid_date[Y-m-d]'], 
			], 
			'toTime' => [
				'label' => lang('backend/tools.labels.hour'), 
				'rules' => ['required', 'valid_date[H:i:s]'], 
			],
		];
	}

	/**
	 * Recupera l'anno del primo audit registrato nel database.
	 *
	 * @return int Anno di partenza o anno corrente se non ci sono record.
	 */
	public function getMinAuditYear(): int
	{
		$sql = 'select min(created_at) as min_date from admins_audits';
		$result  = $this->db->query($sql)->getRow();

		if ( ! empty($result->min_date)):
			return (int) date('Y', strtotime($result->min_date));
		endif;

		return (int) date('Y');
	}

	/**
	 * Recupera le statistiche generali degli audit (totale, prima e ultima data).
	 *
	 * @return array
	 */
	public function getAuditsStats(): array
	{
		$sql = 'select count(*) as total_audits, min(created_at) as min_date, max(created_at) as max_date from admins_audits';

		$query  = $this->db->query($sql);
		$result = $query->getRow();
		
		$total = (int) ($result->total_audits ?? 0);

		/* Restituisce le date solo se esistono record nel database */
		return [
			'total'    => $total,
			'min_date' => $total > 0 ? $result->min_date : null,
			'max_date' => $total > 0 ? $result->max_date : null,
		];
	}

	/* Prepara e valida le date di inizio e fine intervallo */
	protected function buildAuditDates(array $posts): array|bool
	{
		$posts = $this->checkAllowedFields($posts, $this->manageAuditsAllowedFields);

		/* Normalizziamo la data e l'ora, lasciando che PHP gestisca i secondi in automatico */
		$from = date('Y-m-d H:i:s', strtotime($posts['fromDate'] . ' ' . $posts['fromTime']));
		$to   = date('Y-m-d H:i:s', strtotime($posts['toDate'] . ' ' . $posts['toTime']));

		/* Controllo logico: la data di inizio non può essere successiva alla fine */
		if (strtotime($from) > strtotime($to)):
			return false;
		endif;

		return ['from' => $from, 'to' => $to];
	}

	public function deleteAudits(array $posts): array
	{
		$dates = $this->buildAuditDates($posts);

		if ($dates === false):
			return ['result' => false, 'message' => lang('backend/tools.messages.startDateAfterEndDate')];
		endif;

		/* Query di eliminazione diretta */
		$sql = 'delete from admins_audits where created_at between ? and ?';

		$this->db->query($sql, [$dates['from'], $dates['to']]);

		if ($this->db->affectedRows() > 0):

			$fromLog = convertDate($dates['from']);
	        $toLog = convertDate($dates['to']);

			$currentAdmin = service('authorization')->currentAdmin();
			log_admin_activity('DELETE_AUDITS', 'tools', sprintf(lang('Eliminazione audits dal %s al %s'), $fromLog, $toLog), $currentAdmin);

			return ['result' => true, 'message' => lang('backend/tools.messages.deleteSuccess')];

		endif;

		return ['result' => false, 'message' => lang('backend/tools.messages.noAuditsDeleted')];
	}

	public function exportAudits(array $posts): array
	{
		$dates = $this->buildAuditDates($posts);

		if ($dates === false):
			return ['result' => false, 'message' => lang('backend/tools.messages.startDateAfterEndDate')];
		endif;

		/* Validazione e costruzione dinamica delle colonne richieste */
		$allowedColumns   = $this->getAuditColumns();
		$requestedColumns = $posts['columns'] ?? [];
		
		/* array_intersect garantisce che vengano passate alla query solo le colonne realmente esistenti */
		$validColumns = array_intersect($requestedColumns, $allowedColumns);

		if (empty($validColumns)):
			return ['result' => false, 'message' => lang('backend/tools.messages.validationErrors')];
		endif;

		$selectFields = implode(', ', $validColumns);

		/* Estrazione dei record dal database utilizzando le colonne filtrate */
		$sql = "select {$selectFields} from admins_audits where created_at between ? and ? order by created_at asc";
		$query = $this->db->query($sql, [$dates['from'], $dates['to']]);
		$records = $query->getResultArray();

		if (empty($records)):
			return ['result' => false, 'message' => lang('backend/tools.messages.noAuditsExported')];
		endif;

		/* Definizione percorso e nome file (con aggiunta di ore, minuti e secondi come da tua richiesta precedente) */
		$directory = WRITEPATH . 'exports/';
		
		if ( ! is_dir($directory)):
			mkdir($directory, 0755, true);
		endif;

		$fileName = 'export_audits_' . date('d_m_Y_H_i_s') . '.csv';
		$filePath = $directory . $fileName;

		/* Scrittura del file CSV */
		$file = fopen($filePath, 'w');

		/* Scrittura intestazioni colonne */
		fputcsv($file, array_keys($records[0]));

		/* Scrittura righe di dati */
		foreach ($records as $row):
			fputcsv($file, $row);
		endforeach;

		fclose($file);

		/* URL relativo per permettere al JS di chiamare il Controller e avviare il download */
		$downloadUrl = base_url('backend/tools/downloadExport/' . $fileName);

		$fromLog = convertDate($dates['from']);
        $toLog   = convertDate($dates['to']);
        
        $currentAdmin = service('authorization')->currentAdmin();
        log_admin_activity('EXPORT_AUDITS', 'tools', sprintf(lang('Esportazione audits dal %s al %s'), $fromLog, $toLog), $currentAdmin);

		return [
			'result' => true,
			'message' => lang('backend/tools.messages.exportSuccess'),
			'downloadUrl' => $downloadUrl,
		];
	}

	/* Recupera l'elenco delle colonne della tabella per il modale di esportazione */
	public function getAuditColumns(): array
	{
		return $this->db->getFieldNames('admins_audits');
	}
}