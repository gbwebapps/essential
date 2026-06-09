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

        /* Se l'utente è loggato ed è master, prosegue normalmente */
        if ($currentAdmin && (int) $currentAdmin->master === 1):
            return null;
        endif;

        /* Impostazione sessione di errore per accesso negato */
        session()->setFlashdata('message', sprintf(lang('backend/auth.messages.forbiddenArea'), esc($currentAdmin->firstname), esc($currentAdmin->lastname)));
        session()->setFlashdata('class', 'danger');
        session()->setFlashdata('icon', '<i class="fa-solid fa-triangle-exclamation"></i>');

        /* Blocco AJAX: restituisce esito negativo e messaggio */
        if ($request->isAJAX() && $request->is('post')):
            return service('response')->setJSON(['result' => false, 'message' => lang('backend/auth.messages.forbiddenArea')]);
        endif;

        /* Blocco Standard: reindirizza alla dashboard */
        return redirect()->to(base_url('backend/dashboard'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        /* In questo filtro non è necessaria alcuna operazione post-risposta */
    }
}