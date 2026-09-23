<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

class ToolsModel extends BackendModel
{
	protected array $manageAuditsAllowedFields = ['fromDate', 'toDate']; 

	protected array $manageLogsAllowedFields = ['fromDate', 'toDate']; 

	protected function initModel(): void 
	{
		parent::initModel();
	}

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

	public function validateManageLogsRules(): array
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

	public function countAuditsToDelete(array $posts): array|bool
    {
        $dates = $this->buildAuditDates($posts);

        if ($dates === false):
            return false;
        endif;

        $sql = 'select count(*) as total from admins_audits where created_at between ? and ?';
        $result = $this->db->query($sql, [$dates['from'], $dates['to']])->getRow();

        /* Restituiamo una struttura dati completa al Controller */
        return ['count' => (int) $result->total, 'from' => $dates['from'], 'to' => $dates['to']];
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

		$deleted = $this->db->affectedRows();

		if ($deleted > 0):

			$fromLog = convertDate($dates['from'], 'conversational');
	        $toLog = convertDate($dates['to'], 'conversational');

			$currentAdmin = service('authorization')->currentAdmin();
			log_admin_activity('DELETE_AUDITS', 'tools', sprintf(lang('backend/tools.audits.deleteAudits'), $fromLog, $toLog), $currentAdmin);

			return ['result' => true, 'message' => sprintf(lang('backend/tools.messages.deleteAuditsSuccess'), $deleted, $fromLog, $toLog)];

		endif;

		return ['result' => false, 'message' => lang('backend/tools.messages.noAuditsDeleted')];
	}

	public function getLogsStats(): array
	{
		$sql = 'select count(*) as total_logs, min(created_at) as min_date, max(created_at) as max_date from admins_logs';

		$result  = $this->db->query($sql)->getRow();
		
		$total = (int) ($result->total_logs ?? 0);

		/* Restituisce le date solo se esistono record nel database */
		return [
			'total' => $total,
			'min_date' => $total > 0 ? $result->min_date : null,
			'max_date' => $total > 0 ? $result->max_date : null,
		];
	}

	protected function buildLogDates(array $posts): array|bool
	{
		$posts = $this->checkAllowedFields($posts, $this->manageLogsAllowedFields);

		/* Normalizziamo la data e l'ora, lasciando che PHP gestisca i secondi in automatico */
		$from = $posts['fromDate'];
		$to = $posts['toDate'];

		/* Controllo logico: la data di inizio non può essere successiva alla fine */
		if (strtotime($from) > strtotime($to)):
			return false;
		endif;

		return ['from' => $from, 'to' => $to];
	}

	public function countLogsToDelete(array $posts): array|bool
    {
        $dates = $this->buildLogDates($posts);

        if ($dates === false):
            return false;
        endif;

        $sql = 'select count(*) as total from admins_logs where created_at between ? and ?';
        $result = $this->db->query($sql, [$dates['from'], $dates['to']])->getRow();

        /* Restituiamo una struttura dati completa al Controller */
        return ['count' => (int) $result->total, 'from' => $dates['from'], 'to' => $dates['to']];
    }

    public function deleteLogs(array $posts): array
    {
    	$dates = $this->buildLogDates($posts);

    	if ($dates === false):
    		return ['result' => false, 'message' => lang('backend/tools.messages.startDateAfterEndDate')];
    	endif;

    	/* Query di eliminazione diretta */
    	$sql = 'delete from admins_logs where created_at between ? and ?';

    	$this->db->query($sql, [$dates['from'], $dates['to']]);

    	$deleted = $this->db->affectedRows();

    	if ($deleted > 0):

    		$fromLog = convertDate($dates['from'], 'conversational');
            $toLog = convertDate($dates['to'], 'conversational');

    		$currentAdmin = service('authorization')->currentAdmin();
    		log_admin_activity('DELETE_LOGS', 'tools', sprintf(lang('backend/tools.audits.deleteLogs'), $fromLog, $toLog), $currentAdmin);

    		return ['result' => true, 'message' => sprintf(lang('backend/tools.messages.deleteLogsSuccess'), $deleted, $fromLog, $toLog)];

    	endif;

    	return ['result' => false, 'message' => lang('backend/tools.messages.noLogsDeleted')];
    }

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
		log_admin_activity('OPTIMIZE_DB', 'tools', sprintf(lang('backend/tools.audits.targetLog'), $targetLog), $currentAdmin);

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
        $path = WRITEPATH . 'backups/database/';
        $backups = [];

        /* Recupera tutti i file con estensione .zip */
        $files = glob($path . '*.zip');

        if ($files):
            foreach ($files as $file):

                $filename = basename($file);
                
                /* 1. Ottiene la data in formato MySQL (dal nome file o dal file system) */
                $mysqlDate = $this->extractDateFromFilename($filename);
                
                if ( ! $mysqlDate):
                    $mysqlDate = date('Y-m-d H:i:s', filemtime($file));
                endif;

                /* 2. Sfrutta l'helper per generare la stringa discorsiva completa */
                $humanDateTime = convertDate($mysqlDate, 'conversational');

                /* 3. Popola l'array */
                $backups[] = [
                    'filename'      => $filename,
                    'humanDateTime' => $humanDateTime,
                    'size'          => number_format(filesize($file) / 1048576, 2, ',', ''),
                    'time'          => filemtime($file)
                ];

            endforeach;

