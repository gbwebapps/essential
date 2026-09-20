<?php declare(strict_types = 1);

namespace App\Filters\Backend;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\IncomingRequest;
use Config\Database;
use Config\Services;

class LanguageFilter implements FilterInterface
{
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

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        /* Nessuna operazione in uscita */
    }

    /**
     * Recupera la lingua salvata nel DB o nel file di configurazione
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