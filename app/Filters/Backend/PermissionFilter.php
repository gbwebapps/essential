<?php declare(strict_types = 1);

namespace App\Filters\Backend;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filtro di sicurezza (middleware) basato sul controllo degli accessi (ACL).
 * 
 * Protegge le singole rotte del pannello di controllo verificando che l'amministratore 
 * disponga dei privilegi specifici richiesti per l'operazione. Implementa un bypass automatico 
 * per gli account di livello Superadmin, garantendo loro l'accesso incondizionato.
 */
class PermissionFilter implements FilterInterface
{
    /**
     * Intercetta la richiesta in ingresso per validare i privilegi dell'amministratore corrente rispetto alla rotta.
     * 
     * Analizza gli argomenti associati al filtro per identificare il permesso specifico richiesto.
     * Se l'utente non possiede il requisito (e non è un Superadmin), il metodo interrompe l'esecuzione,
     * restituendo un payload JSON di errore per le richieste asincrone (AJAX) o forzando un reindirizzamento
     * standard verso la dashboard.
     *
     * @param RequestInterface $request L'oggetto rappresentante la richiesta HTTP in ingresso
     * @param array|null $arguments Array configurato nelle rotte contenente il permesso richiesto (es. ['manage_users'])
     * @return \CodeIgniter\HTTP\RedirectResponse|\CodeIgniter\HTTP\ResponseInterface|null Restituisce null se l'accesso è autorizzato, oppure una risposta (JSON o Redirect) per negare l'accesso
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $currentAdmin = service('authorization')->currentAdmin();

        /* Sbarramento preliminare: se l'utente non è loggato, lasciamo che se ne occupi l'AuthorizationFilter */
        if ( ! $currentAdmin):
            return null;
        endif;

        /* Bypass Superadmin: se ha la proprietà globale 'all', l'accesso è sempre garantito */
        if (isset($currentAdmin->permissions->all) && $currentAdmin->permissions->all === true):
            return null;
        endif;

        /* Controllo del permesso richiesto dalla rotta */
        if ( ! empty($arguments)):
            $requiredPermission = $arguments[0];

            /* Verifichiamo se la proprietà associata al permesso esiste nell'oggetto permissions dell'admin */
            if (property_exists($currentAdmin->permissions, $requiredPermission)):
                return null;
            endif;
        endif;

        /* --- SE IL PERMESSO MANCA: ABORTO DELLA RICHIESTA --- */

        $message = lang('backend/global.messages.permissionDenied');

        /* Gestione flussi AJAX / POST */
        if ($request->isAJAX() && $request->is('post')):
            return service('response')->setJSON(['result'  => false,'message' => $message]);
        endif;

        /* Gestione flussi Standard / GET */
        session()->setFlashdata('message', $message);
        session()->setFlashdata('class', 'light text-danger fw-bold');
        session()->setFlashdata('icon', '<i class="fa-solid fa-ban"></i>');

        return redirect()->to(base_url('backend/dashboard'));
    }

    /**
     * Intercetta la risposta HTTP in uscita dopo l'elaborazione da parte del controller di destinazione.
     * 
     * Essendo un filtro di sbarramento preventivo, tutte le verifiche avvengono nella fase di pre-elaborazione (before).
     * Il metodo non effettua quindi alcuna manipolazione sulla risposta in uscita.
     *
     * @param RequestInterface $request L'oggetto rappresentante la richiesta HTTP elaborata
     * @param ResponseInterface $response L'oggetto rappresentante la risposta HTTP generata dal controller
     * @param array|null $arguments Parametri opzionali configurati per il filtro
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        /* ... Nessuna azione richiesta dopo il rendering ... */
    }
}