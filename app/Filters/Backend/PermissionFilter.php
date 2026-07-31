<?php declare(strict_types = 1);

namespace App\Filters\Backend;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filtro di sicurezza per l'enforcement dei permessi granulari sulle rotte.
 *
 * Verifica che l'utente loggato possieda i privilegi necessari per accedere alla risorsa.
 * Intercetta le richieste standard respingendole con un redirect e le richieste AJAX
 * bloccandole con una risposta JSON strutturata.
 */
class PermissionFilter implements FilterInterface
{
    /**
     * Esegue il controllo del permesso prima dell'esecuzione del controller.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments Elenco dei permessi passati dalla rotta (es. ['admins_index']).
     * @return mixed
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
     * Logica post-esecuzione (non richiesta per il controllo accessi).
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        /* ... Nessuna azione richiesta dopo il rendering ... */
    }
}