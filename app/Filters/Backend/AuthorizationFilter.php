<?php declare(strict_types = 1);

namespace App\Filters\Backend;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthorizationFilter implements FilterInterface
{
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

        session()->set('intended_url', current_url());
        $message = lang('backend/auth.messages.loginNeeded');

        /* Imposta i flashdata validi per entrambi i flussi (AJAX e Standard) */
        session()->setFlashdata('message', $message);
        session()->setFlashdata('class', 'danger');
        session()->setFlashdata('icon', '<i class="fa-solid fa-triangle-exclamation"></i>');

        /* Gestione della risposta: AJAX vs Standard */
        if ($request->isAJAX() && $request->is('post')):
            return service('response')->setJSON(['result' => 'no_current_user_logged', 'csrf' => csrf_hash()]);
        endif;

        /* Redirect standard pulito dai with() in quanto già impostati nella sessione */
        return redirect()->to(base_url('backend/auth'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        /* In questo filtro non è necessaria alcuna operazione post-risposta */
    }
}