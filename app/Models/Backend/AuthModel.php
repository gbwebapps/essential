<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

class AuthModel extends BackendModel
{
    protected function initModel(): void 
    {
        parent::initModel();
    }

    protected array $loginAllowedFields = ['email', 'password', 'remember']; 
    protected array $resetPasswordAllowedFields = ['email'];
    protected array $setPasswordAllowedFields = ['password', 'confirmPassword', 'checkAuthCode'];

    public function validateLogin(array $posts)
    {
        return [
            'email' => [
                'label' => 'Indirizzo email',
                'rules' => 'required|valid_email'
            ], 
            'password' => [
                'label' => 'Password',
                'rules' => 'required'
            ]
        ];
    }

    public function validateResetPassword(array $posts)
    {
        return [
            'email' => [
                'label' => 'Indirizzo email',
                'rules' => 'required|valid_email'
            ],
        ];
    }

    public function validateSetPassword(array $posts)
    {
        return [
            'password' => [
                'label' => 'Password',
                'rules' => 'required'
            ], 
            'confirmPassword' => [
                'label' => 'Conferma password',
                'rules' => 'required|matches[password]'
            ], 
            'checkAuthCode' => [
                'label' => 'Codice di autenticazione',
                'rules' => 'required'
            ]
        ];    
    }

    public function login(array $posts, \CodeIgniter\HTTP\IncomingRequest $request)
    {
        try 
        {
            /* 1. Inizializzazione variabili e parametri di configurazione */
            $posts = $this->checkAllowedFields($posts, $this->loginAllowedFields);
            $rememberMe = (isset($posts['rememberMe']) && $posts['rememberMe']) ? true : false;
            $ip = $request->getIPAddress();

            /* Lettura centralizzata delle configurazioni per evitare chiamate ridondanti */
            $allowAttempts = (bool) config('BackendAuth')->attempts;
            $allowTwoFactor = (bool) config('BackendAuth')->twoFactor;

            /* 2. Costruzione della query di lettura iniziale dell'utente */
            if ($allowAttempts):
                /* Genera la data passata nel formato corretto per il database */
                $secondsInterval = (int) config('BackendAuth')->attemptsInterval;
                $attemptsInterval = date('Y-m-d H:i:s', time() - $secondsInterval);
                
                $sql = "select admins.uuid, admins.firstname, admins.lastname, admins.email, admins.password_hash, COUNT(admins_attempts.id) as times
                        from admins
                        left join admins_attempts
                        on admins_attempts.user_uuid = admins.uuid and admins_attempts.timestamp > ?
                        where admins.email = ? and admins.status = 1 and admins.suspended_at is null
                        group by admins.uuid limit 1";
                $params = [$attemptsInterval, $posts['email']];
            else:
                $sql = "select uuid, firstname, lastname, email, password_hash 
                        from admins 
                        where email = ? and status = 1 and suspended_at is null limit 1";
                $params = [$posts['email']];
            endif;

            /* Esecuzione della lettura (fuori transazione per ottimizzare le prestazioni) */
            $admin = $this->db->query($sql, $params)->getRow();

            /* Se l'utente non esiste, esce immediatamente con errore generico (sicurezza) */
            if ( ! $admin):
                return ['result' => false, 'message' => lang('backend/auth.messages.loginFailed')];
            endif;

            /* 3. Controllo immediato del blocco tentativi (senza effettuare ulteriori scritture) */
            if ($allowAttempts && isset($admin->times)):
                if ($admin->times >= (int) config('BackendAuth')->attemptsLimit):
                    return ['result' => false, 'message' => lang('backend/auth.messages.tooMAnyAttempts')];
                endif;
            endif;

            /* 4. Verifica della password */
            if ( ! password_verify($posts['password'], $admin->password_hash)):
                
                /* La transazione si apre solo ora, poiché dobbiamo effettuare una scrittura sul DB */
                $this->db->transBegin();
                
                if ($allowAttempts):
                    $sql = "insert into admins_attempts (user_uuid, ip, timestamp) values (?, ?, ?)";
                    $this->db->query($sql, [$admin->uuid, $ip, date('Y-m-d H:i:s')]);
                endif;

                $this->db->transCommit();
                return ['result' => false, 'message' => lang('backend/auth.messages.loginFailed')];
                
            endif;

            /* 5. Gestione del Secondo Fattore di Autenticazione (2FA) */
            if ($allowTwoFactor):
                $sql = "select method, secret from admins_2fa where user_uuid = ? and enabled = 1 limit 1";
                $twofaQuery = $this->db->query($sql, [$admin->uuid]);
                $twofa = $twofaQuery->getRow();

                if ($twofa):
                    /* Se il 2FA è richiesto, interrompiamo qui il flusso prima di azzerare i tentativi.
                       I tentativi verranno azzerati solo dopo la corretta verifica dell'OTP */
                    if ($twofa->method === 'email'):
                        (new EmailOtpService($this->app))->send($admin->uuid);
                    endif;

                    return ['result' => '2fa_required', 'method' => $twofa->method, 'user_uuid' => $admin->uuid, 'remember_me' => $rememberMe];
                endif;
            endif;

            /* 6. Fase finale del Login (Password e 2FA superati con successo) */
            $this->db->transBegin();

            /* Pulizia della tabella tentativi solo ad autenticazione completamente avvenuta */
            if ($allowAttempts):
                $sql = "delete from admins_attempts where user_uuid = ?";
                $this->db->query($sql, [$admin->uuid]);
            endif;

            /* Delega la finalizzazione (creazione sessioni/cookie) al metodo interno */
            return $this->innerLogin($admin, $rememberMe, $request);

        } catch (\Exception $e) {

            /* Verifica se una transazione è attiva prima di effettuare il rollback */
            if ($this->db->transStatus() === false):
                $this->db->transRollback();
            endif;
            
            return ['result' => false, 'message' => 'Errore: ' . $e->getMessage()];
        }
    }

