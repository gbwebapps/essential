<?php declare(strict_types = 1);

namespace App\Filters\Backend;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filtro di autorizzazione (middleware) per la protezione delle rotte del pannello di controllo.
 * 
 * Si occupa di verificare la presenza di una sessione amministrativa valida prima che la richiesta 
 * raggiunga il controller. In assenza di autenticazione, gestisce la pulizia dei cookie e delle sessioni, 
 * memorizza l'URL originario per consentire il reindirizzamento post-login e interrompe il flusso 
 * instradando l'utente verso la pagina di accesso.
 */
class AuthorizationFilter implements FilterInterface
{
    /**
     * Intercetta e analizza la richiesta HTTP in ingresso per validare lo stato di autenticazione dell'utente.
     * 
     * Se l'amministratore non è connesso, il metodo esegue il logout per timeout (pulendo sia i cookie che il database),
     * imposta i messaggi (flashdata) e differenzia l'interruzione del flusso: restituisce un payload JSON specifico 
     * in caso di chiamate asincrone (AJAX) oppure un redirect HTTP standard verso il form di login.
     *
     * @param RequestInterface $request L'oggetto rappresentante la richiesta HTTP in ingresso
     * @param array|null $arguments Parametri opzionali configurati per il filtro (non utilizzati in questo contesto)
     * @return \CodeIgniter\HTTP\RedirectResponse|\CodeIgniter\HTTP\ResponseInterface|null Restituisce null per autorizzare la richiesta, oppure una risposta (JSON o Redirect) per bloccarla
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $currentAdmin = service('authorization')->currentAdmin();

        /* Se l'utente è loggato, lasciamo proseguire la richiesta */
        if ($currentAdmin):
            return null;
        endif;

        /* Carichiamo il model. Nessun impatto negativo sulle performance */
        $authModel = model(\App\Models\Backend\AuthModel::class);
        
        $cookie = $request->getCookie('backendRememberMe');

        /* Eseguiamo la disconnessione completa che pulisce anche il database */
        if ($cookie === null):
            $authModel->logoutBySession('timeout');
        else:
            $authModel->logoutByCookie($cookie, 'timeout');
        endif;

        /* Salviamo l'URL solo se è una normale navigazione GET (non AJAX) e non è il logout */
        if ( ! $request->isAJAX() && $request->is('get') && ! url_is('backend/auth/logout')):
            session()->set('intended_url', current_url());
        endif;

        $message = lang('backend/auth.messages.loginNeeded');

        /* Imposta i flashdata validi per entrambi i flussi (AJAX e Standard) */
        session()->setFlashdata('message', $message);
        session()->setFlashdata('class', 'light text-danger fw-bold');
        session()->setFlashdata('icon', '<i class="fa-solid fa-triangle-exclamation"></i>');

        /* Gestione della risposta: AJAX vs Standard */
        if ($request->isAJAX() && $request->is('post')):
            return service('response')->setJSON(['result' => 'no_current_user_logged']);
        endif;

        /* Redirect standard pulito dai with() in quanto già impostati nella sessione */
        return redirect()->to(base_url('backend/auth'));
    }

    /**
     * Intercetta la risposta HTTP in uscita dopo l'elaborazione da parte del controller.
     * 
     * Per questo specifico filtro di autorizzazione preliminare, non è richiesta alcuna manipolazione 
     * dei dati post-elaborazione, pertanto il metodo rimane intenzionalmente vuoto.
     *
     * @param RequestInterface $request L'oggetto rappresentante la richiesta HTTP elaborata
     * @param ResponseInterface $response L'oggetto rappresentante la risposta HTTP generata dal controller
     * @param array|null $arguments Parametri opzionali configurati per il filtro
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        /* In questo filtro non è necessaria alcuna operazione post-risposta */
    }
}