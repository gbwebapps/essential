<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\BaseModel;
use Config\Backend\Menu;

/** Fornisce i conteggi dei menu, limitandoli alle entità consultabili dall'utente. */
class MenuCountsModel extends BaseModel
{
    /**
     * Esegue un'unica interrogazione per le entità autorizzate, senza filtri CRUD.
     * Attivi, sospesi ed eliminati sono insiemi distinti.
     *
     * @return array<string, array{active: int, trashed: int, suspended: int}>
     */
    public function getCounts(?object $admin, ?string $entity = null): array
    {
        if ($admin === null):
            return [];
        endif;

        $queries = [];
        $bindings = [];

        foreach (config(Menu::class)->counterPermissions as $table => $permission):
            if ($entity !== null && $entity !== $table):
                continue;
            endif;

            $authorized = $table === 'admins'
                ? (int) ($admin->superadmin ?? 0) === 1
                : (($admin->permissions->all ?? false) === true || ($admin->permissions->{$permission} ?? false) === true);

            if ( ! $authorized):
                continue;
            endif;

            $queries[] = 'select ? as entity,
                                count(case when deleted_at is null and status = 1 then 1 end) as active,
                                count(case when deleted_at is not null then 1 end) as trashed,
                                count(case when deleted_at is null and status = 0 then 1 end) as suspended
                         from ' . $this->db->protectIdentifiers($table);
            $bindings[] = $table;
        endforeach;

        if ($queries === []):
            return [];
        endif;

        try
        {
            $query = $this->db->query(implode(' union all ', $queries), $bindings);

            if ($query === false):
                throw new \RuntimeException('Conteggi menu non disponibili.');
            endif;

            $counts = [];

            foreach ($query->getResultArray() as $row):
                $counts[$row['entity']] = [
                    'active' => (int) $row['active'],
                    'trashed' => (int) $row['trashed'],
                    'suspended' => (int) $row['suspended'],
                ];
            endforeach;

            return $counts;
        }
        catch (\Throwable $e)
        {
            /* Un contatore non disponibile non deve impedire l'apertura della pagina. */
            log_message('error', 'Conteggi menu non disponibili: ' . $e->getMessage());
            return [];
        }
    }
}
