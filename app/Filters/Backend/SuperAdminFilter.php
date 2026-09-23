<?php declare(strict_types=1);

namespace App\Filters\Backend;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filtro di massima sicurezza (middleware) riservato agli amministratori di sistema (Superadmin).
 * 
 * Protegge le rotte critiche dell'applicativo consentendo l'accesso esclusivamente 
 * agli account dotati del flag di superadmin, bloccando incondizionatamente qualsiasi 
 * altro livello di privilegio, indipendentemente dai permessi specifici posseduti.
 */
class SuperAdminFilter implements FilterInterface
{
    /**
     * Intercetta la richiesta in ingresso per verificare il privilegio di superadmin dell'utente corrente.
     * 
     * Se l'utente non dispone del livello di accesso massimo, il filtro interrompe immediatamente 
     * l'esecuzione del controller di destinazione. Risponde con un payload JSON di errore per 
     * le chiamate asincrone (AJAX) oppure forza un reindirizzamento verso la dashboard per il flusso standard.
     *
     * @param RequestInterface $request L'oggetto rappresentante la richiesta HTTP in ingresso
     * @param array|null $arguments Parametri opzionali configurati a livello di routing
     * @return \CodeIgniter\HTTP\RedirectResponse|\CodeIgniter\HTTP\ResponseInterface|null Restituisce null per consentire l'accesso, oppure una risposta (JSON o Redirect) per negarlo
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $currentAdmin = service('authorization')->currentAdmin();

        /* Se l'utente è loggato ed è superadmin, prosegue normalmente */
        if ($currentAdmin && (int) $currentAdmin->superadmin === 1):
            return null;
        endif;

        /* Impostazione sessione di errore per accesso negato */
        session()->setFlashdata('message', sprintf(lang('backend/global.messages.permissionDenied'), esc($currentAdmin->firstname), esc($currentAdmin->lastname)));
        session()->setFlashdata('class', 'light text-danger fw-bold');
        session()->setFlashdata('icon', '<i class="fa-solid fa-ban"></i>');

        /* Blocco AJAX: restituisce esito negativo e messaggio */
        if ($request->isAJAX() && $request->is('post')):
            return service('response')->setJSON(['result' => false, 'message' => lang('backend/global.messages.permissionDenied')]);
        endif;

        /* Blocco Standard: reindirizza alla dashboard */
        return redirect()->to(base_url('backend/dashboard'));
    }

    /**
     * Intercetta la risposta HTTP in uscita dopo l'elaborazione da parte del controller di destinazione.
     * 
     * La logica di sbarramento e protezione si esaurisce interamente nella fase preventiva (before), 
     * pertanto questo metodo non esegue alcuna operazione o manipolazione sulla risposta.
     *
     * @param RequestInterface $request L'oggetto rappresentante la richiesta HTTP elaborata
     * @param ResponseInterface $response L'oggetto rappresentante la risposta HTTP generata dal framework
     * @param array|null $arguments Parametri opzionali configurati a livello di routing
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        /* In questo filtro non è necessaria alcuna operazione post-risposta */
    }
}