    /* Completa il login creando token, cookie/sessione e messaggi */
    private function innerLogin(object $admin, bool $rememberMe, \CodeIgniter\HTTP\IncomingRequest $request): array
    {
        if ($rememberMe):
            $time = (int) config('BackendAuth')->rememberMeTime;
            $tokenType = 'cookie';
        else:
            $time = (int) config('BackendAuth')->sessionTime;
            $tokenType = 'session';
        endif;

        $token = new \App\Libraries\Token();
        $tokenHash = $token->getHash(config('BackendAuth')->hashKey);

        /* Generazione delle stringhe DATETIME corrette per admins_tokens */
        $tokenCreate = date('Y-m-d H:i:s');
        $tokenExpire = date('Y-m-d H:i:s', time() + $time);

        /* 3. Pulizia dei vecchi token di tipo sessione se applicabile */
        if ($tokenType === 'session'):
            $sql = "delete from admins_tokens where user_uuid = ? and token_type = ?";
            $this->db->query($sql, [$admin->uuid, 'session']);
        endif;

        /* 4. Registrazione del nuovo token nel database con i metodi nativi di CI4 */
        $userAgent = $request->getUserAgent()->getAgentString();
        $ip = $request->getIPAddress();

        $sql = "insert into admins_tokens (user_uuid, token_hash, token_create, token_expire, token_type, user_agent, ip) values(?, ?, ?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $admin->uuid,
            $tokenHash,
            $tokenCreate,
            $tokenExpire,
            $tokenType,
            $userAgent,
            $ip
        ]);

        /* Chiude la transazione aperta nel metodo principale prima di impostare gli stati del client */
        $this->db->transCommit();

        /* 5. Rigenerazione dell'ID di sessione per prevenire Session Fixation */
        session()->regenerate(true);

        /* 6. Assegnazione del token al client (con cifratura per il cookie Remember Me) */
        if ($rememberMe):
            /* Cifratura del token raw tramite il servizio globale prima di inserirlo nel cookie */
            $encryptedToken = service('crypto')->encrypt($token->getValue());

            /* Utilizzo della funzione nativa helper di CI4 per l'impostazione sicura del cookie */
            helper('cookie');
            set_cookie([
                'name'     => 'backendRememberMe',
                'value'    => $encryptedToken,
                'expire'   => $time,
                'httponly' => true,
                'secure'   => true,
                'samesite' => 'Lax'
            ]);
        else:
            /* Memorizzazione standard nella sessione server */
            session()->set('backendSession', $token->getValue());
        endif;

        /* 7. Configurazione del messaggio flash di avvenuto login */
        $welcomeMessage = sprintf(lang('backend/auth.messages.login_welcome'), esc($admin->firstname) . ' ' . esc($admin->lastname));
        
        session()->setFlashdata([
            'message' => $welcomeMessage,
            'class'   => 'success',
            'icon'    => '<i class="fa-solid fa-check"></i>'
        ]);

        return ['result' => true];
    }

    public function resetPassword()
    {
        
    }

    public function setPassword()
    {
        
    }

    public function checkAuthCode()
    {
        
    }

    /* Logout basato su sessione */
    public function logoutBySession(): void
    {
        try {
            if (session()->has('backendSession')):
                
                /* Recupera il token in chiaro dalla sessione */
                $sessionValue = session()->get('backendSession');
                $token = new \App\Core\Token($sessionValue);
                $tokenHash = $token->getHash(config('BackendAuth')->hashKey);

                /* Elimina il record dal database */
                $sql = "delete from admins_tokens where token_hash = ? and token_type = ?";
                $this->db->query($sql, [$tokenHash, 'session']);

                /* Pulisce e distrugge la sessione */
                session()->remove('backendSession');

            endif;
        } catch (\Throwable $e) {
            log_message('error', lang('backend/auth.messages.logoutSessionError') . ' - ' . $e->getMessage());
        }
    }

    /* Logout basato su cookie persistente */
    public function logoutByCookie(string $cookieValue): void
    {
        try {
            /* Decifra il valore del cookie */
            $decryptedValue = service('crypto')->decrypt($cookieValue);

            if ($decryptedValue):
                /* Ricava l'hash dal token decifrato */
                $token = new \App\Core\Token($decryptedValue);
                $tokenHash = $token->getHash(config('BackendAuth')->hashKey);

                /* Elimina il record dal database */
                $sql = "delete from admins_tokens where token_hash = ? and token_type = ?";
                $this->db->query($sql, [$tokenHash, 'cookie']);
            endif;

            /* Rimuove il cookie fisicamente dal browser */
            helper('cookie');
            delete_cookie('backendRememberMe');

        } catch (\Throwable $e) {
            log_message('error', lang('backend/auth.messages.logoutCookieError') . ' - ' . $e->getMessage());
        }
    }
}