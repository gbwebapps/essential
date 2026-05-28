<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

class AuthModel extends BackendModel
{
    protected function initModel(): void 
    {
        parent::initModel();
    }

    protected array $loginAllowedFields = ['email', 'password', 'rememberMe']; 
    protected array $resetPasswordAllowedFields = ['email'];
    protected array $setPasswordAllowedFields = ['password', 'token'];

    public function validateLoginRules()
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

    public function validateResetPasswordRules()
    {
        return [
            'email' => [
                'label' => 'Indirizzo email',
                'rules' => 'required|valid_email'
            ],
        ];
    }

    public function validateSetPasswordRules()
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
            'token' => [
                'label' => 'Token di autenticazione',
                'rules' => 'required|checkTokenRule', 
                'errors' => [
                    'checkTokenRule' => lang('backend/auth.messages.checkAuthError')
                ]
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
                        on admins_attempts.admin_uuid = admins.uuid and admins_attempts.timestamp > ?
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
                return ['result' => 'loginFailed', 'message' => lang('backend/auth.messages.loginFailed')];
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
                    $sql = "insert into admins_attempts (admin_uuid, ip, timestamp) values (?, ?, ?)";
                    $this->db->query($sql, [$admin->uuid, $ip, date('Y-m-d H:i:s')]);
                endif;

                $this->db->transCommit();
                return ['result' => 'loginFailed', 'message' => lang('backend/auth.messages.loginFailed')];
                
            endif;

            /* 5. Gestione del Secondo Fattore di Autenticazione (2FA) */
            if ($allowTwoFactor):
                $sql = "select method, secret from admins_2fa where admin_uuid = ? and enabled = 1 limit 1";
                $twofaQuery = $this->db->query($sql, [$admin->uuid]);
                $twofa = $twofaQuery->getRow();

                if ($twofa):
                    /* Se il 2FA è richiesto, interrompiamo qui il flusso prima di azzerare i tentativi.
                       I tentativi verranno azzerati solo dopo la corretta verifica dell'OTP */
                    if ($twofa->method === 'email'):
                        (new EmailOtpService($this->app))->send($admin->uuid);
                    endif;

                    return ['result' => '2fa_required', 'method' => $twofa->method, 'admin_uuid' => $admin->uuid, 'remember_me' => $rememberMe];
                endif;
            endif;

            /* 6. Fase finale del Login (Password e 2FA superati con successo) */
            $this->db->transBegin();

            /* Pulizia della tabella tentativi solo ad autenticazione completamente avvenuta */
            if ($allowAttempts):
                $sql = "delete from admins_attempts where admin_uuid = ?";
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
            $sql = "delete from admins_tokens where admin_uuid = ? and token_type = ?";
            $this->db->query($sql, [$admin->uuid, 'session']);
        endif;

        /* 4. Registrazione del nuovo token nel database con i metodi nativi di CI4 */
        $userAgent = $request->getUserAgent()->getAgentString();
        $ip = $request->getIPAddress();

        $sql = "insert into admins_tokens (admin_uuid, token_hash, token_create, token_expire, token_type, user_agent, ip) values(?, ?, ?, ?, ?, ?, ?)";
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
        $welcomeMessage = sprintf(lang('backend/auth.messages.loginWelcome'), esc($admin->firstname) . ' ' . esc($admin->lastname));
        
        session()->setFlashdata([
            'message' => $welcomeMessage,
            'class'   => 'success',
            'icon'    => '<i class="fa-solid fa-check"></i>'
        ]);

        return ['result' => true];
    }

    public function resetPassword(array $posts, \CodeIgniter\HTTP\IncomingRequest $request): array
    {
        $posts = $this->checkAllowedFields($posts, $this->resetPasswordAllowedFields);

        $sql = "select uuid, firstname, lastname, email from admins where email = ?";
        $admin = $this->db->query($sql, [$posts['email']])->getRow();

        if ($admin):

            /* 1. Transazione avviata solo se l'utente esiste (Ottimizzazione DB) */
            try {
                $token = new \App\Libraries\Token();
                $tokenHash = $token->getHash(config('BackendAuth')->hashKey);

                $time = (int) config('BackendAuth')->activationTime;

                $tokenCreate = date('Y-m-d H:i:s');
                $tokenExpire = date('Y-m-d H:i:s', time() + $time);

                $this->db->transBegin();

                $sql = "delete from admins_tokens where admin_uuid = ? and token_type = ?";
                $this->db->query($sql, [$admin->uuid, 'activation']);

                $sql = "insert into admins_tokens (admin_uuid, token_hash, token_create, token_expire, token_type, user_agent, ip) values(?,?,?,?,?,?,?)";
                $this->db->query($sql, [
                    $admin->uuid,
                    $tokenHash,
                    $tokenCreate,
                    $tokenExpire,
                    'activation',
                    $request->getUserAgent()->getAgentString(),
                    $request->getIPAddress()
                ]);

                if ($this->db->transStatus() === false):
                    $this->db->transRollback();
                    log_message('error', lang('backend/email.messages.resetPasswordFailed') . ' - ' . $e->getMessage());
                    return ['result' => 'resetPasswordFailed', 'message' => lang('backend/email.messages.resetPasswordFailed')];
                endif;

                $this->db->transCommit();

            } catch (\Throwable $e) {
                $this->db->transRollback();
                log_message('error', lang('backend/email.messages.resetPasswordFailed') . ' - ' . $e->getMessage());
                return ['result' => 'resetPasswordFailed', 'message' => lang('backend/email.messages.resetPasswordFailed')];
            }

            /* 2. Integrazione classe nativa Email e compilazione della vista */
            $emailData = [
                'firstname' => esc($admin->firstname),
                'lastname'  => esc($admin->lastname),
                'email'     => esc($admin->email),
                'token'     => $token->getValue()
            ];
            $emailHTML = view('backend/auth/partials/email/emailResetPasswordPartial', $emailData);

            $emailService = \Config\Services::email();
            $emailService->setTo(esc($admin->email));
            $emailService->setSubject(sprintf(lang('backend/email.auth.resetPassword.subjectResetPasswordEmail'), esc($admin->firstname), esc($admin->lastname)));
            $emailService->setMessage($emailHTML);

            /* 3. Coerenza invio fallito: blocca il redirect se la mail non parte */
            if (! $emailService->send()):

                $debugger = $emailService->printDebugger(['headers']);
                log_message('error', 'Errore SMTP: ' . $debugger);

                return ['result' => 'emailFailed', 'message' => lang('backend/email.messages.sendingEmailFailed')];
            endif;

        endif;

        return ['result' => true, 'message' => lang('backend/email.messages.sendingEmailSuccess')];
    }

