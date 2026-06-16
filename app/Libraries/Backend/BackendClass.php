<?php declare(strict_types = 1); 

namespace App\Libraries\Backend;

use App\Libraries\BaseClass;

/**
 * Componente di servizio per la gestione e l'organizzazione degli asset nel backend.
 *
 * Questa classe fornisce funzionalità helper isolate per manipolare le dipendenze dei file
 * JavaScript e CSS. Opera indipendentemente dal resto dell'architettura per garantire
 * la corretta sequenzialità di caricamento delle librerie nel layout dell'applicazione.
 */
class BackendClass 
{
    /**
     * Unisce e ordina in modo chirurgico gli asset addizionali rispetto alla lista dei componenti core.
     *
     * Cicla l'elenco degli asset personalizzati e, basandosi sulle direttive di posizionamento
     * relative (before/after) e sull'identificativo dell'asset target, inserisce dinamicamente
     * ogni elemento nella posizione corretta della pipeline. Qualora il target non venga rintracciato,
     * l'asset viene accodato in fondo alla lista per prevenirne la perdita.
     *
     * @param array $coreAssets Elenco degli asset di base predefiniti del sistema.
     * @param array $customAssets Elenco dei nuovi asset da iniettare contestualmente nella pagina.
     * @return array Lista globale degli asset ordinata secondo le dipendenze fornite.
     */
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

    /**
     * Ricerca l'indice numerico di un determinato asset all'interno della lista corrente.
     *
     * Scansiona l'array lineare degli asset mappati confrontando la proprietà identificativa univoca.
     * Restituisce l'indice intero della posizione nel DOM qualora venga individuato il riscontro,
     * oppure false nel caso in cui l'identificativo non sia presente.
     *
     * @param array $list L'elenco degli asset in cui eseguire la ricerca.
     * @param mixed $id L'identificativo univoco dell'asset da rintracciare.
     * @return mixed L'indice intero (int) dell'asset se trovato, altrimenti false.
     */
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