            /* Ordina l'array dal file più recente al più vecchio usando l'operatore astrale */
            usort($backups, function($a, $b) {
                return $b['time'] <=> $a['time'];
            });
        endif;

        return $backups;
    }

    private function extractDateFromFilename(string $filename): string|bool
    {
        /* Regex rigorosa: backup_YYYY-MM-DD_HH-MM-SS.zip */
        $pattern = '/^backup_(\d{4}-\d{2}-\d{2})_(\d{2}-\d{2}-\d{2})\.zip$/';
        
        if (preg_match($pattern, $filename, $matches)):
            
            $timeFormatted = str_replace('-', ':', $matches[2]);
            
            return $matches[1] . ' ' . $timeFormatted;
            
        endif;
        
        return false;
    }

	public function generateDatabaseBackups(): bool
	{
		$path = WRITEPATH . 'backups/database/';
		
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
		log_admin_activity('GENERATE_BACKUP', 'tools', sprintf(lang('backend/tools.audits.generateBackup'), $zipFilename), $currentAdmin);

		return true;
	}

	public function deleteBackups(string $filename): bool
	{
		/* basename protegge il percorso assicurando che sia solo il nome del file */
		$path = WRITEPATH . 'backups/database/' . basename($filename);
		
		if (file_exists($path) && is_file($path)):
			if (unlink($path)):
				
				/* Registrazione attività */
				$currentAdmin = service('authorization')->currentAdmin();
				log_admin_activity('DELETE_BACKUP', 'tools', sprintf(lang('backend/tools.audits.deleteBackup'), basename($filename)), $currentAdmin);
				
				return true;
			endif;
		endif;

		return false;
	}

	protected array $cleanableFolders = ['backups/database', 'backups/imports', 'cache', 'debugbar', 'exports', 'logs', 'session', 'uploads/staging'];

	public function getWritableFoldersStatus(): array
	{
	    $status = [];

	    foreach ($this->cleanableFolders as $folder):
	        $path = WRITEPATH . $folder;
	        $count = 0;

	        if (is_dir($path)):
	            $files = scandir($path);
	            foreach ($files as $file):
	                if ($file !== '.' && $file !== '..' && strtolower($file) !== 'index.html'):
	                    if (is_file($path . DIRECTORY_SEPARATOR . $file)):
	                        $count++;
	                    endif;
	                endif;
	            endforeach;
	        endif;

	        $status[] = [
	            'name'  => $folder,
	            'count' => $count
	        ];
	    endforeach;

	    return $status;
	}

	public function cleanWritableFolder(string $folder): array
	{
	    /* Validazione di Sicurezza (Whitelist) */
	    if ( ! in_array($folder, $this->cleanableFolders, true)):
	        return ['result' => false, 'message' => lang('backend/tools.messages.validationErrors')];
	    endif;

	    $path = WRITEPATH . $folder;
	    $deletedCount = 0;

	    if (is_dir($path)):
	        $files = scandir($path);
	        foreach ($files as $file):
	            if ($file !== '.' && $file !== '..' && strtolower($file) !== 'index.html'):
	                $filePath = $path . DIRECTORY_SEPARATOR . $file;
	                
	                /* Elimina solo se è un file (non tocca eventuali sottocartelle) */
	                if (is_file($filePath)):
	                    if (unlink($filePath)):
	                        $deletedCount++;
	                    endif;
	                endif;
	            endif;
	        endforeach;
	    endif;

	    /* Registrazione attività */
	    $currentAdmin = service('authorization')->currentAdmin();
	    log_admin_activity('CLEAN_' . strtoupper($folder), 'tools', sprintf(lang('backend/tools.audits.cleanFolder'), $folder, $deletedCount), $currentAdmin);

	    return ['result' => true, 'message' => sprintf(lang('backend/tools.messages.folderCleanSuccess'), $deletedCount, $folder)];
	}

	public function getSystemInfo(): array
	{
		return [
			'framework' => [
				'ci_version'  => \CodeIgniter\CodeIgniter::CI_VERSION,
				'environment' => ENVIRONMENT,
			],
			'local' => [
				'locale'      => service('request')->getLocale(),
				'timezone'    => date_default_timezone_get(),
			],
			'server' => [
				/* Recupera il sistema operativo, la release e l'architettura */
				'os'       => php_uname('s') . ' ' . php_uname('r') . ' (' . php_uname('m') . ')',
				'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Sconosciuto',
			],
			'php' => [
				'version'             => PHP_VERSION,
				'architecture'        => (PHP_INT_SIZE * 8) . '-bit',
				'sapi'                => php_sapi_name(),
				'memory_limit'        => ini_get('memory_limit'),
				'max_execution_time'  => ini_get('max_execution_time'),
				'upload_max_filesize' => ini_get('upload_max_filesize'),
				'max_file_uploads'    => ini_get('max_file_uploads'),
				'post_max_size'       => ini_get('post_max_size'),
				'max_input_vars'      => ini_get('max_input_vars'),
				'display_errors'      => ini_get('display_errors') ? 'On' : 'Off',
				'opcache'             => ini_get('opcache.enable') ? 'On' : 'Off',
			],
			'extensions' => [
				/* extension_loaded restituisce true/false in base alla presenza dell'estensione */
				'intl'     => extension_loaded('intl'),
				'mbstring' => extension_loaded('mbstring'),
				'curl'     => extension_loaded('curl'),
				'zip'      => extension_loaded('zip'),
				'gd'       => extension_loaded('gd'),
			]
		];
	}
}