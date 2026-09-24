<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

/**
 * Modello dedicato agli Strumenti di sistema (Tools).
 * 
 * Gestisce tutte le operazioni di manutenzione del pannello di controllo: pulizia massiva dei log 
 * e degli audit, ottimizzazione delle tabelle del database, generazione e rotazione dei backup, 
 * svuotamento delle cartelle temporanee (cache) e raccolta delle informazioni sul server.
 */
class ToolsModel extends BackendModel
{
	/**
	 * @var array Whitelist dei campi POST consentiti durante le operazioni di filtraggio o eliminazione degli Audit.
	 */
	protected array $manageAuditsAllowedFields = ['fromDate', 'toDate']; 

	/**
	 * @var array Whitelist dei campi POST consentiti durante le operazioni di filtraggio o eliminazione dei Log.
	 */
	protected array $manageLogsAllowedFields = ['fromDate', 'toDate']; 

	/**
	 * @var array Elenco (Whitelist) delle cartelle interne che possono essere svuotate in sicurezza (es. log, cache).
	 */
	protected array $cleanableFolders = ['backups/database', 'backups/imports', 'cache', 'debugbar', 'exports', 'logs', 'session', 'uploads/staging'];1

	/**
	 * Metodo di inizializzazione nativo di CodeIgniter.
	 * 
	 * Richiama il setup della classe genitore (BackendModel) per preparare le dipendenze di base.
	 */
	protected function initModel(): void 
	{
		parent::initModel();
	}

	/**
	 * Regole di validazione per i form di gestione degli Audit.
	 * 
	 * Verifica che l'utente abbia compilato le date di inizio (Da) e fine (A) e che 
	 * queste siano in un formato temporale valido per il database.
	 *
	 * @return array Regole native di CodeIgniter
	 */
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
	 * Regole di validazione per i form di gestione dei Log.
	 * 
	 * Verifica che le date di inizio e fine per la ricerca o cancellazione siano presenti e corrette.
	 *
	 * @return array Regole native di CodeIgniter
	 */
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

	/**
	 * Calcola le statistiche generali della tabella degli Audit.
	 * 
	 * Recupera con una singola query il numero totale dei record salvati e 
	 * identifica la data del primo e dell'ultimo evento registrato.
	 *
	 * @return array Statistiche (totale, data minima e data massima)
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

	/**
	 * Filtra e normalizza le date inviate dal form per la gestione degli Audit.
	 * 
	 * Applica la whitelist per sicurezza e controlla a livello logico che la data di partenza 
	 * non sia successiva a quella di fine, impedendo intervalli temporali impossibili (es. Da: 2026, A: 2024).
	 *
	 * @param array $posts I dati provenienti dal form (Da, A)
	 * @return array|bool Array con le date normalizzate, oppure false se l'intervallo non ha senso
	 */
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

	/**
	 * Conta quanti record di Audit rientrano nell'intervallo di date specificato.
	 * 
	 * Utile per mostrare all'operatore un'anteprima (es. "Stai per eliminare 150 record") 
	 * prima di procedere con la cancellazione irreversibile.
	 *
	 * @param array $posts Le date inviate dal form
	 * @return array|bool Struttura con il conteggio e le date validate, false in caso di errore
	 */
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

    /**
     * Elimina definitivamente i record di Audit compresi nel range temporale richiesto.
     * 
     * Esegue la cancellazione fisica (DELETE) sul database. Se l'operazione rimuove 
     * effettivamente dei record, converte le date in un formato leggibile e 
     * registra l'avvenuta pulizia nello storico di sistema.
     *
     * @param array $posts Le date inviate dal form
     * @return array Risposta con esito e messaggio per l'interfaccia
     */
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

	/**
	 * Calcola le statistiche generali della tabella dei Log di accesso.
	 * 
	 * Interroga il database per sapere quanti login/logout sono stati registrati 
	 * e da quanto tempo (data del log più vecchio e del log più recente).
	 *
	 * @return array Statistiche (totale, data minima e data massima)
	 */
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

	/**
	 * Filtra e normalizza le date inviate dal form per la gestione dei Log.
	 * 
	 * Verifica che l'intervallo temporale inserito (Da, A) abbia senso logico 
	 * e scarta eventuali campi non permessi.
	 *
	 * @param array $posts I dati provenienti dal form
	 * @return array|bool Array con le date normalizzate, oppure false in caso di incoerenza
	 */
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

	/**
	 * Conta quanti record di Log rientrano nell'intervallo di date specificato.
	 * 
	 * Fornisce il numero esatto dei record che verrebbero cancellati, permettendo 
	 * al controller di chiedere una conferma sicura all'utente.
	 *
	 * @param array $posts Le date inviate dal form
	 * @return array|bool Struttura con il conteggio totale, false in caso di errore
	 */
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

    /**
     * Elimina definitivamente i record di Log compresi nell'intervallo temporale.
     * 
     * Dopo aver rimosso i dati, genera una stringa esplicativa per l'Audit log, 
     * così da tracciare sempre chi ha svuotato lo storico e per quale periodo.
     *
     * @param array $posts Le date inviate dal form
     * @return array Esito e messaggio dell'operazione
     */
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

