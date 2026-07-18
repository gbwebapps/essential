<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\BaseModel;

/**
 * Class BackendModel
 *
 * Modello astratto centrale per la gestione strutturata dei dati nel Backend.
 * Centralizza le operazioni di paginazione, ricerca tramite filtri dinamici,
 * whitelist dei campi per la sicurezza dei dati e tracciamento delle modifiche.
 */
abstract class BackendModel extends BaseModel
{
	/**
	 * Identificativo del modulo di backend corrente (es. 'admins').
	 * 
	 * @var string|null 
	 */
	protected ?string $module = null;

	protected ?string $entity = null;

	/**
	 * Query SQL predefinita per la selezione dei record principali.
	 *
	 * @var string|null
	 */
	protected ?string $getDataQuery = null;

	/**
	 * Query SQL per il recupero di un record tramite il suo UUID.
	 * 
	 * @var string|null 
	 */
	protected ?string $getUUIDQuery = null;

	/**
	 * Query SQL per il conteggio totale dei record presenti nel modulo.
	 * 
	 * @var string|null 
	 */
	protected ?string $getNumRowsQuery = null;

	/**
	 * Colonna di ordinamento predefinita applicata alle query.
	 * 
	 * @var string|null 
	 */
	protected ?string $defaultColumn = null;

	/**
	 * Elenco dei campi da confrontare per verificare se i dati hanno subito variazioni.
	 * 
	 * @var array 
	 */
	protected array $toCompare = [];

	/**
	 * Campi della tabella consentiti per la visualizzazione nell'elenco generale.
	 * 
	 * @var array 
	 */
	protected array $showAllAllowedFields = [];

	protected array $showAllAllowedDates = [];

	/**
	 * Campi della tabella consentiti durante l'operazione di inserimento (Add).
	 * 
	 * @var array 
	 */
	protected array $addAllowedFields = [];

	/**
	 * Campi della tabella consentiti durante l'operazione di modifica (Edit).
	 * 
	 * @var array 
	 */
	protected array $editAllowedFields = [];

	/**
	 * Campi della tabella consentiti per la gestione della cancellazione (Delete).
	 * 
	 * @var array 
	 */
	protected array $delAllowedFields = [];

	/**
	 * Campi della tabella consentiti per la variazione rapida dello stato (Status).
	 * 
	 * @var array 
	 */
	protected array $changeStatusAllowedFields = [];

	/**
	 * Colonne sulle quali il sistema permette l'ordinamento dei dati (Order BY).
	 *  
	 * @var array 
	 */
	protected array $allowedOrderColumns = [];

	/**
	 * Elenco dei campi su cui è autorizzata l'esecuzione di filtri di ricerca.
	 *  
	 * @var array 
	 */
	protected array $showAllSearchAllowedFields = [];
	
	/**
	 * Esegue l'inizializzazione del modello richiamando le connessioni del modello padre.
	 *
	 * @return void
	 */
	protected function initModel(): void 
	{
		parent::initModel();
		
		helper('audits');
	}

	/**
	 * Elabora l'estrazione paginata dei record applicando ordinamenti, filtri di ricerca e limiti.
	 *
	 * @param array $posts Parametri di input per la paginazione, l'ordinamento e i filtri.
	 * @return array Esito dell'operazione contenente i record estratti e la configurazione della paginazione.
	 */
	public function getData(array $posts): array
	{
		try
		{
			$posts = $this->checkAllowedFields($posts, array_merge($this->showAllAllowedFields, ['searchDates']));

			$params = [];
			$paramsFilter = [];

			$posts['order'] = (isset($posts['order']) && $posts['order'] === 'desc') ? 'asc' : 'desc';
			$posts['column'] = (isset($posts['column']) && in_array($posts['column'], $this->allowedOrderColumns)) ? $posts['column'] : $this->defaultColumn;

			$sql = $this->getDataQuery;

			if($this->module === 'admins'):
				$params[] = 1;
				$params[] = service('authorization')->currentAdmin()->uuid;
			endif;

			/* 1. Filtri di testo standard (Esistente) */
			if ( ! empty(array_filter($posts['searchFields']))):
				$sql .= $this->buildFilters($posts['searchFields'], $params);
				$paramsFilter['searchFields'] = $posts['searchFields'];
			endif;

			/* 2. NUOVO: Filtri per i range di date (Aggiunto) */
			if ( ! empty(array_filter($posts['searchDates']))):
				$sql .= $this->buildDateFilters($posts['searchDates'], $params);
				$paramsFilter['searchDates'] = $posts['searchDates'];
			endif;

			$sql .= ' order by ' . $posts['column'] . ' ' . $posts['order'];

			$page = (isset($posts['page']) && is_numeric($posts['page']) && $posts['page'] > 0) ? (int)$posts['page'] : 1;

			$recordsPerPage = (isset($posts['rows']) && is_numeric($posts['rows']) && $posts['rows'] > 0) ? min((int)$posts['rows'], 20) : 5;

			$offset = ($page - 1) * $recordsPerPage; 

			$sql .= ' limit ' . $offset . ', ' . $recordsPerPage;

			$records = $this->db->query($sql, $params)->getResult();

			$totalRows = $this->getNumRows($paramsFilter); 

			$lastItemPage = ($totalRows - $offset);

			$pagination = ['page' => $page, 'limit' => $recordsPerPage, 'totalRows' => $totalRows]; 

			return ['result' => true, 'records' => $records, 'pagination' => $pagination, 'lastItemPage' => $lastItemPage];

		} catch (\Throwable $e) {

			log_message('error', lang('backend/global.messages.getDataError') . ' - ' . $e);
			return ['result' => false, 'message' => lang('backend/global.messages.getDataError')];

		}
	}

