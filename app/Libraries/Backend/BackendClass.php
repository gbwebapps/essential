<?php declare(strict_types = 1); 

namespace App\Libraries\Backend;

use App\Libraries\BaseClass;

/**
 * Classe core di supporto per l'elaborazione globale dell'interfaccia del pannello di controllo.
 * 
 * Richiamata direttamente dal BackendController, si occupa della gestione e della risoluzione 
 * delle dipendenze lato client (asset CSS e JS). Garantisce che gli script e i fogli di stile 
 * specifici delle singole sezioni vengano iniettati nell'esatta posizione gerarchica (DOM) 
 * rispetto agli asset strutturali di base, prevenendo conflitti di esecuzione e ottimizzando il caricamento.
 */
class BackendClass 
{
    /**
     * Fonde e ordina gerarchicamente gli asset strutturali (core) con le librerie specifiche della sezione attiva.
     * 
     * Il metodo elabora l'array degli asset aggiuntivi e ne calcola l'esatto punto di inserimento 
     * all'interno della lista principale basandosi sulle direttive 'target' (l'ID di riferimento) 
     * e 'position' (before o after). Sfrutta array_splice per un inserimento chirurgico che preserva 
     * l'integrità della catena di dipendenze (es. caricare un plugin locale subito dopo il suo core). 
     * Se il target richiesto non esiste, l'asset viene accodato in sicurezza (fallback).
     *
     * @param array $coreAssets La lista base degli script o dei fogli di stile strutturali del layout
     * @param array $customAssets Gli asset aggiuntivi richiesti dinamicamente dalla singola vista
     * @return array La lista definitiva e sequenzialmente ordinata, pronta per il rendering nel template
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
     * Metodo interno di utilità per l'individuazione dell'indice posizionale di uno specifico asset.
     * 
     * Scansiona in modo lineare la lista degli asset per rintracciare l'elemento corrispondente 
     * all'identificativo (ID) richiesto, restituendone la chiave numerica (zero-based). Questa ricerca 
     * è propedeutica al calcolo dell'offset matematico necessario all'inserimento del nuovo elemento.
     *
     * @param array $list L'array multidimensionale (core o parziale) in cui effettuare la ricerca
     * @param mixed $id L'identificativo stringa univoco dell'asset bersaglio (es. 'flatpickr-js')
     * @return mixed L'indice intero dell'elemento se individuato con successo, false in caso contrario
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