<?php declare(strict_types = 1);

namespace App\Filters\Backend;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Class AuthorizationFilter
 *
 * Filtro di protezione (Middleware) dedicato alla verifica dello stato di autenticazione
 * dell'amministratore di backend. Gestisce i tentativi di persistenza tramite cookie,
 * la memorizzazione dell'URL inteso e i flussi di reindirizzamento (standard e AJAX).
 */
class AuthorizationFilter implements FilterInterface
{
    /**
     * Intercetta la richiesta HTTP in ingresso per validare la sessione dell'utente.
     *
     * @param RequestInterface $request   Oggetto della richiesta HTTP corrente.
     * @param array|null       $arguments Argomenti opzionali configurati nella rotta.
     * @return ResponseInterface|null Restituisce un oggetto Response (Redirect o JSON) se l'utente non è autenticato, altrimenti null.
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
            $authModel->logoutBySession();
        else:
            $authModel->logoutByCookie($cookie);
        endif;

        if ( ! url_is('backend/auth/logout')):
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

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        /* In questo filtro non è necessaria alcuna operazione post-risposta */
    }
}