    /**
     * Interroga MySQL per ottenere lo stato fisico e lo spazio occupato dalle tabelle.
     * 
     * Calcola matematicamente le dimensioni dei dati e degli indici convertendole in Megabyte (MB). 
     * Calcola inoltre l'Overhead, ovvero lo spazio vuoto/frammentato che può essere recuperato 
     * ottimizzando la tabella.
     *
     * @param string|null $tableName Nome specifico di una tabella (opzionale, altrimenti le estrae tutte)
     * @return array Lista dettagliata delle tabelle con righe, peso (MB) e frammentazione
     */
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

	/**
	 * Avvia la manutenzione fisica del database (Deframmentazione).
	 * 
	 * Accetta una o più tabelle ed esegue in sequenza i comandi nativi MySQL: 
	 * ANALYZE (statistiche), CHECK (integrità) e OPTIMIZE (recupero spazio frammentato). 
	 * Una volta finito, restituisce lo stato aggiornato (pesi in MB) da mostrare all'utente.
	 *
	 * @param string|array $target Il nome della singola tabella o l'array di tutte le tabelle da ottimizzare
	 * @return array|bool Lo stato aggiornato della/e tabella/e elaborata/e
	 */
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

	/**
	 * Recupera i parametri essenziali di connessione al database attivo.
	 * 
	 * Utile per la pagina delle informazioni di sistema (mostra il nome del DB, 
	 * il driver PDO in uso e la versione del server MySQL/MariaDB).
	 *
	 * @return array Dati base del database
	 */
	public function getDatabase(): array
	{
		return [
			'dbName' => $this->db->getDatabase(), 
			'dbDriver' => $this->db->DBDriver, 
			'dbVersion' => $this->db->getVersion(),
		];
	}

	/**
	 * Esplora la cartella dei backup e restituisce la lista dei file disponibili.
	 * 
	 * Cerca tutti gli archivi `.zip`, ne formatta il peso in MB e recupera la data 
	 * esatta (leggendola dal nome del file). Ordina poi i risultati mostrando i backup 
	 * più recenti in cima alla lista.
	 *
	 * @return array Elenco strutturato dei file di backup pronti per il download o l'eliminazione
	 */
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

    /**
     * Funzione di utilità (Helper) che estrae data e ora leggendo il nome del file di backup.
     * 
     * Sfrutta una RegEx (espressione regolare) per analizzare file come "backup_2026-09-24_10-00-00.zip" 
     * e trasformarli in una data comprensibile per PHP (2026-09-24 10:00:00).
     *
     * @param string $filename Il nome del file da analizzare
     * @return string|bool La data formattata, oppure false se il nome non rispetta lo standard
     */
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

    /**
     * Genera un backup completo del database (struttura e dati) salvandolo su disco.
     * 
     * Operazione massiva: cicla tutte le tabelle, genera le query per ricrearle (CREATE TABLE) 
     * e per reinserire i dati (INSERT INTO), proteggendo stringhe e caratteri speciali.
     * Salva tutto in un file `.sql`, lo comprime in uno `.zip` per risparmiare spazio, 
     * cancella l'originale in chiaro ed elimina eventuali backup vecchi mantenendo solo gli ultimi 10.
     *
     * @return bool True se il file zip è stato creato con successo, false altrimenti
     */
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

	/**
	 * Elimina definitivamente un file di backup (.zip) dal server.
	 * 
	 * Scudo di sicurezza: utilizza basename() per isolare solo il nome del file, 
	 * ignorando eventuali percorsi completi. Questo blocca attacchi di "Path Traversal" 
	 * (tentativi di cancellare file fuori dalla cartella consentita usando stringhe come "../").
	 *
	 * @param string $filename Il nome del file zip da rimuovere
	 * @return bool True se eliminato, false se il file non esiste o l'operazione fallisce
	 */
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

	/**
	 * Scansiona le cartelle temporanee del server per capire quanti file contengono.
	 * 
	 * Cicla la whitelist delle directory (es. la cache di CodeIgniter), conta i file 
	 * al loro interno escludendo i file nascosti o di sistema (come index.html) e 
	 * restituisce la mappa completa all'interfaccia.
	 *
	 * @return array Lista delle cartelle analizzate con il relativo conteggio dei file
	 */
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

	/**
	 * Svuota fisicamente il contenuto di una specifica cartella di sistema.
	 * 
	 * Controlla prima che la directory richiesta sia presente nella whitelist per evitare 
	 * cancellazioni pericolose. Elimina solo i file (ignorando index.html e sottocartelle) 
	 * per liberare spazio su disco e registra l'avvenuta pulizia nell'audit log.
	 *
	 * @param string $folder Il nome della cartella da pulire (es. 'cache')
	 * @return array Esito dell'operazione e messaggio di riepilogo
	 */
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

	/**
	 * Aggrega e restituisce tutte le informazioni vitali del Server e del Framework.
	 * 
	 * Estrae dati fondamentali come la versione di PHP, la versione di CodeIgniter, 
	 * i limiti di memoria (es. memory_limit, upload_max_filesize), il sistema operativo 
	 * e lo stato delle estensioni indispensabili (come curl e zip).
	 *
	 * @return array Mappa strutturata con tutte le informazioni tecniche di sistema
	 */
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