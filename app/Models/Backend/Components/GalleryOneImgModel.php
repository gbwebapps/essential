<?php declare(strict_types=1);

namespace App\Models\Backend\Components;

use App\Libraries\ImageFileSystemService;

use App\Models\Backend\BackendModel;

/**
 * Modello dedicato al Componente Globale per la gestione delle Immagini (GalleryOneImg).
 * 
 * Fornisce la business logic per il recupero, l'eliminazione fisica e logica, e la gestione 
 * delle immagini di copertina (cover) associate a una specifica entità del sistema 
 * (es. un singolo amministratore, un prodotto, un articolo).
 */
class GalleryOneImgModel extends BackendModel 
{
    /**
     * Whitelist dei campi consentiti per la richiesta di caricamento della galleria.
     * @var array 
     */
    private array $getImagesFields = ['entity', 'uuid'];

    /**
     * Whitelist dei campi consentiti per la richiesta di eliminazione di una singola immagine.
     * @var array 
     */
	private array $allowedDeleteFields = ['id', 'entity', 'uuid', 'filename'];

    /**
     * Whitelist dei campi consentiti per l'assegnazione o la rimozione di un'immagine di copertina.
     * @var array 
     */
    private array $coverFields = ['id', 'entity', 'uuid'];

    /**
     * Regole di validazione per la richiesta di estrazione delle immagini.
     * 
     * Verifica che il nome dell'entità (es. 'admins') contenga solo lettere e che l'UUID 
     * rispetti lo standard crittografico, prevenendo manipolazioni dei parametri 
     * durante le chiamate asincrone del componente.
     *
     * @return array Regole di validazione per i parametri strutturali
     */
    public function getImagesValidateFields()
    {
        return [
            'entity' => [
                'label' => lang('backend/components/galleryOneImg.labels.entity'),
                'rules' => ['required', 'alpha'],
            ],
            'uuid' => [
                'label' => lang('backend/components/galleryOneImg.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
            'context' => [
                'label' => lang('backend/components/galleryOneImg.labels.context'),
                'rules' => ['required', 'in_list[show,edit]'],
            ],
        ];
    }

    /**
     * Regole di validazione per l'eliminazione di un'immagine.
     * 
     * Controlla rigorosamente i parametri identificativi e valida il nome del file (`filename`) 
     * tramite un'espressione regolare chiusa. Questo impedisce in modo assoluto attacchi 
     * di tipo Path Traversal (es. tentare di iniettare stringhe come "../../../etc/passwd").
     *
     * @return array Regole strutturate
     */
    public function deleteImageValidateFields()
    {
        return [
            'id' => [
                'label' => lang('backend/components/galleryOneImg.labels.id'),
                'rules' => ['required', 'is_natural_no_zero'],
            ],
            'context' => [
                'label' => lang('backend/components/galleryOneImg.labels.context'),
                'rules' => ['required', 'in_list[show,edit]'],
            ],
            'entity' => [
                'label' => lang('backend/components/galleryOneImg.labels.entity'),
                'rules' => ['required', 'alpha'],
            ],
            'uuid' => [
                'label' => lang('backend/components/galleryOneImg.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
            'filename' => [
                'label' => lang('backend/components/galleryOneImg.labels.filename'),
                'rules' => ['required', 'regex_match[/^[A-Za-z0-9\-\_\(\)]+\.[A-Za-z]{3,4}$/]', 'max_length[255]'],
            ],
        ];
    }

    /**
     * Regole di validazione per la gestione dell'immagine di copertina.
     * 
     * Applica controlli stringenti sull'ID numerico del file e sui riferimenti all'entità 
     * per assicurarsi che il flag "is_cover" venga modificato solo su record legittimi.
     *
     * @return array Regole strutturate
     */
    public function coverValidateFields()
    {
        return [
            'id' => [
                'label' => lang('backend/components/galleryOneImg.labels.id'),
                'rules' => ['required', 'is_natural_no_zero'],
            ],
            'entity' => [
                'label' => lang('backend/components/galleryOneImg.labels.entity'),
                'rules' => ['required', 'alpha'],
            ],
            'uuid' => [
                'label' => lang('backend/components/galleryOneImg.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
            'context' => [
                'label' => lang('backend/components/galleryOneImg.labels.context'),
                'rules' => ['required', 'in_list[show,edit]'],
            ],
            'filename' => [
                'label' => lang('backend/components/galleryOneImg.labels.filename'),
                'rules' => ['required', 'regex_match[/^[A-Za-z0-9\-\_\(\)]+\.[A-Za-z]{3,4}$/]', 'max_length[255]'],
            ],
        ];
    }

    /**
     * Estrae l'elenco delle immagini attualmente associate a una specifica entità.
     * 
     * Filtra i dati in ingresso e interroga il database ordinando i risultati per logica di importanza: 
     * l'eventuale immagine di copertina (`is_cover = 1`) viene forzata al primo posto, 
     * seguita dalle restanti immagini ordinate dalla più recente alla più vecchia.
     *
     * @param array $posts Dati POST filtrati contenenti 'entity' e 'uuid'
     * @return array Elenco associativo con i dati delle immagini estratte
     */
    public function getImages(array $posts): array
    {
        $posts = $this->checkAllowedFields($posts, $this->getImagesFields); 

        $sql = "select id, filename, is_cover from images where entity = ? and entity_uuid = ? order by is_cover desc, id DESC";
        return $this->db->query($sql, [$posts['entity'], $posts['uuid']])->getResultArray();
    }

    /**
     * Elimina un'immagine sia a livello logico (Database) che fisico (Disco).
     * 
     * Esegue la query di DELETE mirata (vincolando ID, entità e UUID per estrema sicurezza). 
     * Subito dopo, invoca il servizio `ImageFileSystemService` per rimuovere fisicamente il file 
     * dalla cartella `uploads`, evitando l'accumulo di "file orfani" sul server. Infine registra 
     * l'azione nell'Audit Log.
     *
     * @param array $posts Dati essenziali per l'eliminazione (id, entity, uuid, filename)
     * @return bool True se l'eliminazione a database va a buon fine, false altrimenti
     */
    public function deleteImage(array $posts): bool
    {
        $posts = $this->checkAllowedFields($posts, $this->allowedDeleteFields);

        $sql = "delete from images where id = ? and entity = ? and entity_uuid = ?";
        $this->db->query($sql, [(int) $posts['id'], $posts['entity'], $posts['uuid']]);

        /* Elimini il file fisico */
        ImageFileSystemService::removeSingleImage($posts['entity'], $posts['uuid'], $posts['filename']);

        log_admin_activity('DELETE_IMAGE', 'gallery one', 'Eliminazione immagine.');

        return $this->db->affectedRows() > 0;
    }

    /**
     * Imposta un'immagine specifica come copertina principale (Transazionale).
     * 
     * Poiché per logica di business un'entità può avere una sola copertina, il metodo 
     * garantisce l'integrità dei dati aprendo una transazione: prima "spegne" il flag `is_cover` 
     * su tutte le immagini collegate a quell'UUID, poi lo "accende" esclusivamente sull'ID richiesto.
     * Se una delle due query fallisce, il Rollback annulla la modifica.
     *
     * @param array $posts I dati identificativi (id dell'immagine, entity, uuid proprietario)
     * @return bool True in caso di successo, false in caso di errore o fallimento della transazione
     */
    public function setCover(array $posts): bool
    {
        try {

            $posts = $this->checkAllowedFields($posts, $this->coverFields); 

            $this->db->transBegin();

            /* Azzera tutte le copertine per l'entità specifica */
            $sqlReset = "update images set is_cover = 0 where entity = ? and entity_uuid = ?";
            $this->db->query($sqlReset, [$posts['entity'], $posts['uuid']]);

            /* Imposta la nuova copertina */
            $sqlSet = "update images set is_cover = 1 where id = ? and entity = ? and entity_uuid = ?";
            $this->db->query($sqlSet, [(int) $posts['id'], $posts['entity'], $posts['uuid']]);

            if ($this->db->transStatus() === false):
                $this->db->transRollback();
                return false;
            endif;

            $this->db->transCommit();

            log_admin_activity('SET_COVER', 'gallery one', 'Impostazione cover.');

            return true;

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Errore impostazione copertina: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Rimuove il flag di copertina da una specifica immagine.
     * 
     * Esegue un UPDATE mirato riportando `is_cover` a 0. Lascia l'entità momentaneamente 
     * priva di un'immagine di copertina esplicita e registra l'evento nel log.
     *
     * @param array $posts I dati identificativi dell'immagine (id, entity, uuid)
     * @return bool True se la riga viene effettivamente modificata, false altrimenti
     */
    public function removeCover(array $posts): bool
    {
        $posts = $this->checkAllowedFields($posts, $this->coverFields); 

        $sql = "update images set is_cover = 0 where id = ? and entity = ? and entity_uuid = ?";
        $this->db->query($sql, [(int) $posts['id'], $posts['entity'], $posts['uuid']]);

        log_admin_activity('REMOVE_COVER', 'gallery one', 'Rimozione cover.');

        return $this->db->affectedRows() > 0;
    }
}