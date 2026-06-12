<?php declare(strict_types = 1);

namespace App\Traits;

/**
 * Trait HashableTrait
 *
 * Fornisce funzionalità riutilizzabili e condivise per la generazione di stringhe
 * casuali ed hash crittograficamente sicuri all'interno dell'applicazione.
 */
trait HashableTrait
{
    /**
     * Genera una stringa esadecimale casuale e crittograficamente sicura.
     *
     * @param int $num Il numero di byte casuali da generare prima della conversione in esadecimale.
     * @return string La stringa hash generata (la lunghezza finale sarà il doppio dei byte indicati).
     */
    public function generateHash(int $num): string
    {
        return bin2hex(random_bytes($num));
    }
}