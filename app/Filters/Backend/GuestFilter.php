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
            
            /* Reindirizzamento hardcoded (anti-loop) con Flashdata concatenati */
            return redirect()->to(base_url('backend/dashboard'))->with('message', $message)->with('class', 'danger')->with('message_icon', '<i class="fa-solid fa-triangle-exclamation"></i>');
            
        endif;

        /* Se non è loggato, lasciamo proseguire regolarmente la richiesta */
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        /* In questo filtro non è necessaria alcuna operazione post-risposta */
    }
}