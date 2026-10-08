<?php declare(strict_types = 1);

namespace App\Interfaces;

/**
 * Contratto opzionale per le sezioni CRUD che vogliono limitare
 * esplicitamente le colonne esportabili dal componente Export.
 *
 * Le entità che non implementano questa interfaccia continuano a usare
 * lo schema del database secondo il comportamento generico di ExportModel.
 */
interface ExportValidationInterface
{
    /**
     * @return list<string> Nomi fisici delle colonne esportabili dal CRUD.
     */
    public function exportAllowedFields(): array;
}
