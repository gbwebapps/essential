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

        /* 1. Definizione dinamica dei parametri in base allo stato */
        $isNotLogged = ! $currentAdmin;
        
        $ajaxStatus  = $isNotLogged ? 401 : 403;
        $ajaxResult  = $isNotLogged ? 'no_current_user_logged' : false;
        
        $redirectUrl = $isNotLogged ? 'backend/auth' : 'backend/dashboard';
        $flashMsg    = $isNotLogged ? lang('backend/auth.messages.loginNeeded') : sprintf(lang('backend/auth.messages.forbiddenArea'), esc($currentAdmin->firstname), esc($currentAdmin->lastname));

        /* 2. Impostazione della sessione valida per entrambi i flussi (AJAX e Standard) */
        session()->setFlashdata('message', $flashMsg);
        session()->setFlashdata('class', 'danger');
        session()->setFlashdata('icon', '<i class="fa-solid fa-triangle-exclamation"></i>');

        /* 3. Esecuzione blocco unificato AJAX */
        if ($request->isAJAX()):
            $json = ['result' => $ajaxResult];
            
            /* Aggiunge il messaggio all'array JSON se necessario */
            if ( ! $isNotLogged):
                $json['message'] = lang('backend/auth.messages.forbiddenArea');
            endif;
            
            return service('response')->setJSON($json)->setStatusCode($ajaxStatus);
        endif;

        /* 4. Esecuzione blocco unificato Standard Redirect */
        return redirect()->to(base_url($redirectUrl));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        /* In questo filtro non è necessaria alcuna operazione post-risposta */
    }
}