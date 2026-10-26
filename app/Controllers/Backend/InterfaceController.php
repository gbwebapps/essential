<?php declare(strict_types = 1);

namespace App\Controllers\Backend;

use App\Libraries\Backend\InterfacePreferences;
use CodeIgniter\HTTP\ResponseInterface;

/** Nessuna operazione di questo controller salva impostazioni nel database. */
class InterfaceController extends BackendController
{
    public function change(): ResponseInterface
    {
        $posts = $this->request->getPost();
        $key = $posts['preference'] ?? null;
        $value = $posts['value'] ?? null;
        $preferences = new InterfacePreferences();

        if ( ! $this->request->isAJAX() || ! is_string($key) || ! is_string($value)):
            return $this->jsonResponse(['result' => false], 400);
        endif;

        try {
            $translations = $key === 'language' && in_array($value, config('App')->supportedLocales, true)
                ? $preferences->translations($value)
                : null;
            $preferences->set($key, $value);
        } catch (\InvalidArgumentException $e) {
            return $this->jsonResponse(['result' => false], 422);
        }

        $payload = ['result' => true, 'language' => $preferences->language(), 'theme' => $preferences->theme()];

        if ($key === 'language'):
            $this->request->setLocale($value);
            service('language')->setLocale($value);
            $payload['translations'] = $translations;
        endif;

        return $this->jsonResponse($payload);
    }
}
