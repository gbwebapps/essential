<?php declare(strict_types = 1);

namespace Modules\Backend\Warehouse\Models;

use App\Models\Backend\BackendModel;
use Modules\Backend\Warehouse\Config\Package;

/**
 * Definisce il perimetro dati del pacchetto magazzino.
 */
class WarehouseModel extends BackendModel
{
    /**
     * Restituisce le tabelle nell'ordine previsto per il pacchetto XLSX.
     *
     * @return array<int, string>
     */
    public function getPackageTables(): array
    {
        return Package::TABLES;
    }

    /* ESPORTAZIONE XLSX -------------------------------------------------------------------------- */

    /**
     * Restituisce intestazioni e record delle cinque tabelle del pacchetto.
     *
     * Le colonne seguono lo schema di scambio autorizzato, senza date tecniche.
     * Un'unica transazione di lettura mantiene coerenti tutti i fogli.
     *
     * @return array<string, array{headers: array<int, string>, rows: array<int, array<string, mixed>>}>
     */
    public function getPackageData(): array
    {
        if ($this->db->transDepth !== 0 || $this->db->query('set transaction isolation level repeatable read') === false || ! $this->db->transBegin()):
            throw new \RuntimeException('Impossibile acquisire lo snapshot di esportazione Warehouse.');
        endif;

        try
        {
            $package = $this->readPackageData();

            if ( ! $this->db->transStatus() || ! $this->db->transCommit()):
                throw new \RuntimeException('Snapshot di esportazione Warehouse non completato.');
            endif;

            return $package;
        }
        catch (\Throwable $e)
        {
            $this->db->transRollback();
            throw $e;
        }
    }

    /** Restituisce i cinque fogli vuoti con intestazioni ricavate dallo schema autorizzato. */
    public function getPackageTemplate(): array
    {
        $package = [];

        foreach (model(WarehouseImportModel::class)->getPackageImportStructures() as $table => $structure):
            $package[$table] = ['headers' => array_column($structure, 'name'), 'rows' => []];
        endforeach;

        return $package;
    }

    /**
     * Legge solo entità non cestinate e associazioni fra entità incluse nello snapshot.
     * @return array<string, array{headers: array<int, string>, rows: array<int, array<string, mixed>>}>
     */
    private function readPackageData(): array
    {
        $package = $this->getPackageTemplate();

        foreach (Package::TABLES as $table):
            $columns = $package[$table]['headers'];
            $query = $this->db
                ->table($table)
                ->select(array_map(static fn(string $column): string => $table . '.' . $column, $columns))
                ->orderBy($table . '.' . $columns[0], 'asc');

            if (isset(Package::PIVOTS[$table])):
                foreach (Package::PIVOTS[$table] as $field => $entity):
                    $query->join($entity, $entity . '.id = ' . $table . '.' . $field);
                    $query->where($entity . '.deleted_at', null);
                endforeach;

                $query->orderBy($table . '.product_id', 'asc');
            else:
                $query->where($table . '.deleted_at', null);
            endif;

            $sql = $query->getCompiledSelect();
            $result = $this->db->query($sql);

            if ($result === false):
                throw new \RuntimeException('Lettura del pacchetto Warehouse non riuscita.');
            endif;

            $package[$table] = [
                'headers' => $columns,
                'rows' => $result->getResultArray(),
            ];
        endforeach;

        return $package;
    }

}
