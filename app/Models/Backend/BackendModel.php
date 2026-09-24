<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\BaseModel;

/**
 * Modello Astratto Fondamentale (Base Class) per la gestione logica e le transazioni CRUD dell'intero Backend.
 * 
 * Estende il BaseModel nativo e funge da "blueprint" architetturale per tutti i modelli di modulo (es. AdminsModel). 
 * Centralizza e astrae logiche complesse e ripetitive quali: l'estrazione dati con paginazione (DataTables), 
 * l'iniezione dinamica e sicura di filtri temporali e testuali, la protezione anti Mass-Assignment (tramite Whitelist), 
 * la risoluzione dell'identità (UUID), il soft delete (Cestino) e l'elaborazione massiva (Bulk Insert) delle immagini.
 */
abstract class BackendModel extends BaseModel
{
	/**
	 * @var string|null Nome identificativo del modulo di pertinenza (es. 'admins', 'logs'). 
	 * Utilizzato a runtime per individuare la tabella di riferimento, la risoluzione dinamica delle colonne, 
	 * per l'applicazione dei prefissi nelle query (prevenendo ambiguità nelle JOIN) e per il partizionamento strutturale dei percorsi.
	 */
	protected ?string $module = null;

	/**
	 * @var bool Definisce se l'entità corrente supporta la cancellazione logica (Soft Delete). 
	 * Se true, il motore di estrazione inietterà in automatico i filtri protettivi sulla colonna 'deleted_at'.
	 */
	protected bool $hasSoftDelete = false;

	/**
	 * @var string|null Costrutto SQL nativo preparato per l'estrazione paginata dei record (metodo getData).
	 */
	protected ?string $getDataQuery = null;

	/**
	 * @var string|null Costrutto SQL nativo preparato per la singola estrazione tramite identificatore (metodo getByUUID).
	 */
	protected ?string $getUUIDQuery = null;

	/**
	 * @var string|null Costrutto SQL nativo (COUNT) indispensabile per il calcolo matematico della paginazione (metodo getNumRows).
	 */
	protected ?string $getNumRowsQuery = null;

	/**
	 * @var string|null La colonna di default a cui ricorrere per l'ordinamento (ORDER BY) in assenza di direttive del client.
	 */
	protected ?string $defaultColumn = null;

	/**
	 * @var array Elenco dei campi tabella soggetti al controllo euristico di mutazione dati (metodo hasDataChanged).
	 */
	protected array $toCompare = [];

	/**
	 * @var array Insieme di proprietà Whitelist (anti Mass-Assignment e SQL Injection strutturale).
	 * Ciascun array ($showAllAllowedFields, $addAllowedFields,$editAllowedFields, ecc.) stabilisce in modo 
	 * rigoroso e contestuale il perimetro esclusivo delle chiavi ammesse nelle operazioni HTTP POST, 
	 * impedendo la manipolazione non autorizzata dei payload e l'ordinamento su colonne protette.
	 */
	protected array $showAllAllowedFields = [];
	protected array $showAllAllowedDates = [];
	protected array $addAllowedFields = [];
	protected array $editAllowedFields = [];
	protected array $delAllowedFields = [];
	protected array $changeStatusAllowedFields = [];
	protected array $allowedOrderColumns = [];
	protected array $showAllSearchAllowedFields = [];

	/**
	 * Hook nativo di CodeIgniter 4, invocato automaticamente durante l'inizializzazione dell'istanza.
	 * 
	 * Invoca il costruttore padre per predisporre la connessione al database e carica preventivamente 
	 * l'helper globale 'audits', garantendo a tutti i modelli figli l'immediata disponibilità 
	 * delle funzioni di tracciamento (log_admin_activity).
	 */
	protected function initModel(): void 
	{
		parent::initModel();
		
		helper('audits');
	}

