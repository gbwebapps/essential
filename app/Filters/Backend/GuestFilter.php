<?php declare(strict_types = 1);

namespace App\Filters\Backend;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class GuestFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $currentAdmin = service('authorization')->currentAdmin();

        /* Se l'utente è loggato, impediamo l'accesso alle pagine di login/reset */
        if ($currentAdmin):
            
            $message = esc($currentAdmin->firstname) . ' ' . esc($currentAdmin->lastname) . ' la sessione è ancora in corso.';
            
            /* Impostazione dei flashdata nativi per gli avvisi */
            session()->setFlashdata('message', $message);
            session()->setFlashdata('class', 'danger');
            session()->setFlashdata('message_icon', '<i class="fa-solid fa-triangle-exclamation"></i>');

            /* Reindirizzamento alla dashboard interrompendo la richiesta corrente */
            return redirect()->to(base_url('backend/dashboard'));
            
        endif;

        /* Se non è loggato, lasciamo proseguire regolarmente la richiesta */
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        /* In questo filtro non è necessaria alcuna operazione post-risposta */
    }
}