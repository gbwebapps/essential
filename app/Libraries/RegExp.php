<?php declare(strict_types = 1); 

namespace App\Libraries; 

/**
 * Class RegExp
 *
 * Libreria di utilità globale dedicata alla validazione strutturale di stringhe e dati complessi
 * mediante l'utilizzo di espressioni regolari (Regex) centralizzate.
 */
class RegExp 
{
	/**
	 * Valida la conformità sintattica di una stringa rispetto al formato standard internazionale UUID (versioni da 1 a 5).
	 *
	 * @param string $uuid La stringa dell'identificativo UUID da verificare.
	 * @return bool True se l'UUID rispetta l'espressione regolare standard, altrimenti false.
	 */
	public function validateUUID(string $uuid): bool
	{
		return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
	}

	/**
	 * Verifica i criteri di robustezza di una password in conformità con le policy di sicurezza del sistema.
	 * Richiede obbligatoriamente: almeno una lettera maiuscola, un numero, un carattere speciale e un minimo di 8 caratteri.
	 *
	 * @param string $password La stringa della password da sottoporre a validazione.
	 * @return bool True se la password soddisfa tutti i requisiti di complessità, altrimenti false.
	 */
	public function validatePassword(string $password): bool
	{
	    return (bool) preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password);
	}
}