    public function setPassword(array $posts): array
    {
        try 
        {
            $posts = $this->checkAllowedFields($posts, $this->setPasswordAllowedFields);

            /* 1. Recupero il token passato dal form (il nome deve combaciare con l'input hidden) */
            $token = new \App\Libraries\Token($posts['token']);
            $tokenHash = $token->getHash(config('BackendAuth')->hashKey);

            /* 2. Sostituito fetch() con getRow() */
            $sql = "select uuid, firstname, lastname from admins as u join admins_tokens as t on u.uuid = t.admin_uuid where t.token_hash = ? and t.token_type = ? limit 1";
            $admin = $this->db->query($sql, [$tokenHash, 'activation'])->getRow();

            if($admin):

                /* 3. Sintassi transazioni nativa CI4 */
                $this->db->transBegin();

                $sql = "update admins set password_hash = ?, resetted_at = ? where uuid = ?";
                $this->db->query($sql, [password_hash($posts['password'], PASSWORD_DEFAULT), null, $admin->uuid]);

                $sql = "delete from admins_tokens where admin_uuid = ? and token_type = ?";
                $this->db->query($sql, [$admin->uuid, 'activation']);

                /* 4. Verifica stato transazione prima del commit */
                if ($this->db->transStatus() === false):
                    $this->db->transRollback();
                    return ['result' => 'setPasswordFailed', 'message' => lang('backend/auth.messages.setPasswordError')];
                endif;

                $this->db->transCommit();

                $message = sprintf(lang('backend/auth.messages.setPasswordSuccess'), esc($admin->firstname), esc($admin->lastname));

                return ['result' => true, 'message' => $message];

            endif;

            return ['result' => 'setPasswordFailed', 'message' => lang('backend/auth.messages.setPasswordFailed')];

        } catch (\Throwable $e) {
            
            /* 5. Rollback di sicurezza solo se la transazione era effettivamente in corso */
            if ($this->db->transStatus() !== true):
                $this->db->transRollback();
            endif;

            log_message('error', lang('backend/auth.messages.setPasswordError') . ' - ' . $e->getMessage());
            
            /* Modificato false in 'setPasswordFailed' per coerenza con le aspettative del Controller */
            return ['result' => 'setPasswordFailed', 'message' => lang('backend/auth.messages.setPasswordError')];
        }
    }

    /* Verifica se il token di attivazione è valido e non scaduto */
    public function checkAuthToken(string $token): bool
    {
        try 
        {
            $tokenObj = new \App\Libraries\Token($token);
            $tokenHash = $tokenObj->getHash(config('BackendAuth')->hashKey);

            $sql = "select t.token_expire, t.admin_uuid, u.password_hash, u.email  
                from admins as u 
                join admins_tokens as t 
                on t.admin_uuid = u.uuid 
                where t.token_hash = ? 
                and t.token_type = ? 
                limit 1";

            $query = $this->db->query($sql, [$tokenHash, 'activation'])->getRow();

            if (($query) && (date('Y-m-d H:i:s') < $query->token_expire)):
                return true;
            endif;

            return false;

        } catch (\Throwable $e) {
            log_message('error', lang('backend/auth.messages.AuthTokenError') . ' - ' . $e->getMessage());
            return false;
        }
    }

    /* Logout basato su sessione */
    public function logoutBySession(): void
    {
        try 
        {
            if (session()->has('backendSession')):
                
                /* Recupera il token in chiaro dalla sessione */
                $sessionValue = session()->get('backendSession');
                $token = new \App\Libraries\Token($sessionValue);
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
        try 
        {
            /* Decifra il valore del cookie */
            $decryptedValue = service('crypto')->decrypt($cookieValue);

            if ($decryptedValue):
                /* Ricava l'hash dal token decifrato */
                $token = new \App\Libraries\Token($decryptedValue);
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