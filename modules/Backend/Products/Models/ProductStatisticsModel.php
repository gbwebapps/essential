<?php declare(strict_types = 1);

namespace Modules\Backend\Products\Models;

use App\Models\BaseModel;
use Modules\Backend\Categories\Models\CategoriesModel;

/** Aggrega i prodotti per le analisi del magazzino, senza scritture applicative. */
class ProductStatisticsModel extends BaseModel
{
    /** Somma quantità × prezzo dei prodotti non eliminati, senza join e duplicazioni. */
    public function getInventoryValue(): string
    {
        $query = $this->db->query('select coalesce(sum(quantity * price), 0.00) as value
                                 from products where deleted_at is null');

        if ($query === false):
            throw new \RuntimeException('Valore del magazzino non disponibile.');
        endif;

        return (string) $query->getRowArray()['value'];
    }

    /**
     * Classifica le marche per valore dei prodotti non eliminati e associazioni abilitate.
     * Gli importi rimangono stringhe decimali: nessun arrotondamento binario nei calcoli.
     *
     * @return list<array{id: int, label: string, value: string}>
     */
    public function getTopBrandsByValue(int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $query = $this->db->query('select brands.id, brands.brand as label, sum(products.quantity * products.price) as value
                                 from brands
                                 join brands_to_products on brands_to_products.brand_id = brands.id and brands_to_products.status = 1
                                 join products on products.id = brands_to_products.product_id and products.deleted_at is null
                                 where brands.deleted_at is null
                                 group by brands.id, brands.brand
                                 order by value desc, brands.brand asc, brands.id asc
                                 limit ' . $limit);

        if ($query === false):
            throw new \RuntimeException('Valore dei prodotti per marca non disponibile.');
        endif;

        return array_map(static fn(array $row): array => [
            'id' => (int) $row['id'], 'label' => (string) $row['label'], 'value' => (string) $row['value'],
        ], $query->getResultArray());
    }

    /**
     * Classifica le categorie per valore delle sole associazioni dirette abilitate.
     * Il percorso completo distingue categorie omonime; non si sommano gli antenati.
     *
     * @return list<array{id: int, label: string, value: string}>
     */
    public function getTopCategoriesByValue(int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $query = $this->db->query('select categories.id, categories.category as label, sum(products.quantity * products.price) as value
                                 from categories
                                 join categories_to_products on categories_to_products.category_id = categories.id and categories_to_products.status = 1
                                 join products on products.id = categories_to_products.product_id and products.deleted_at is null
                                 where categories.deleted_at is null
                                 group by categories.id, categories.category
                                 order by value desc, categories.category asc, categories.id asc
                                 limit ' . $limit);

        if ($query === false):
            throw new \RuntimeException('Valore dei prodotti per categoria non disponibile.');
        endif;

        $rows = $query->getResultArray();

        if ($rows === []):
            return [];
        endif;

        $hierarchy = model(CategoriesModel::class)->getCategoryHierarchy();

        return array_map(static fn(array $row): array => [
            'id' => (int) $row['id'],
            'label' => $hierarchy['pathsById'][(int) $row['id']] ?? (string) $row['label'],
            'value' => (string) $row['value'],
        ], $rows);
    }

    /**
     * Conta i prodotti distinti, non eliminati, associati a ogni marca non eliminata.
     * Sono considerate soltanto le associazioni abilitate; non si sommano le quantità.
     *
     * @return list<array{id: int, label: string, count: int}>
     */
    public function getTopBrands(int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $query = $this->db->query('select brands.id, brands.brand as label, count(distinct products.id) as count
                                 from brands
                                 join brands_to_products on brands_to_products.brand_id = brands.id and brands_to_products.status = 1
                                 join products on products.id = brands_to_products.product_id and products.deleted_at is null
                                 where brands.deleted_at is null
                                 group by brands.id, brands.brand
                                 order by count desc, brands.brand asc, brands.id asc
                                 limit ' . $limit);

        if ($query === false):
            throw new \RuntimeException('Statistiche dei prodotti per marca non disponibili.');
        endif;

        return array_map(static fn(array $row): array => [
            'id' => (int) $row['id'], 'label' => (string) $row['label'], 'count' => (int) $row['count'],
        ], $query->getResultArray());
    }

    /**
     * Conta le associazioni dirette alle categorie, senza aggregare anche gli antenati.
     * Il percorso completo distingue categorie omonime in rami diversi dell'albero.
     *
     * @return list<array{id: int, label: string, count: int}>
     */
    public function getTopCategories(int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $query = $this->db->query('select categories.id, categories.category as label, count(distinct products.id) as count
                                 from categories
                                 join categories_to_products on categories_to_products.category_id = categories.id and categories_to_products.status = 1
                                 join products on products.id = categories_to_products.product_id and products.deleted_at is null
                                 where categories.deleted_at is null
                                 group by categories.id, categories.category
                                 order by count desc, categories.category asc, categories.id asc
                                 limit ' . $limit);

        if ($query === false):
            throw new \RuntimeException('Statistiche dei prodotti per categoria non disponibili.');
        endif;

        $rows = $query->getResultArray();

        if ($rows === []):
            return [];
        endif;

        $hierarchy = model(CategoriesModel::class)->getCategoryHierarchy();

        return array_map(static fn(array $row): array => [
            'id' => (int) $row['id'],
            'label' => $hierarchy['pathsById'][(int) $row['id']] ?? (string) $row['label'],
            'count' => (int) $row['count'],
        ], $rows);
    }
}
