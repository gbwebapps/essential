<?php declare(strict_types = 1);

namespace App\Filters\Backend;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\IncomingRequest;
use Config\Database;
use Config\Services;

/**
 * Filtro (middleware) dedicato alla gestione dinamica della localizzazione dell'applicativo.
 * 
 * Intercetta tutte le richieste in ingresso per configurare a livello globale la lingua (locale) 
 * del framework. Assicura che i servizi di traduzione e le librerie interne utilizzino sempre 
 * la lingua corretta, rispettando le impostazioni salvate nel database o eventuali selezioni in tempo reale.
 */
class LanguageFilter implements FilterInterface
{
    /**
     * Intercetta la richiesta HTTP per determinare e impostare la lingua di sistema prima dell'esecuzione del controller.
     * 
     * Applica una gerarchia di priorità: analizza prima un'eventuale richiesta esplicita via POST 
     * (utile per i cambi di lingua dinamici in interfaccia), per poi ripiegare sulle preferenze storicizzate. 
     * Una volta individuato il codice lingua, lo propaga a tutti i servizi centrali (Request, Language, Config) di CodeIgniter.
     *
     * @param RequestInterface $request L'oggetto rappresentante la richiesta HTTP in ingresso
     * @param array|null $arguments Parametri opzionali configurati per il filtro
     * @return void Interrompe silenziosamente l'esecuzione solo se la richiesta non è di tipo web (CLI)
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        /* Interrompe se non è una richiesta web HTTP */
        if ( ! $request instanceof IncomingRequest):
            return;
        endif;

        /* 1. Determina la lingua: priorità al POST, poi al DB/Config */
        $activeLanguage = $request->getPost('language') ?: $this->getStoredLanguage();

        /* 2. Applica la lingua a tutti i livelli del framework */
        $request->setLocale($activeLanguage);
        Services::request()->setLocale($activeLanguage);
        Services::language()->setLocale($activeLanguage);
        config('App')->defaultLocale = $activeLanguage;
    }

    /**
     * Intercetta la risposta HTTP in uscita dopo l'elaborazione da parte del controller.
     * 
     * Poiché il compito di questo filtro si esaurisce nella configurazione ambientale preliminare (fase before),
     * il metodo non esegue alcuna manipolazione sui dati in uscita.
     *
     * @param RequestInterface $request L'oggetto rappresentante la richiesta HTTP elaborata
     * @param ResponseInterface $response L'oggetto rappresentante la risposta HTTP generata dal framework
     * @param array|null $arguments Parametri opzionali configurati per il filtro
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        /* Nessuna operazione in uscita */
    }

    /**
     * Recupera il codice della lingua predefinita del sistema ispezionando le diverse fonti di persistenza.
     * 
     * Esegue una lettura sequenziale per definire il fallback corretto: parte da una base statica ('en'), 
     * tenta la lettura dal file di configurazione nativo e, infine, esegue una query diretta (RAW SQL) al database 
     * per applicare eventuali sovrascritture amministrative. Gestisce in modo silente eventuali eccezioni (es. tabelle mancanti in fase di setup).
     *
     * @return string Il codice ISO della lingua individuata (es. 'it', 'en')
     */
    private function getStoredLanguage(): string
    {
        /* Fallback di base */
        $language = 'en';

        /* Tenta la lettura dal file di configurazione statico */
        $config = config('Backend\General');
        
        if ($config !== null && property_exists($config, 'language')):
            $language = $config->language;
        endif;

        /* Tenta la lettura diretta dal database (sovrascrive il config se esiste) */
        try {
            $db = Database::connect();
            $row = $db->table('settings')
                      ->whereIn('class', ['Backend\General', 'App\Config\Backend\General'])
                      ->where('key', 'language')
                      ->get()
                      ->getRow();

            if ($row !== null):
                $language = $row->value;
            endif;
        } catch (\Exception $e) {
            /* Ignora silenziosamente se la tabella non è ancora stata creata */
        }

        return $language;
    }
}