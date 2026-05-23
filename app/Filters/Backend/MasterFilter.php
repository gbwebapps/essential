<?php declare(strict_types=1);

namespace App\Filters\Backend;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class MasterFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $currentAdmin = service('authorization')->currentAdmin();

        /* Se l'utente non è loggato o non ha i privilegi di master, blocca l'accesso */
        if ( ! $currentAdmin || (int) $currentAdmin->master === 0):
            
            /* Gestione dell'errore per le richieste asincrone (AJAX) con status code 403 (Forbidden) */
            if ($request->isAJAX()):
                return service('response')->setJSON(['result'  => false, 'message' => 'Non è possibile accedere a questa area.'])->setStatusCode(403);
            endif;

            /* Gestione dell'errore per le richieste standard con Flashdata */
            session()->setFlashdata('message', 'Non è possibile accedere a questa area.');
            session()->setFlashdata('class', 'danger');
            session()->setFlashdata('message_icon', '<i class="fa-solid fa-triangle-exclamation"></i>');

            /* Reindirizzamento alla dashboard */
            return redirect()->to(base_url('backend/dashboard'));
            
        endif;

        /* L'utente è master, la richiesta prosegue normalmente */
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        /* In questo filtro non è necessaria alcuna operazione post-risposta */
    }
}