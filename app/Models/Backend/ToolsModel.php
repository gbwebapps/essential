<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

class ToolsModel extends BackendModel
{
	protected array $manageAuditsAllowedFields = ['fromDate', 'toDate']; 

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
				'rules' => ['required', 'valid_date[Y-m-d H:i:s]'], 
			], 
			'toDate' => [
				'label' => lang('backend/tools.labels.dateTo'), 
				'rules' => ['required', 'valid_date[Y-m-d H:i:s]'], 
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

		$result  = $this->db->query($sql)->getRow();
		
		$total = (int) ($result->total_audits ?? 0);

		/* Restituisce le date solo se esistono record nel database */
		return [
			'total' => $total,
			'min_date' => $total > 0 ? $result->min_date : null,
			'max_date' => $total > 0 ? $result->max_date : null,
		];
	}

	/* Prepara e valida le date di inizio e fine intervallo */
	protected function buildAuditDates(array $posts): array|bool
	{
		$posts = $this->checkAllowedFields($posts, $this->manageAuditsAllowedFields);

		/* Normalizziamo la data e l'ora, lasciando che PHP gestisca i secondi in automatico */
		$from = $posts['fromDate'];
		$to = $posts['toDate'];

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

	/* Recupera l'elenco delle colonne della tabella per il modale di esportazione */
	public function getAuditColumns(): array
	{
		return $this->db->getFieldNames('admins_audits');
	}

	/* Recupera le statistiche di una o di tutte le tabelle del database */
	public function getTablesStatus(?string $tableName = null): array
	{
		/* Se è presente un nome, filtriamo la query per quella specifica tabella */
		$sql = $tableName ? "show table status like ?" : "show table status";
		$params = $tableName ? [$tableName] : [];

		$tables = $this->db->query($sql, $params)->getResultArray();

		$result = [];

		foreach ($tables as $table) {
			/* Calcolo della dimensione totale (Dati + Indici) convertita in MB */
			$totalSizeBytes = $table['Data_length'] + $table['Index_length'];
			$sizeMB = round($totalSizeBytes / 1048576, 2);

			/* Calcolo dell'overhead (Spazio liberabile/frammentato) convertito in MB */
			$overheadBytes = $table['Data_free'];
			$overheadMB = round($overheadBytes / 1048576, 2);

			$result[] = [
				'name' => $table['Name'],
				'rows' => $table['Rows'] ?? 0,
				'size' => $sizeMB,
				'overhead' => $overheadMB,
				'engine' => $table['Engine']
			];
		}

		return $result;
	}

	/* Esegue l'ottimizzazione e restituisce i dati aggiornati */
	public function runOptimization(string|array $target): array|bool
	{
		/* Normalizziamo l'input in un array per processare le query */
		$tables = is_array($target) ? $target : [$target];

		foreach ($tables as $table):
			$escapedTable = $this->db->escapeIdentifiers($table);
			
			$this->db->query("ANALYZE TABLE {$escapedTable}");
			$this->db->query("CHECK TABLE {$escapedTable}");
			$this->db->query("OPTIMIZE TABLE {$escapedTable}");
		endforeach;

		/* Registrazione attività */
		$currentAdmin = service('authorization')->currentAdmin();
		$targetLog = is_string($target) ? "la tabella {$target}" : "tutte le tabelle del database";
		log_admin_activity('OPTIMIZE_DB', 'tools', sprintf('Eseguita ottimizzazione su %s', $targetLog), $currentAdmin);

		/* Se la richiesta era per una tabella singola, estraiamo solo quella */
		if (is_string($target)):
			return $this->getTablesStatus($target);
		endif;

		/* Se la richiesta era un array (tutte le tabelle), restituiamo l'intero database aggiornato */
		return $this->getTablesStatus();
	}

	public function getDatabase(): array
	{
		return [
			'dbName' => $this->db->getDatabase(), 
			'dbDriver' => $this->db->DBDriver, 
			'dbVersion' => $this->db->getVersion(),
		];
	}

	public function getBackups(): array
	{
		/* Definisce il percorso assoluto alla cartella backups di CodeIgniter */
		$path = WRITEPATH . 'backups/';
		$backups = [];

		/* Recupera tutti i file con estensione .zip */
		$files = glob($path . '*.zip');

		if ($files):
			foreach ($files as $file):

				/* Popola l'array con le informazioni fisiche del file */
				$backups[] = [
					'filename' => basename($file),
					'date' => date('d/m/Y H:i:s', filemtime($file)),
					'size' => number_format(filesize($file) / 1048576, 2, ',', ''),
					'time' => filemtime($file) /* Salviamo il timestamp grezzo per l'ordinamento */
				];

			endforeach;

			/* Ordina l'array dal file più recente al più vecchio usando l'operatore astrale */
			usort($backups, function($a, $b) {
				return $b['time'] <=> $a['time'];
			});
		endif;

		return $backups;
	}

	public function generateDatabaseBackups(): bool
	{
		$path = WRITEPATH . 'backups/';
		
		/* Crea la cartella se non dovesse esistere */
		if ( ! is_dir($path)):
			mkdir($path, 0775, true);
		endif;

		$date = date('Y-m-d_H-i-s');
		$sqlFilename = 'backup_' . $date . '.sql';
		$zipFilename = 'backup_' . $date . '.zip';
		
		$sqlPath = $path . $sqlFilename;
		$zipPath = $path . $zipFilename;

		/* 1. Generazione file SQL in puro PHP (Universale) */
		$fileHandler = fopen($sqlPath, 'w');
		if ($fileHandler === false):
			return false;
		endif;

		/* Intestazione del file SQL */
		fwrite($fileHandler, "/* Backup Database generato il " . date('Y-m-d H:i:s') . " */\n\n");
		fwrite($fileHandler, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

		/* Recupera tutte le tabelle */
		$tables = $this->db->listTables();

		foreach ($tables as $table):
			/* Salva la struttura della tabella */
			$query = $this->db->query("SHOW CREATE TABLE `{$table}`");
			$row = $query->getRowArray();
			
			fwrite($fileHandler, "DROP TABLE IF EXISTS `{$table}`;\n");
			fwrite($fileHandler, $row['Create Table'] . ";\n\n");

			/* Salva i dati della tabella */
			$query = $this->db->query("SELECT * FROM `{$table}`");
			$results = $query->getResultArray();

			if (count($results) > 0):
				foreach ($results as $dataRow):
					$values = [];
					foreach ($dataRow as $val):
						if (is_null($val)):
							$values[] = 'NULL';
						else:
							/* escape() protegge la stringa e aggiunge automaticamente gli apici */
							$values[] = $this->db->escape($val);
						endif;
					endforeach;
					
					$sqlInsert = "INSERT INTO `{$table}` VALUES(" . implode(', ', $values) . ");\n";
					fwrite($fileHandler, $sqlInsert);
				endforeach;
				fwrite($fileHandler, "\n");
			endif;
		endforeach;

		/* Chiusura file SQL */
		fwrite($fileHandler, "SET FOREIGN_KEY_CHECKS = 1;\n");
		fclose($fileHandler);

		/* 2. Compressione del file in formato ZIP */
		$zip = new \ZipArchive();
		
		if ($zip->open($zipPath, \ZipArchive::CREATE) === true):
			$zip->addFile($sqlPath, $sqlFilename);
			$zip->close();
		else:
			/* Pulizia in caso di errore di compressione */
			unlink($sqlPath);
			return false;
		endif;

		/* 3. Eliminazione del file SQL in chiaro */
		if (file_exists($sqlPath)):
			unlink($sqlPath);
		endif;

		/* 4. Rotazione Backup: manteniamo solo gli ultimi 10 file */
		$files = glob($path . '*.zip');
		
		if (is_array($files) && count($files) > 10):
			
			/* Ordina i file dal più vecchio al più recente */
			usort($files, function($a, $b) {
				return filemtime($a) <=> filemtime($b);
			});
			
			$filesToDelete = count($files) - 10;
			
			for ($i = 0; $i < $filesToDelete; $i++):
				if (file_exists($files[$i])):
					unlink($files[$i]);
				endif;
			endfor;
			
		endif;

		/* Registrazione attività */
		$currentAdmin = service('authorization')->currentAdmin();
		log_admin_activity('GENERATE_BACKUP', 'tools', sprintf('Generato nuovo backup del database: %s', $zipFilename), $currentAdmin);

		return true;
	}

	public function deleteBackups(string $filename): bool
	{
		/* basename protegge il percorso assicurando che sia solo il nome del file */
		$path = WRITEPATH . 'backups/' . basename($filename);
		
		if (file_exists($path) && is_file($path)):
			if (unlink($path)):
				
				/* Registrazione attività */
				$currentAdmin = service('authorization')->currentAdmin();
				log_admin_activity('DELETE_BACKUP', 'tools', sprintf('Eliminato backup del database: %s', basename($filename)), $currentAdmin);
				
				return true;
			endif;
		endif;

		return false;
	}
}