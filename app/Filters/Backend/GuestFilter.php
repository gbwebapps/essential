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

        /* Se non è loggato, lasciamo proseguire regolarmente la richiesta */
        if ( ! $currentAdmin):
            return null;
        endif;

        $message = sprintf(lang('backend/auth.messages.currentSessionOn'), esc($currentAdmin->firstname), esc($currentAdmin->lastname));

        /* Imposta i flashdata per entrambi i flussi */
        session()->setFlashdata('message', $message);
        session()->setFlashdata('class', 'danger');
        session()->setFlashdata('icon', '<i class="fa-solid fa-triangle-exclamation"></i>');

        /* Se un utente loggato tenta un'operazione AJAX su rotte guest (es. tab rimasta aperta) */
        if ($request->isAJAX() && $request->is('post')):
            return service('response')->setJSON(['result' => false, 'message' => $message, 'csrf' => $this->csrf]);
        endif;

        /* Reindirizzamento standard alla dashboard */
        return redirect()->to(base_url('backend/dashboard'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        /* In questo filtro non è necessaria alcuna operazione post-risposta */
    }
}