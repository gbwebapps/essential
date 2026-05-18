<?php declare(strict_types = 1); 

namespace App\Libraries\Backend;

use App\Libraries\BaseClass;

class BackendClass extends BaseClass 
{
    protected function initClass(): void
    {
        parent::initClass();
    }

    public function getOrderedAssets(array $coreAssets, array $customAssets): array
    {
        /* Se non ci sono asset extra, restituiamo i fissi così come sono */
        if (empty($customAssets)):
            return $coreAssets;
        endif;

        $orderedList = $coreAssets;

        foreach ($customAssets as $newAsset):

            /* Cerchiamo la posizione del target nell'array attuale */
            $targetId = $newAsset['target'] ?? null;
            $position = $newAsset['position'] ?? 'after';
            
            /* Troviamo l'indice numerico del target (es: 0, 1, 2...) */
            $targetIndex = $this->findAssetIndex($orderedList, $targetId);

            if ($targetIndex !== false):

                /* Calcoliamo dove inserire: se 'before' l'indice resta quello, se 'after' dobbiamo inserire all'indice successivo (+1) */
                $insertAt = ($position === 'before') ? $targetIndex : $targetIndex + 1;

                /* Eseguiamo l'inserimento chirurgico */
                array_splice($orderedList, $insertAt, 0, [$newAsset]);

            else:
                /* Se il target non esiste, lo mettiamo semplicemente in fondo */
                $orderedList[] = $newAsset;
            endif;

        endforeach;

        return $orderedList;
    }

    private function findAssetIndex(array $list, $id): mixed
    {
        foreach ($list as $index => $asset):

            if ($asset['id'] === $id):
                return $index;
            endif;

        endforeach;

        return false;
    }
}