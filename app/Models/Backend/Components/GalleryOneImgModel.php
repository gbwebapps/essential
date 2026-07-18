<?php declare(strict_types=1);

namespace App\Models\Backend\Components;

use CodeIgniter\Database\BaseConnection;
use App\Libraries\ImageFileSystemService;

use App\Models\Backend\BackendModel;

class GalleryOneImgModel extends BackendModel 
{
    private array $getImagesFields = ['entity', 'uuid'];

	private array $allowedDeleteFields = ['id', 'entity', 'uuid', 'filename'];

    private array $coverFields = ['id', 'entity', 'uuid'];

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
     * Recupera tutte le immagini associate a un'entità e al suo UUID.
     */
    public function getImages(array $posts): array
    {
        $posts = $this->checkAllowedFields($posts, $this->getImagesFields); 

        $sql = "select id, filename, is_cover from images where entity = ? and entity_uuid = ? order by is_cover desc, id DESC";
        return $this->db->query($sql, [$posts['entity'], $posts['uuid']])->getResultArray();
    }

    /**
     * Elimina il record dell'immagine dal database.
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
     * Imposta un'immagine come copertina, azzerando le altre della stessa entità.
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
     * Rimuove lo stato di copertina da una specifica immagine.
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