	/**
	 * Motore principale per l'estrazione paginata, ordinata e filtrata dei dataset (DataTables e Liste).
	 * 
	 * Svolge il lavoro pesante incapsulando: sanitizzazione del payload tramite Whitelist, validazione 
	 * rigorosa delle colonne di ordinamento e calcolo degli offset matematici (LIMIT/OFFSET). 
	 * Compila dinamicamente la stringa SQL finale iniettando costrutti protetti per il filtro Cestino, 
	 * i parametri di ricerca testuale (LIKE) e i range di date. Interroga infine getNumRows() passando 
	 * i filtri esatti per garantire la perfetta coerenza totale/risultati.
	 *
	 * @param array $posts Il payload POST strutturato proveniente dall'interfaccia client
	 * @return array Struttura standardizzata comprendente l'esito (result), il blocco record, i dati di paginazione e l'offset ultimo
	 */
	public function getData(array $posts): array
	{
		try
		{
			/* Whitelist estesa: aggiunto 'trash_filter' ai parametri consentiti */
			$posts = $this->checkAllowedFields($posts, array_merge($this->showAllAllowedFields, ['searchDates', 'trash_filter']));

			$params = [];
			$paramsFilter = [];

			$posts['order'] = (isset($posts['order']) && $posts['order'] === 'desc') ? 'asc' : 'desc';
			$posts['column'] = (isset($posts['column']) && in_array($posts['column'], $this->allowedOrderColumns)) ? $posts['column'] : $this->defaultColumn;

			$sql = $this->getDataQuery;

			/* Filtro Cestino basato sulla proprietà module */
			$trashStatus = (isset($posts['trash_filter']) && in_array($posts['trash_filter'], ['active', 'trashed', 'all'])) ? $posts['trash_filter'] : 'active';
			$sql .= $this->buildTrashFilter($trashStatus, $this->module, $this->hasSoftDelete);
			$paramsFilter['trash_filter'] = $trashStatus;

			/* 1. Filtri di testo standard */
			if ( ! empty(array_filter($posts['searchFields']))):
				$sql .= $this->buildFilters($posts['searchFields'], $params);
				$paramsFilter['searchFields'] = $posts['searchFields'];
			endif;

			/* 2. Filtri per i range di date */
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

			/* Passaggio dei parametri completi al metodo di conteggio */
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
	 * Calcola in modo chirurgico il numero totale dei record che soddisfano la query corrente.
	 * 
	 * Metodo ancillare, rigorosamente accoppiato a getData(). Elabora l'identico set di parametri e filtri 
	 * ($paramsFilter) sfruttando query preparate, garantendo al motore di paginazione frontend 
	 * di conoscere l'esatto volume di dati indipendentemente dai limiti della singola "pagina" SQL.
	 *
	 * @param array $paramsFilter Array contenente i flag e i valori validati (trash_filter, searchFields, searchDates)
	 * @return int Il conteggio intero dei record validi estratti dal database
	 */
	private function getNumRows(array $paramsFilter): int
	{
		$params = [];
		$sql = $this->getNumRowsQuery;

		/* 0. Filtro Cestino basato sulla proprietà module (Sincronizzato) */
		$trashStatus = isset($paramsFilter['trash_filter']) ? $paramsFilter['trash_filter'] : 'active';
		$sql .= $this->buildTrashFilter($trashStatus, $this->module, $this->hasSoftDelete);

		if (isset($paramsFilter['searchFields']) && is_array($paramsFilter['searchFields'])):
			$sql .= $this->buildFilters($paramsFilter['searchFields'], $params);
		endif;

		if (isset($paramsFilter['searchDates']) && is_array($paramsFilter['searchDates'])):
			$sql .= $this->buildDateFilters($paramsFilter['searchDates'], $params);
		endif;

		return (int) $this->db->query($sql, $params)->getRow()->count;
	}

	/**
	 * Costruisce in modo dinamico i costrutti logici SQL (LIKE) per la ricerca testuale (Full Search).
	 * 
	 * Processa l'array di ricerca confrontando ogni chiave con la whitelist dedicata ($showAllSearchAllowedFields). 
	 * Se il campo è autorizzato, concatena la stringa SQL e appende il valore mascherato (%) per riferimento 
	 * nell'array dei parametri ($params), delegando la protezione contro l'Injection al driver PDO.
	 *
	 * @param array $searchFields Valori di ricerca estratti dal payload del client
	 * @param array &$params Riferimento all'array PDO per il data binding
	 * @return string Porzione di query SQL formattata (es. " AND table.column LIKE ?")
	 */
	private function buildFilters(array $searchFields, array &$params): string
	{
		$whereClause = '';

		foreach ($searchFields as $key => $val):
		    if (in_array($key, $this->showAllSearchAllowedFields)):
		        $whereClause .= " and " . $this->module . '.' . $key . " like ?";
		        $params[] = "%$val%";
		    endif;
		endforeach;

		return $whereClause;
	}

	/**
	 * Genera i costrutti logici operazionali (>= e <=) per il partizionamento e filtraggio temporale.
	 * 
	 * Basandosi sulla whitelist delle colonne Data ammesse ($showAllSearchAllowedDates), intercetta 
	 * convenzionalmente i suffissi di input '-from' e '-to'. Gestisce range temporali parziali o totali, 
	 * accodando i valori referenziati all'array di bind per preservare l'integrità strutturale dell'interrogazione.
	 *
	 * @param array $searchDates Valori temporali (date/datetime) estratti dal payload del client
	 * @param array &$params Riferimento all'array PDO per il data binding
	 * @return string Porzione di query SQL formattata (es. " AND table.date_col >= ? AND table.date_col <= ?")
	 */
	private function buildDateFilters(array $searchDates, array &$params): string
	{
		$whereClause = '';

		foreach ($this->showAllSearchAllowedDates as $dbColumn):
			/* 1. Controllo e binding per il limite inferiore (Da / >=) */
			$fromKey = $dbColumn . '-from';
			if (isset($searchDates[$fromKey]) && trim($searchDates[$fromKey]) !== ''):
				$whereClause .= " and " . $this->module . '.' . $dbColumn . " >= ?";
				$params[] = $searchDates[$fromKey];
			endif;

			/* 2. Controllo e binding per il limite superiore (A / <=) */
			$toKey = $dbColumn . '-to';
			if (isset($searchDates[$toKey]) && trim($searchDates[$toKey]) !== ''):
				$whereClause .= " and " . $this->module . '.' . $dbColumn . " <= ?";
				$params[] = $searchDates[$toKey];
			endif;
		endforeach;

		return $whereClause;
	}

	/**
	 * Implementa la logica a scudo (Shield) per l'isolamento dei record sottoposti a cancellazione logica.
	 * 
	 * La funzione analizza lo stato richiesto, rimuove proattivamente i caratteri anomali dal nome tabella via Regex 
	 * per impedire injection sulla stringa. Include un blocco di salvaguardia: se il modulo non possiede 
	 * un cestino logico ($hasSoftDelete = false) restituisce una stringa vuota, prevenendo eccezioni SQL per colonne inesistenti. 
	 * Il comportamento di fallback obbligatorio (active) sigilla l'esposizione dei record cancellati.
	 *
	 * @param string $filter Lo stato di visibilità richiesto (active, trashed, all)
	 * @param string $table Il nome stringa della tabella di riferimento (sanitizzato internamente)
	 * @param bool $hasSoftDelete Flag strutturale sulla persistenza logica (default true)
	 * @return string Clausola condizionale SQL mirata (es. " AND table.deleted_at IS NULL") o vuota
	 */
	private function buildTrashFilter(string $filter, string $table, bool $hasSoftDelete = true): string
	{
	    /* Sanitizzazione rigorosa del nome tabella per prevenire SQL injection strutturali */
	    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

	    /* Scudo strutturale: se la tabella non possiede la colonna deleted_at, la logica viene aggirata */
	    if ($hasSoftDelete === false):
	        return "";
	    endif;

	    if ($filter === 'trashed'):
	        return " and {$table}.deleted_at IS NOT NULL";
	    endif;

	    if ($filter === 'all'):
	        return ""; /* Nessuna restrizione, restituisce l'intero storico */
	    endif;

	    /* Comportamento enterprise di default (active): blocco rigoroso dei record cestinati */
	    return " and {$table}.deleted_at IS NULL";
	}

	/**
	 * Recupera un singolo record dal database avvalendosi dell'Identificatore Univoco Universale (UUID).
	 * 
	 * Sfrutta la query nativa fornita dal modulo figlio (getUUIDQuery), avvolgendo l'interrogazione in 
	 * un blocco Try/Catch per catturare silenziando disallineamenti gravi a database. Restituisce una struttura 
	 * di array fissa e immutabile, garantendo al BackendController un pattern di risposta standard per l'elaborazione.
	 *
	 * @param string $uuid La stringa alfanumerica di 36 caratteri (Identificatore)
	 * @return array Array associativo contenente un booleano (result) e l'oggetto dati (row) in caso di query fortunata
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
	 * Esegue una validazione euristica delle differenze per l'ottimizzazione degli UPDATE transazionali.
	 * 
	 * Effettua un'analisi comparativa tra il payload POST in ingresso e l'oggetto originario prelevato dal DB. 
	 * Castando temporaneamente entrambi i poli di confronto a stringa, neutralizza le divergenze puramente sintattiche 
	 * tra NULL e stringhe vuote. Effettua uno scanning secondario sulle chiavi di tipo 'images' per rilevare 
	 * l'esistenza di operazioni multimediali in pendenza, ritornando true al primo differenziale rilevato per limitare il dispendio CPU.
	 *
	 * @param array $posts Dataset sanificato sottomesso via HTTP POST
	 * @param object $original L'oggetto rappresentativo dell'istanza fisica residente nel database
	 * @return bool True se è emersa un'effettiva mutazione logica (testo o file immessi), false viceversa
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

	/**
	 * Orchestratore logico per l'inserimento massivo (Bulk Insert) delle dipendenze fotografiche (Images).
	 * 
	 * Metodo ad elevata efficienza che aggrega tuple in una singola query preparata, minimizzando le latenze I/O con il database. 
	 * Analizza il contesto operativo ('add' o 'edit') allocando in modo automatico l'attributo 'is_cover' (copertina) 
	 * alla prima immagine dell'array iterato, qualora un query lookup preventivo certifichi l'assenza di copertine attuali.
	 *
	 * @param array $filenames Array monolitico contenente le stringhe alfanumeriche dei file già consolidati su disco
	 * @param string $uuid L'identificatore univoco del record padrone (proprietario della galleria)
	 * @param string $entity Lo slug/nome dell'entità proprietaria associata (es. 'admins')
	 * @param string $action Parametro comportamentale ('add' per prima allocazione, 'edit' per accodamento)
	 */
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
	 * Algoritmo File System distruttivo per la rimozione ricorsiva di cartelle ad albero (RRMDIR).
	 * 
	 * Implementazione a basso livello progettata per disintegrare un'intera directory e le sue foglie 
	 * (es. al momento dell'eliminazione fisica di un utente o entità). Scandisce gli inode e si richiama ricorsivamente. 
	 * Fa uso intensivo del soppressore d'errore `@` di PHP per ammortizzare eccezioni fatali derivanti 
	 * da file in stato "Locked" (OS I/O block) o transitorie indisponibilità di permessi CHMOD.
	 *
	 * @param string $dir Percorso assoluto root della directory bersaglio sul server
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
     * Fabbrica crittograficamente sicura per la generazione di Identificatori Univoci Universali (UUID versione 4).
     * 
     * Ricorre all'API CSPRNG nativa di PHP (random_bytes) per produrre un pool entropico a 16 byte. 
     * Modifica bit a bit i metadati (AND/OR logici) per conformare la sequenza al rigoroso standard RFC 4122 (versione 4, variante). 
     * Converte infine il buffer binario in esadecimale stringa con trattini (hyphen).
     *
     * @return string Stringa formattata (es. 'b5e5a210-9e63-4a1c-99a3-5c8e3d304ec8')
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
	 * Scudo Architetturale Anti Mass-Assignment per la disinfezione dell'input HTTP POST.
	 * 
	 * Filtra e decostruisce l'array dei parametri in ingresso basandosi severamente su una Whitelist fornita come matrice. 
	 * Utilizzando in_array in modalità strict, sgancia ed esegue l'unset su qualsiasi chiave POST arbitraria o malevola 
	 * introdotta per forzare alterazioni su colonne sensibili del database (es. iniezione del campo superadmin o permessi).
	 *
	 * @param array $posts Array grezzo o parzialmente manipolato prelevato dalla request HTTP
	 * @param array $allowedFields Array monolitico (Whitelist) dei nodi (chiavi) consentite per il ciclo vitale attuale
	 * @return array L'array bonificato e compattato, pronto per i layer Data Access o Validazione
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