	/**
	 * Calcola il numero totale di righe corrispondenti ai parametri di ricerca attivi.
	 *
	 * @param array $paramsFilter Array contenente i filtri di ricerca attivi.
	 * @return int Numero complessivo di record rilevati.
	 */
	private function getNumRows(array $paramsFilter): int
	{
		$params = [];

		$sql = $this->getNumRowsQuery;

		if($this->module === 'admins'):
			$params[] = 1;
			$params[] = service('authorization')->currentAdmin()->uuid;
		endif;

		if (isset($paramsFilter['searchFields']) && is_array($paramsFilter['searchFields'])):
			$sql .= $this->buildFilters($paramsFilter['searchFields'], $params);
		endif;

		if (isset($paramsFilter['searchDates']) && is_array($paramsFilter['searchDates'])):
			$sql .= $this->buildDateFilters($paramsFilter['searchDates'], $params);
		endif;

		return (int) $this->db->query($sql, $params)->getRow()->count;
	}

	/**
	 * Genera dinamicamente la stringa SQL dei filtri e mappa i parametri di binding.
	 *
	 * @param array $searchFields Campi di ricerca inviati dal client.
	 * @param array $params       Riferimento all'array dei parametri SQL per il binding (passato per riferimento).
	 * @return string La stringa SQL contenente le condizioni WHERE aggiuntive.
	 */
	private function buildFilters(array $searchFields, array &$params): string
	{
		$whereClause = '';

		foreach ($searchFields as $key => $val):
		    if (in_array($key, $this->showAllSearchAllowedFields)):
		        $whereClause .= " and " . $key . " like ?";
		        $params[] = "%$val%";
		    endif;
		endforeach;

		return $whereClause;
	}

	private function buildDateFilters(array $searchDates, array &$params): string
		{
			$whereClause = '';

			foreach ($this->showAllSearchAllowedDates as $dbColumn):
				/* 1. Controllo e binding per il limite inferiore (Da / >=) */
				$fromKey = $dbColumn . '-from';
				if (isset($searchDates[$fromKey]) && trim($searchDates[$fromKey]) !== ''):
					$whereClause .= " and " . $dbColumn . " >= ?";
					$params[] = $searchDates[$fromKey];
				endif;

				/* 2. Controllo e binding per il limite superiore (A / <=) */
				$toKey = $dbColumn . '-to';
				if (isset($searchDates[$toKey]) && trim($searchDates[$toKey]) !== ''):
					$whereClause .= " and " . $dbColumn . " <= ?";
					$params[] = $searchDates[$toKey];
				endif;
			endforeach;

			return $whereClause;
		}

	/**
	 * Recupera un singolo record specifico estraendolo tramite il valore UUID.
	 *
	 * @param string $uuid Identificativo univoco globale del record richiesto.
	 * @return array Esito dell'operazione combinato con l'oggetto del record o il messaggio di errore.
	 */
	public function getByUUID(string $uuid): array 
	{
	    try 
	    {
	        $row = $this->db->query($this->getUUIDQuery, [$uuid])->getRow();

	        if ( ! $row):
	            return ['result' => false, 'message' => lang('backend/global.messages.UUIDNotFound')];
	        endif;

	        /* Struttura di ritorno fissa, coerente e affidabile per tutti i moduli */
	        return ['result' => true, 'row' => $row];

	    } catch(\Throwable $e) {
	        log_message('error', lang('backend/global.messages.getUUIDError') . ' - ' . $e->getMessage());
	        return ['result' => false, 'message' => lang('backend/global.messages.getUUIDError')];
	    }
	}

