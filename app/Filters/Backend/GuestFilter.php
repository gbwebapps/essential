<?php declare(strict_types = 1);

namespace App\Filters\Backend;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filtro di restrizione (middleware) per le rotte accessibili esclusivamente agli utenti non autenticati (guest).
 * 
 * Impedisce agli amministratori con una sessione già attiva di accedere a pagine destinate 
 * ai visitatori (come il modulo di login o il recupero della password). In caso di sessione attiva, 
 * il filtro interrompe l'accesso alla rotta richiesta e reindirizza automaticamente l'utente verso la dashboard.
 */
class GuestFilter implements FilterInterface
{
    /**
     * Intercetta la richiesta HTTP in ingresso per verificare l'assenza di una sessione amministrativa attiva.
     * 
     * Se l'utente è già autenticato, impedisce l'esecuzione del controller di destinazione, imposta 
     * un messaggio di notifica (flashdata) e interrompe il flusso, restituendo un payload JSON di blocco 
     * per le chiamate asincrone (AJAX) o un redirect HTTP standard verso il pannello di controllo.
     *
     * @param RequestInterface $request L'oggetto rappresentante la richiesta HTTP in ingresso
     * @param array|null $arguments Parametri opzionali configurati per il filtro
     * @return \CodeIgniter\HTTP\RedirectResponse|\CodeIgniter\HTTP\ResponseInterface|null Restituisce null per consentire l'accesso ai guest, oppure una risposta (JSON o Redirect) per bloccare gli utenti già connessi
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $currentAdmin = service('authorization')->currentAdmin();

        /* Se non è loggato, lasciamo proseguire regolarmente la richiesta */
        if ( ! $currentAdmin):
            return null;
        endif;

        $message = sprintf(lang('backend/auth.messages.currentSessionOn'), esc($currentAdmin->firstname), esc($currentAdmin->lastname));

        /* Imposta i flashdata per entrambi i flussi */
        session()->setFlashdata('message', $message);
        session()->setFlashdata('class', 'light text-danger fw-bold');
        session()->setFlashdata('icon', '<i class="fa-solid fa-triangle-exclamation"></i>');

        /* Se un utente loggato tenta un'operazione AJAX su rotte guest (es. tab rimasta aperta) */
        if ($request->isAJAX() && $request->is('post')):
            return service('response')->setJSON(['result' => false, 'message' => $message]);
        endif;

        /* Reindirizzamento standard alla dashboard */
        return redirect()->to(base_url('backend/dashboard'));
    }

    /**
     * Intercetta la risposta HTTP in uscita dopo l'elaborazione da parte del controller.
     * 
     * Per la logica di questo specifico filtro non è richiesta alcuna manipolazione 
     * dei dati post-elaborazione, pertanto il metodo non esegue alcuna operazione.
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