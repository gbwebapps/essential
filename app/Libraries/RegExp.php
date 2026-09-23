<?php declare(strict_types = 1); 

namespace App\Libraries; 

/**
 * Libreria di utilità dedicata alla validazione dei dati tramite espressioni regolari (RegEx).
 * 
 * Centralizza e incapsula i pattern di controllo complessi, garantendo che le stringhe critiche 
 * all'interno del sistema (come gli identificatori univoci o le credenziali di accesso) 
 * rispettino rigorosamente i requisiti formali e di sicurezza previsti.
 */
class RegExp 
{
	/**
	 * Valida la conformità di una stringa allo standard UUID (Universally Unique Identifier).
	 * 
	 * L'espressione regolare applicata verifica che la struttura rispetti il formato canonico a 36 caratteri 
	 * (inclusi i trattini) e che corrisponda formalmente alle specifiche delle versioni dalla 1 alla 5. 
	 * Questo controllo previene l'immissione di identificatori corrotti o contraffatti nelle query al database.
	 *
	 * @param string $uuid La stringa da sottoporre al controllo di validità formale
	 * @return bool Esito della validazione: true se il formato corrisponde a un UUID valido, false altrimenti
	 */
	public function validateUUID(string $uuid): bool
	{
		return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
	}

	/**
	 * Valida la robustezza e la complessità di una password secondo le policy di sicurezza di base.
	 * 
	 * Il pattern impone che la stringa abbia una lunghezza minima di 8 caratteri e richiede 
	 * la presenza simultanea (tramite lookahead) di almeno una lettera maiuscola, un carattere numerico 
	 * e un carattere speciale (incluso l'underscore). Il metodo è fondamentale per respingere 
	 * la registrazione o l'aggiornamento di credenziali considerate deboli o facilmente violabili.
	 *
	 * @param string $password La stringa in chiaro contenente la password da analizzare
	 * @return bool Esito della validazione: true se tutti i requisiti di complessità sono soddisfatti, false altrimenti
	 */
	public function validatePassword(string $password): bool
	{
	    return (bool) preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password);
	}
}