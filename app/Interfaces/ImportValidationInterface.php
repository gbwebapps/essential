<?php declare(strict_types = 1);

namespace App\Interfaces;

/**
 * Definisce il contratto di importazione CSV per i model CRUD applicativi.
 *
 * L'implementazione costituisce un opt-in esplicito all'import CRUD: il model dichiara sia
 * la whitelist dei campi importabili sia le regole applicative da sovrapporre al fallback SQL.
 * La modalità Tools > Database resta invece indipendente da questa interfaccia.
 */
interface ImportValidationInterface
{
    /**
     * Restituisce la whitelist dei campi fisici della tabella importabili dal flusso CRUD.
     * Le colonne non elencate restano escluse anche se esistono nello schema SQL.
     *
     * @return list<string>
     */
    public function importAllowedFields(): array;

    /**
     * Restituisce le regole specifiche per validare un record destinato all'inserimento tramite import.
     *
     * @return array Regole di validazione nel formato supportato da CodeIgniter
     */
    public function importAddValidationRules(): array;

    /**
     * Restituisce le regole specifiche per validare un record destinato alla modifica tramite import.
     *
     * @param array $data Dati di contesto del record, inclusa la primary key quando disponibile
     * @return array Regole di validazione nel formato supportato da CodeIgniter
     */
    public function importEditValidationRules(array $data): array;

    /**
     * Applica vincoli di dominio che non possono essere espressi con le sole validation rules.
     * Il metodo viene rieseguito immediatamente prima della scrittura per evitare race condition.
     *
     * @param string $action insert oppure update
     * @param array $data Dati normalizzati provenienti dal CSV
     * @param array|null $currentRow Record corrente del database per gli update, null per gli insert
     * @return list<string> Errori di business; array vuoto se l'operazione è consentita
     */
    public function importBusinessValidationErrors(string $action, array $data, ?array $currentRow): array;
}
