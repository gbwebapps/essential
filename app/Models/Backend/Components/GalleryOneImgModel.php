<?php declare(strict_types=1);

namespace App\Models\Backend\Components;

use CodeIgniter\Database\BaseConnection;

use App\Libraries\ImageFileSystemService;

class GalleryOneImgModel 
{
	private BaseConnection $db;

	public function __construct()
	{
		$this->db = db_connect();
	}

    /**
     * Recupera tutte le immagini associate a un'entità e al suo UUID.
     */
    public function getImages(string $entity, string $entityUuid): array
    {
        $sql = "select id, filename, is_cover from images where entity = ? and entity_uuid = ? order by is_cover desc, id DESC";
        return $this->db->query($sql, [$entity, $entityUuid])->getResultArray();
    }

    /**
     * Elimina il record dell'immagine dal database.
     */
    public function deleteImage(int $id, string $entity, string $entityUuid, string $filename): bool
    {
        $sql = "delete from images where id = ? and entity = ? and entity_uuid = ?";
        $this->db->query($sql, [$id, $entity, $entityUuid]);

        /* Elimini il file fisico */
        ImageFileSystemService::removeSingleImage($entity, $entityUuid, $filename);

        return $this->db->affectedRows() > 0;
    }

    /**
     * Imposta un'immagine come copertina, azzerando le altre della stessa entità.
     */
    public function setCover(int $id, string $entity, string $entityUuid): bool
    {
        try {

            $this->db->transBegin();

            /* Azzera tutte le copertine per l'entità specifica */
            $sqlReset = "update images set is_cover = 0 where entity = ? and entity_uuid = ?";
            $this->db->query($sqlReset, [$entity, $entityUuid]);

            /* Imposta la nuova copertina */
            $sqlSet = "update images set is_cover = 1 where id = ? and entity = ? and entity_uuid = ?";
            $this->db->query($sqlSet, [$id, $entity, $entityUuid]);

            if ($this->db->transStatus() === false):
                $this->db->transRollback();
                return false;
            endif;

            $this->db->transCommit();
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
    public function removeCover(int $id, string $entity, string $entityUuid): bool
    {
        $sql = "update images set is_cover = 0 where id = ? and entity = ? and entity_uuid = ?";
        $this->db->query($sql, [$id, $entity, $entityUuid]);

        return $this->db->affectedRows() > 0;
    }
}