	/**
	 * Determina se i dati inviati nel form contengono differenze rispetto al record originale del DB.
	 *
	 * @param array  $posts    Dati inviati per il salvataggio.
	 * @param object $original Oggetto del record originale memorizzato nel database.
	 * @return bool True se i dati o gli allegati differiscono dall'originale, altrimenti false.
	 */
	protected function hasDataChanged(array $posts, object $original): bool
	{
	    /* 1. Controllo dei campi nativi della tabella (Valido per TUTTI i moduli) */
        foreach ($this->toCompare as $field):
            
            /* Se il campo è presente nel POST, normalizziamo il confronto a stringa */
            if (isset($posts[$field])):
                
                /* Recuperiamo il valore originale gestendo il possibile NULL dal DB */
                $originalValue = $original->$field ?? '';

                /* Il cast a (string) azzera la differenza tra null e "" senza toccare i dati reali */
                if ((string)$posts[$field] !== (string)$originalValue):
                    return true;
                endif;

            endif;
        endforeach;

	    /* 2. Controllo dei file caricati (Valido per TUTTI i moduli che accettano allegati) */
	    foreach (['images'] as $type):
	        if (isset($posts[$type]) && is_array($posts[$type])):
	            foreach ($posts[$type] as $file):
	                if ($file instanceof \CodeIgniter\HTTP\Files\UploadedFile && $file->isValid() && ! $file->hasMoved()):
	                    return true;
	                endif;
	            endforeach;
	        endif;
	    endforeach;

	    return false;
	}

	protected function insertImages(array $filenames, string $uuid, string $entity, string $action = 'add'): void
	{
	    $dataImage = [];
	    $flag = false;

	    if ($action === 'edit'):
	        $sql = "select 1 from images where entity_uuid = ? and is_cover = ? and entity = ? limit 1";
	        $result = $this->db->query($sql, [$uuid, '1', $entity])->getRow();
	        $flag = $result ? true : false;
	    endif;

	    foreach ($filenames as $k => $v):
	        $dataImage[$k]['entity'] = $entity;
	        $dataImage[$k]['entity_uuid'] = $uuid;
	        $dataImage[$k]['filename'] = $v;
	        
	        if ($flag):
	            $dataImage[$k]['is_cover'] = '0';
	        else:
	            $dataImage[$k]['is_cover'] = ($k === 0) ? '1' : '0';
	        endif;
	    endforeach;

	    $placeholders = [];
	    $bind = [];
	    $now = date('Y-m-d H:i:s');

	    foreach ($dataImage as $row):
	        $placeholders[] = "(?, ?, ?, ?, ?)";
	        $bind[] = $row['entity'];
	        $bind[] = $row['entity_uuid'];
	        $bind[] = $row['filename'];
	        $bind[] = $row['is_cover'];
	        $bind[] = $now;
	    endforeach;

	    $sql = "insert into images (entity, entity_uuid, filename, is_cover, created_at) values " . implode(", ", $placeholders);
	    $this->db->query($sql, $bind);
	}

	/**
     * Rimozione ricorsiva di una directory e di tutto il suo contenuto.
     */
    protected function rrmdir(string $dir): void
    {
        if ( ! is_dir($dir)):
            return;
        endif;

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file):
            $full = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($full)):
                $this->rrmdir($full);
            else:
                @unlink($full);
            endif;
        endforeach;

        @rmdir($dir);
    }

	/**
	 * Genera un identificativo univoco crittograficamente sicuro conforme allo standard UUID versione 4.
	 *
	 * @return string Stringa formattata dell'UUID generato.
	 */
	protected function generateUUID(): string
	{
	    $data = random_bytes(16);

	    $data[6] = chr((ord($data[6]) & 0x0f) | 0b01000000);
	    $data[8] = chr((ord($data[8]) & 0x3f) | 0b10000000);

	    $hex = bin2hex($data);

	    return vsprintf('%08s-%04s-%04s-%04s-%12s', sscanf($hex, '%8s%4s%4s%4s%12s'));
	}

	/**
	 * Intercetta l'array di input e rimuove qualsiasi chiave non inclusa nella whitelist dei campi consentiti.
	 *
	 * @param array $posts         Insieme di dati grezzi in ingresso da ripulire.
	 * @param array $allowedFields Elenco dei soli campi autorizzati per l'operazione corrente.
	 * @return array L'array filtrato e sicuro per la manipolazione.
	 */
	protected function checkAllowedFields(array $posts, array $allowedFields): array
	{
	    foreach (array_keys($posts) as $key):
	        /* Rimuove il campo se non è presente nei campi consentiti */
	        if ( ! in_array($key, $allowedFields, true)):
	            unset($posts[$key]);
	        endif;
	    endforeach;

	    return $posts;
	}
}