<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

/**
 * Modello principale dedicato all'Autenticazione e alla Sicurezza degli accessi (Auth).
 * 
 * Estende il BackendModel e incapsula tutta la complessa business logic legata all'ingresso 
 * nel sistema. Gestisce il flusso di Login a più step (incluso il controllo 2FA), il sistema 
 * anti brute-force (throttling dei tentativi falliti), il recupero delle credenziali, 
 * la generazione di token crittografici (sessioni o cookie "Remember Me") e la tracciatura 
 * rigorosa di ogni accesso o disconnessione nell'Audit Log.
 */
class AuthModel extends BackendModel
{
    /**
     * @var object Contenitore per i parametri di configurazione globali del modulo Auth 
     * (es. limiti di tentativi, durata sessioni, espressioni regolari per le password).
     */
    private object $config;

    /**
     * @var string|null Nome identificativo del modulo corrente, utilizzato per instradare i log o risolvere i template email.
     */
    public ?string $module = 'auth';

    /**
     * @var array Whitelist dei campi HTTP POST consentiti in fase di Login.
     * Blocca l'immissione di parametri non previsti prima dell'elaborazione delle credenziali.
     */
    protected array $loginAllowedFields = ['email', 'password', 'rememberMe']; 

    /**
     * @var array Whitelist dei campi consentiti per la richiesta di reset della password (solo email).
     */
    protected array $resetPasswordAllowedFields = ['email'];

    /**
     * @var array Whitelist dei campi consentiti durante l'impostazione fisica di una nuova password 
     * (tramite link di ripristino o attivazione).
     */
    protected array $setPasswordAllowedFields = ['password', 'token'];

    /**
     * @var array Whitelist dei campi consentiti durante la verifica OTP per il Secondo Fattore di Autenticazione (2FA).
     */
    protected array $verifyAllowedFields = ['code'];

    /**
     * Hook di inizializzazione nativo di CodeIgniter 4.
     * 
     * Richiama il setup genitore e inietta immediatamente in memoria le configurazioni 
     * del modulo Auth, rendendole disponibili a tutti i metodi della classe per evitare query ripetitive.
     */
    protected function initModel(): void 
    {
        parent::initModel();

        $this->config = setting('Backend\Auth');
    }

    /**
     * Regole di validazione per il form di Login.
     * 
     * Verifica che l'email sia in un formato valido e che la password rispetti rigorosamente 
     * l'espressione regolare di sicurezza (Regex) definita dinamicamente nelle configurazioni di sistema.
     *
     * @return array Regole native di CodeIgniter
     */
    public function validateLoginRules(): array
    {
        return [
            'email' => [
                'label' => 'Indirizzo email',
                'rules' => ['required', 'valid_email', 'trim']
            ], 
            'password' => [
                'label' => 'Password',
                'rules' => ['required', 'min_length[8]', 'max_length[255]', "regex_match[{$this->config->passwordRegex}]"],
                'errors' => [
                    'regex_match' => 'La password non rispetta i requisiti di sicurezza.'
                ]
            ]
        ];
    }

    /**
     * Regole di validazione per la richiesta di Reset della Password (Form "Password Dimenticata").
     * 
     * Controlla esclusivamente la correttezza formale dell'indirizzo email prima di interrogare il database.
     *
     * @return array Regole native di CodeIgniter
     */
    public function validateResetPasswordRules()
    {
        return [
            'email' => [
                'label' => 'Indirizzo email',
                'rules' => ['required', 'valid_email', 'trim'], 
            ],
        ];
    }

    /**
     * Regole di validazione per l'impostazione di una nuova password.
     * 
     * Verifica che la nuova password rispetti la Regex di sicurezza, che il campo di conferma combaci 
     * perfettamente (`matches[password]`) e applica una regola personalizzata (`checkTokenRule`) 
     * per validare l'autenticità del token nascosto inviato dal form.
     *
     * @return array Regole strutturate
     */
    public function validateSetPasswordRules()
    {
        return [
            'password' => [
                'label' => 'Password',
                'rules' => ['required', "regex_match[{$this->config->passwordRegex}]"],
                'errors' => [
                    'regex_match' => 'La password non rispetta i requisiti di sicurezza.'
                ]
            ], 
            'confirmPassword' => [
                'label' => 'Conferma password',
                'rules' => ['required', 'matches[password]'], 
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

    /**
     * Regole di validazione per la verifica del codice OTP (2FA).
     * 
     * Assicura che il codice immesso sia un numero intero naturale e che sia composto 
     * esattamente da 6 cifre (`exact_length[6]`), bloccando stringhe malformate o tentativi di injection.
     *
     * @return array Regole per il campo code
     */
    public function validateVerifyRules(): array
    {
        return [
            'code' => [
                'label' => 'Codice',
                /* CORRETTO: exact_length blocca la stringa a 6 caratteri, is_natural permette i numeri interi */
                'rules' => ['required', 'is_natural', 'exact_length[6]'], 
            ],
        ];
    }

    /**
     * Orchestratore principale del flusso di Autenticazione (Login).
     * 
     * Metodo denso e protetto:
     * 1. Filtra i dati in ingresso e legge le configurazioni (es. limiti tentativi, 2FA).
     * 2. Estrae l'utente verificando subito gli Scudi (deve essere attivo, non cestinato, non sospeso).
     * 3. Applica il controllo Throttling: se l'utente ha superato il limite di tentativi falliti nel tempo previsto, slitta il blocco e respinge l'accesso.
     * 4. Valida l'hash della password. Se fallisce, registra il tentativo errato a DB in transazione.
     * 5. Se la password è corretta ma il 2FA è attivo, parcheggia i dati sicuri in una sessione temporanea server-side, 
     *    invia l'eventuale OTP via email e avvisa il controller di richiedere il secondo fattore (`result => '2fa_required'`).
     * 6. Se il 2FA non è richiesto, azzera i tentativi falliti e demanda la creazione della sessione a `innerLogin()`.
     *
     * @param array $posts Credenziali pulite sottomesse dal form
     * @param \CodeIgniter\HTTP\IncomingRequest $request Richiesta per estrarre Indirizzo IP
     * @return array Risposta strutturata con esito e messaggi per il router o il client
     */
    public function login(array $posts, \CodeIgniter\HTTP\IncomingRequest $request)
    {
        try 
        {
            /* Inizializzazione variabili e parametri di configurazione */
            $posts = $this->checkAllowedFields($posts, $this->loginAllowedFields);

            $rememberMe = (isset($posts['rememberMe']) && $posts['rememberMe']) ? true : false;
            $ip_address = $request->getIPAddress();

            /* Lettura centralizzata delle configurazioni per evitare chiamate ridondanti */
            $allowAttempts = (bool) $this->config->attempts;
            $allowTwoFactor = (bool) $this->config->twoFactor;

            /* Costruzione della query di lettura iniziale dell'utente */
            if ($allowAttempts):
                $secondsInterval = (int) $this->config->attemptsInterval;
                $attemptsInterval = date('Y-m-d H:i:s', time() - $secondsInterval);
                
                /* Inserita clausola di sicurezza AND admins.deleted_at IS NULL */
                $sql = "select admins.uuid, admins.firstname, admins.lastname, admins.email, admins.password_hash, COUNT(admins_attempts.id) as times
                        from admins
                        left join admins_attempts
                        on admins_attempts.admin_uuid = admins.uuid and admins_attempts.timestamp > ?
                        where admins.email = ? and admins.status = 1 and admins.suspended_at IS NULL and admins.deleted_at IS NULL
                        group by admins.uuid limit 1";
                $params = [$attemptsInterval, $posts['email']];
            else:
                /* Inserita clausola di sicurezza AND deleted_at IS NULL */
                $sql = "select uuid, firstname, lastname, email, password_hash from admins where email = ? and status = 1 and suspended_at IS NULL and deleted_at IS NULL limit 1";
                $params = [$posts['email']];
            endif;

            /* Esecuzione della lettura (fuori transazione per ottimizzare le prestazioni) */
            $admin = $this->db->query($sql, $params)->getRow();

            /* Se l'utente non esiste, esce immediatamente con errore generico (sicurezza) */
            if ( ! $admin):
                log_admin_activity(null, 'LOGIN_REFUSED', 'auth', lang('backend/auth.audits.loginRefused'));
                return ['result' => false, 'message' => lang('backend/auth.messages.loginFailed')];
            endif;

            /* Controllo immediato del blocco tentativi */
            if ($allowAttempts && isset($admin->times)):
                if ($admin->times >= (int) $this->config->attemptsLimit):
                    
                    $this->db->transBegin();

                    /* Recuperiamo il timestamp dell'ultimo tentativo per questo specifico admin all'interno della finestra */
                    $sql = "select MAX(timestamp) as last_ts from admins_attempts where admin_uuid = ? and timestamp > ?";
                    $row = $this->db->query($sql, [$admin->uuid, $attemptsInterval])->getRow();

                    /* Se troviamo l'ultimo tentativo, ne aggiorniamo l'orario a questo istante per far slittare il blocco */
                    if ($row && $row->last_ts) :
                        $sql = "update admins_attempts set timestamp = ? where admin_uuid = ? and timestamp = ?";
                        $this->db->query($sql, [date('Y-m-d H:i:s'), $admin->uuid, $row->last_ts]);
                    endif;

                    $this->db->transCommit();

                    log_admin_activity('LOGIN_BLOCKED', 'auth', sprintf(lang('backend/auth.audits.loginBlocked'), esc($admin->firstname), esc($admin->lastname)), $admin);

                    return ['result' => false, 'message' => lang('backend/auth.messages.tooMAnyAttempts')];
                endif;
            endif;

            /* Verifica della password */
            if ( ! password_verify($posts['password'], $admin->password_hash)):
                
                /* La transazione si apre solo ora, poiché dobbiamo effettuare una scrittura sul DB */
                $this->db->transBegin();
                
                if ($allowAttempts):
                    $sql = "insert into admins_attempts (admin_uuid, ip_address, timestamp) values (?, ?, ?)";
                    $this->db->query($sql, [$admin->uuid, $ip_address, date('Y-m-d H:i:s')]);
                endif;

                $this->db->transCommit();

                log_admin_activity('LOGIN_FAILED', 'auth', sprintf(lang('backend/auth.audits.loginFailed'), esc($admin->firstname), esc($admin->lastname)), $admin);

                return ['result' => false, 'message' => lang('backend/auth.messages.loginFailed')];
                
            endif;

            /* Gestione del Secondo Fattore di Autenticazione (2FA) */
            if ($allowTwoFactor):

                $sql = "select method from admins_2fa where admin_uuid = ? and enabled = 1 limit 1";
                $twofa = $this->db->query($sql, [$admin->uuid])->getRow();

                if ($allowAttempts):
                    $sqlClearAttempts = "delete from admins_attempts where admin_uuid = ?";
                    $this->db->query($sqlClearAttempts, [$admin->uuid]);
                endif;

                if ($twofa):
                    /* IMPLEMENTAZIONE SICURA: Scrittura dei dati sensibili in sessione server protetta */
                    session()->set('auth_2fa_pending', ['admin_uuid'  => $admin->uuid, 'rememberMe' => $rememberMe, 'method' => $twofa->method]);

                    /* Eliminazione preventiva di eventuali codici presenti in admins_2fa_codes per l'utente corrente */
                    $sql = "delete from admins_2fa_codes where admin_uuid = ?";
                    $this->db->query($sql, [$admin->uuid]);

                    if ($twofa->method === 'email'):
                        (new \App\Libraries\EmailOtpService())->send($admin->uuid);
                    endif;

                    log_admin_activity('2FA_REQUIRED', 'auth', sprintf(lang('backend/auth.audits.2faRequired'), esc($twofa->method), esc($admin->firstname), esc($admin->lastname)), $admin);

                    /* Il client riceve solo la notifica del successo parziale senza dati sensibili esposti */
                    return ['result' => '2fa_required', 'method' => $twofa->method];
                endif;

            endif;

            /* Fase finale del Login (Password e 2FA non richiesto) */
            $this->db->transBegin();

            if ($allowAttempts):
                $sql = "delete from admins_attempts where admin_uuid = ?";
                $this->db->query($sql, [$admin->uuid]);
            endif;

            /* Chiusura della transazione prima del passaggio di consegne */
            $this->db->transCommit();

            /* Delega la finalizzazione (creazione sessioni/cookie) al metodo interno */
            return $this->innerLogin($admin, $rememberMe, $request);

        } catch (\Throwable $e) {
            /* In CodeIgniter 4 si esegue il rollback sicuro verificando lo stato interno del database */
            if ($this->db->transStatus() === false):
                $this->db->transRollback();
            endif;
            
            log_message('error', lang('backend/auth.messages.loginFailed') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/auth.messages.loginFailed')];
        }
    }

    /**
     * Conclude positivamente l'autenticazione generando i token e i cookie necessari.
     * 
     * Valuta se l'utente ha richiesto la funzione "Ricordami" per decidere la scadenza (cookie lungo o sessione breve).
     * Genera un nuovo token crittografico univoco, lo salva nella tabella `admins_tokens`, 
     * e inserisce un record di ingresso (Login) nella tabella `admins_logs` per l'auditing.
     * Successivamente rigenera l'ID di sessione PHP (protezione da Session Fixation) e imposta 
     * il cookie cifrato o la sessione standard, restituendo al frontend un messaggio flash di benvenuto.
     *
     * @param object $admin I dati validati dell'amministratore
     * @param bool $rememberMe Flag che indica se il client ha spuntato "Ricordami"
     * @param \CodeIgniter\HTTP\IncomingRequest $request Richiesta per tracciare User Agent e IP
     * @return array Esito positivo
     */
    private function innerLogin(object $admin, bool $rememberMe, \CodeIgniter\HTTP\IncomingRequest $request): array
    {
        if ($rememberMe):
            $time = (int) $this->config->rememberMeTime;
            $tokenType = 'cookie';
        else:
            $time = (int) $this->config->sessionTime;
            $tokenType = 'session';
        endif;

        $token = new \App\Libraries\Token();
        $tokenHash = $token->getHash($this->config->hashKey);

        /* Generazione delle stringhe DATETIME corrette per admins_tokens */
        $tokenCreate = date('Y-m-d H:i:s');
        $lastActivity = date('Y-m-d H:i:s');
        $tokenExpire = date('Y-m-d H:i:s', time() + $time);

        /* 3. Pulizia dei vecchi token di tipo sessione se applicabile */
        if ($tokenType === 'session'):
            $sql = "delete from admins_tokens where admin_uuid = ? and token_type = ?";
            $this->db->query($sql, [$admin->uuid, 'session']);
        endif;

        /* 4. Registrazione del nuovo token nel database con i metodi nativi di CI4 */
        $userAgent = $request->getUserAgent()->getAgentString();
        $ip_address = $request->getIPAddress();

        $sql = "insert into admins_tokens (admin_uuid, token_hash, token_create, last_activity, token_expire, token_type, user_agent, ip_address, created_at) values(?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $admin->uuid,
            $tokenHash,
            $tokenCreate,
            $lastActivity, 
            $tokenExpire,
            $tokenType,
            $userAgent,
            $ip_address, 
            date('Y-m-d H:i:s')
        ]);

        $token_id = $this->db->insertID();

        $sql = "insert into admins_logs (token_id, admin_uuid, username, login, token_type, user_agent, ip_address, created_at) values (?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $token_id, 
            $admin->uuid,
            $admin->email, /* Disponibile grazie alla SELECT in login() */
            date('Y-m-d H:i:s'),
            $tokenType,
            $userAgent,
            $ip_address,
            date('Y-m-d H:i:s')
        ]);

        /* Recupero immediato dell'ID autogenerato del log per il futuro logout */
        $loginLogId = $this->db->insertID();

        /* Chiude la transazione aperta nel metodo principale prima di impostare gli stati del client */
        $this->db->transCommit();

        log_admin_activity('LOGIN_SUCCESS', 'auth', sprintf(lang('backend/auth.audits.loginSuccess'), esc($admin->firstname), esc($admin->lastname)), $admin);

        /* 5. Rigenerazione dell'ID di sessione per prevenire Session Fixation */
        session()->regenerate(true);

        /* Salvataggio dell'ID del log di accesso per aggiornarlo al momento del logout */
        session()->set('login_log_id', $loginLogId);

        /* 6. Assegnazione del token al client (con cifratura per il cookie Remember Me) */
        if ($rememberMe):
            /* Cifratura del token raw tramite il servizio globale prima di inserirlo nel cookie */
            $encryptedToken = service('crypto')->encrypt($token->getValue());

            /* Utilizzo della funzione nativa set_cookie di CI4 per l'impostazione sicura del cookie */
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
        $welcomeMessage = sprintf(lang('backend/auth.messages.welcome'), esc($admin->firstname), esc($admin->lastname));
        
        session()->setFlashdata([
            'message' => $welcomeMessage,
            'class'   => 'light text-success fw-bold',
            'icon'    => '<i class="fa-solid fa-handshake"></i>'
        ]);

        return ['result' => true];
    }

    /**
     * Gestisce la richiesta di ripristino per "Password dimenticata".
     * 
     * Cerca l'utente tramite email, assicurandosi che non sia cestinato. Se trovato, 
     * inizia una transazione per generare un token di attivazione univoco, ne calcola la scadenza, 
     * lo salva a DB eliminando eventuali vecchi token pendenti e invia l'email transazionale 
     * contenente il link di recupero.
     *
     * @param array $posts Dati POST contenenti l'email
     * @param \CodeIgniter\HTTP\IncomingRequest $request Richiesta HTTP per logging dati connessione
     * @return array Esito dell'operazione e relativo messaggio utente
     */
    public function resetPassword(array $posts, \CodeIgniter\HTTP\IncomingRequest $request): array
    {
        $posts = $this->checkAllowedFields($posts, $this->resetPasswordAllowedFields);

        $sql = "select uuid, firstname, lastname, email from admins where email = ? and deleted_at IS NULL";
        $admin = $this->db->query($sql, [$posts['email']])->getRow();

        if ($admin):

            /* 1. Transazione avviata solo se l'utente esiste (Ottimizzazione DB) */
            try {
                $token = new \App\Libraries\Token();
                $tokenHash = $token->getHash($this->config->hashKey);

                $time = (int) $this->config->activationTime;

                $tokenCreate = date('Y-m-d H:i:s');
                $tokenExpire = date('Y-m-d H:i:s', time() + $time);

                $this->db->transBegin();

                $sql = "update admins set resetted_at = ? where uuid = ?";
                $this->db->query($sql, [date('Y-m-d H:i:s'), $admin->uuid]);

                /* Eliminiamo eventuali token di attivazione precedenti ancora attivi o scaduti per questo specifico admin */
                $sql = "delete from admins_tokens where admin_uuid = ? and token_type = ?";
                $this->db->query($sql, [$admin->uuid, 'activation']);

                $sql = "insert into admins_tokens (admin_uuid, token_hash, token_create, token_expire, token_type, user_agent, ip_address, created_at) values(?,?,?,?,?,?,?,?)";
                $this->db->query($sql, [$admin->uuid,$tokenHash, $tokenCreate, $tokenExpire, 'activation', $request->getUserAgent()->getAgentString(), $request->getIPAddress(), date('Y-m-d H:i:s')]);

                if ($this->db->transStatus() === false):
                    $this->db->transRollback();
                    log_message('error', lang('backend/auth.messages.resetPasswordFailed'));
                    return ['result' => false, 'message' => lang('backend/auth.messages.resetPasswordFailed')];
                endif;

                $this->db->transCommit();

                log_admin_activity('RESET_PASSWORD_AUTH', 'auth', sprintf(lang('backend/auth.audits.resetPasswordAuth'), esc($admin->firstname), esc($admin->lastname)), $admin);

            } catch (\Throwable $e) {
                $this->db->transRollback();
                log_message('error', lang('backend/auth.messages.resetPasswordFailed') . ' - ' . $e);
                return ['result' => false, 'message' => lang('backend/auth.messages.resetPasswordFailed')];
            }

            /* 2. Istanzio il servizio email dedicato e tento l'invio */
            $emailService = new \App\Libraries\Backend\EmailService();

            /* Configuro i parametri dinamici per questa specifica chiamata */
            $module = $this->module;
            $template = 'emailResetPasswordPartial';
            $subjectLangKey = 'backend/email.auth.resetPassword.subjectResetPasswordEmail';

            /* Chiamata al metodo con i nuovi parametri separati e gestione dei ritorni */
            if ( ! $emailService->sendActivationEmail($admin, $token->getValue(), $module, $template, $subjectLangKey)):

                $message = sprintf(lang('backend/auth.messages.resetPasswordSuccessNoEmail'), esc($admin->firstname), esc($admin->lastname));
                return ['result' => false, 'message' => $message];
                
            else:
                
                $message = sprintf(lang('backend/auth.messages.resetPasswordSuccess'), esc($admin->firstname), esc($admin->lastname));
                return ['result' => true, 'message' => $message];
                
            endif;

        endif;

        /* Fallback di sicurezza se l'admin non viene trovato nel database */
        return ['result' => false, 'message' => lang('backend/auth.messages.resetPasswordFailed')];
    }

    /**
     * Salva la nuova password dopo la convalida del token di reset/attivazione.
     * 
     * Recupera l'utente a partire dall'hash del token fornito. Esegue in transazione 
     * la rigenerazione dell'hash della password (`password_hash`), azzera la data di reset 
     * ed elimina il token ormai consumato (monouso) dal database, chiudendo il ciclo di recupero.
     *
     * @param array $posts Dati POST contenenti la nuova password e il token
     * @return array Esito del salvataggio
     */
    public function setPassword(array $posts): array
    {
        try
        {
            $posts = $this->checkAllowedFields($posts, $this->setPasswordAllowedFields);

            /* 1. Recupero il token passato dal form (il nome deve combaciare con l'input hidden) */
            $token = new \App\Libraries\Token($posts['token']);
            $tokenHash = $token->getHash($this->config->hashKey);

            /* 2. Sostituito fetch() con getRow() */
            $sql = "select uuid, firstname, lastname, email from admins as u join admins_tokens as t on u.uuid = t.admin_uuid where t.token_hash = ? and t.token_type = ? limit 1";
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
                    return ['result' => false, 'message' => lang('backend/auth.messages.setPasswordError')];
                endif;

                $this->db->transCommit();

                log_admin_activity('SET_PASSWORD', 'auth', sprintf(lang('backend/auth.audits.setPassword'), esc($admin->firstname), esc($admin->lastname)), $admin);

                $message = sprintf(lang('backend/auth.messages.setPasswordSuccess'), esc($admin->firstname), esc($admin->lastname));

                return ['result' => true, 'message' => $message];

            endif;

            return ['result' => false, 'message' => lang('backend/auth.messages.setPasswordFailed')];

        } catch (\Throwable $e) {

            /* 5. Rollback di sicurezza solo se la transazione era effettivamente in corso */
            if ($this->db->transStatus() !== true):
                $this->db->transRollback();
            endif;

            log_message('error', lang('backend/auth.messages.setPasswordError') . ' - ' . $e);

            /* Modificato false in 'setPasswordFailed' per coerenza con le aspettative del Controller */
            return ['result' => false, 'message' => lang('backend/auth.messages.setPasswordError')];
        }
    }

    /**
     * Verifica l'autenticità e la validità temporale di un token di reset/attivazione.
     * 
     * Metodo di utilità usato frequentemente per proteggere le rotte (es. form di reset password). 
     * Controlla che il token esista nel database e che il timestamp attuale non abbia 
     * superato la data di scadenza prestabilita (`token_expire`).
     *
     * @param string $token La stringa raw del token da verificare
     * @return bool True se il token è valido e non scaduto, false altrimenti
     */
    public function checkAuthToken(string $token): bool
    {
        try 
        {
            $tokenObj = new \App\Libraries\Token($token);
            $tokenHash = $tokenObj->getHash($this->config->hashKey);

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
            log_message('error', lang('backend/auth.messages.AuthTokenError') . ' - ' . $e);
            return false;
        }
    }

    /**
     * Convalida l'inserimento del codice OTP (Autenticazione a Due Fattori).
     * 
     * Estrae in sicurezza i dati pre-autorizzati dalla sessione temporanea `auth_2fa_pending`. 
     * Applica il Throttling specifico per il 2FA: se si sbaglia il codice troppe volte, rinnova il blocco e respinge.
     * Verifica il codice immesso valutando sia la corrispondenza (Tramite App Authenticator o DB per OTP Email) 
     * sia la scadenza temporale. In caso di errore, incrementa i fallimenti in transazione. 
     * In caso di successo, svuota le tabelle temporanee, distrugge la sessione di parcheggio e 
     * finalizza l'ingresso invocando `innerLogin()`.
     *
     * @param array $posts Il payload contenente il codice (OTP)
     * @param \CodeIgniter\HTTP\IncomingRequest $request Richiesta HTTP per l'estrazione dell'IP
     * @return array Esito dell'operazione (successo o messaggio d'errore specifico se scaduto o errato)
     */
    public function verify(array $posts, \CodeIgniter\HTTP\IncomingRequest $request): array
    {
        try 
        {
            $posts = $this->checkAllowedFields($posts, $this->verifyAllowedFields);

            $config = $this->config;
            $ip_address = $request->getIPAddress();

            /* Recupero e validazione immediata della sessione protetta temporanea */
            $sessionData = session()->get('auth_2fa_pending');
            if ((empty($sessionData)) || ( ! isset($sessionData['admin_uuid']))):
                return ['result' => false, 'message' => lang('backend/auth.messages.sessionExpired')];
            endif;

            $adminUuid  = (string) $sessionData['admin_uuid'];
            $method     = (string) $sessionData['method'];
            $rememberMe = (bool) $sessionData['rememberMe'];

            /* Recupero l'oggetto anagrafico dell'admin per il login finale, bloccando rigorosamente i record cestinati */
            $admin = $this->db->query("select * from admins where uuid = ? and status = 1 and deleted_at IS NULL limit 1", [$adminUuid])->getRow();
            if ( ! $admin):
                log_admin_activity(null, 'VERIFY_FAILED', 'auth', lang('backend/auth.audits.verifyFailed'));
                return ['result' => false, 'message' => lang('backend/auth.messages.verifyFailed')];
            endif;

            /* Controllo Throttling Anti Brute-Force (2FA) */
            $cutoffTime = date('Y-m-d H:i:s', time() - (int)$config->twoFactorTime);

            /* Conteggio tentativi falliti */
            $sql = "select COUNT(id) as cnt from admins_2fa_attempts where admin_uuid = ? and method = ? and timestamp > ?";
            $cntRow = $this->db->query($sql, [$adminUuid, $method, $cutoffTime])->getRow();

            if ($cntRow && (int) $cntRow->cnt >= (int) $config->twoFactorLimit):
                
                $this->db->transBegin();

                /* Aggiorna il timestamp dell'ultimo tentativo fallito per mantenere attivo il blocco */
                $sql = "select MAX(timestamp) as last_ts from admins_2fa_attempts where admin_uuid = ? and method = ? and timestamp > ?";
                $row = $this->db->query($sql, [$adminUuid, $method, $cutoffTime])->getRow();

                if ($row && $row->last_ts):
                    $sql = "update admins_2fa_attempts set timestamp = ? where admin_uuid = ? and method = ? and timestamp = ?";
                    $this->db->query($sql, [date('Y-m-d H:i:s'), $adminUuid, $method, $row->last_ts]);
                endif;

                $this->db->transCommit();

                log_admin_activity('2FA_BLOCKED', 'auth', sprintf(lang('backend/auth.audits.2faBlocked'), esc($admin->firstname), esc($admin->lastname)), $admin);

                return ['result' => false, 'message' => lang('backend/auth.messages.tooManyAttempts')];
            endif;

            /* Validazione del codice OTP (TOTP o Email) */
            $isValidCode = false;
            $isExpired = false; 

            if ($method === 'totp'):
                $sql = "select secret from admins_2fa where admin_uuid = ? and method = 'totp' and enabled = 1 limit 1";
                $row = $this->db->query($sql, [$adminUuid])->getRow();

                if ($row && ! empty($row->secret)):
                    $isValidCode = (new \App\Libraries\AppOtpService())->verify($row->secret, $posts['code']);
                endif;

            elseif ($method === 'email'):
                /* Prima query: controlliamo solo se il codice inserito esiste per questo utente */
                $sql = "select expires_at from admins_2fa_codes where admin_uuid = ? and code = ? limit 1";
                $row = $this->db->query($sql, [$adminUuid, $posts['code']])->getRow();

                if ( ! $row):
                    /* Il codice non esiste nel DB: è sbagliato */
                    $isValidCode = false;
                else:
                    /* Il codice esiste! Adesso controlliamo se è scaduto rispetto a questo momento */
                    if (date('Y-m-d H:i:s') > $row->expires_at):
                        $isValidCode = false;
                        $isExpired = true; /* Segnaliamo che il problema è il tempo */
                    else:
                        $isValidCode = true;
                    endif;
                endif;
            endif;

            /* Gestione Esito Validazione */
            $this->db->transBegin();

            if ( ! $isValidCode):
                /* Qualsiasi fallimento conta come tentativo errato per la sicurezza */
                $sql = "insert into admins_2fa_attempts (admin_uuid, method, ip_address, timestamp) values (?, ?, ?, ?)";
                $this->db->query($sql, [$adminUuid, $method, $ip_address, date('Y-m-d H:i:s')]);

                $this->db->transCommit();

                log_admin_activity('2FA_FAILED', 'auth', sprintf(lang('backend/auth.audits.2faFailed'), esc($admin->firstname), esc($admin->lastname)), $admin);

                /* Scegliamo il messaggio specifico in base allo stato */
                $errorMessage = $isExpired ? lang('backend/auth.messages.expiredCode') : lang('backend/auth.messages.wrongCode');

                return ['result' => false, 'message' => $errorMessage];
            endif;

            /* Codice Corretto: Pulizia tabelle temporanee dell'utente */
            $sql = "delete from admins_2fa_attempts where admin_uuid = ? and method = ?";
            $this->db->query($sql, [$adminUuid, $method]);

            $sql = "delete from admins_2fa_codes where admin_uuid = ?";
            $this->db->query($sql, [$adminUuid]);

            $this->db->transCommit();

            /* Rimozione tassativa della sessione temporanea e Finalizzazione Login */
            session()->remove('auth_2fa_pending');

            return $this->innerLogin($admin, $rememberMe, $request);

        } catch (\Throwable $e) {
            /* Forza tassativamente il rollback se c'è una transazione attiva al momento del crash */
            $this->db->transRollback();

            log_message('error', 'Errore nel metodo verify 2FA: ' . $e->getMessage());
            return ['result' => false, 'message' => lang('backend/auth.messages.verifyError')];
        }
    }

    /**
     * Esegue il Logout (Disconnessione) per gli utenti autenticati via Sessione standard.
     * 
     * Recupera il token dalla sessione locale, identifica il log aperto nella tabella `admins_logs` 
     * (tramite `login_log_id` salvato durante il login) e lo chiude formalmente inserendo 
     * l'ora di uscita (`last_activity`) e la causale (es. 'manual' o 'timeout'). 
     * Infine distrugge il record del token dal database e resetta completamente la sessione PHP locale.
     *
     * @param string $reason Motivo della disconnessione (default: 'manual')
     */
    public function logoutBySession(string $reason = 'manual'): void
    {
        try {
            if (session()->has('backendSession')):
                
                $sessionValue = session()->get('backendSession');
                $token = new \App\Libraries\Token($sessionValue);
                $tokenHash = $token->getHash($this->config->hashKey);

                if (session()->has('login_log_id')):
                    $logId = session()->get('login_log_id');

                    /* 1. Guard Clause: Verifico lo stato attuale del log */
                    $sqlLogCheck = "select logout_reason from admins_logs where id = ?";
                    $logRow = $this->db->query($sqlLogCheck, [$logId])->getRow();

                    /* 2. Se il log è aperto (null), procedo alla chiusura formale */
                    if ($logRow && is_null($logRow->logout_reason)):

                        /* Leggo last_activity PRIMA di eliminare il token */
                        $sqlSelect = "select last_activity from admins_tokens where token_hash = ? and token_type = ?";
                        $tokenRow = $this->db->query($sqlSelect, [$tokenHash, 'session'])->getRow();
                        
                        $logoutTime = ($tokenRow && $reason === 'timeout' && ! empty($tokenRow->last_activity)) ? $tokenRow->last_activity : date('Y-m-d H:i:s');

                        /* 3. Scrivo la disconnessione nel log */
                        $sqlLogUpdate = "update admins_logs set logout = ?, logout_reason = ? where id = ?";
                        $this->db->query($sqlLogUpdate, [$logoutTime, $reason, $logId]);

                    endif;

                    /* Rimuovo il tracciante locale a prescindere dall'esito */
                    session()->remove('login_log_id');
                endif;

                /* 4. Elimino fisicamente il token (se non è già stato eliminato da altri) */
                $sqlDelete = "delete from admins_tokens where token_hash = ? and token_type = ?";
                $this->db->query($sqlDelete, [$tokenHash, 'session']);

                /* 5. Distruzione sessione locale */
                session()->remove('backendSession');
                session()->regenerate(true);

            endif;
        } catch (\Throwable $e) {
            log_message('error', lang('backend/auth.messages.logoutSessionError') . ' - ' . $e);
        }
    }

    /**
     * Esegue il Logout (Disconnessione) per gli utenti autenticati via Cookie "Ricordami".
     * 
     * Funzionamento analogo a `logoutBySession`, ma estrae l'hash partendo dal valore crittografato 
     * del cookie inviato dal browser. Chiude il log aperto registrando il timestamp di logout 
     * ed elimina definitivamente il token di tipo 'cookie' dal database, invalidando gli accessi futuri.
     *
     * @param string $cookieValue Il valore decifrato del cookie 'backendRememberMe'
     * @param string $reason Motivo della disconnessione (default: 'manual')
     */
    public function logoutByCookie(string $cookieValue, string $reason = 'manual'): void
    {
        try {
            $token = new \App\Libraries\Token($cookieValue);
            $tokenHash = $token->getHash($this->config->hashKey);

            if (session()->has('login_log_id')):
                $logId = session()->get('login_log_id');

                /* 1. Guard Clause: Verifico lo stato attuale del log */
                $sqlLogCheck = "select logout_reason from admins_logs where id = ?";
                $logRow = $this->db->query($sqlLogCheck, [$logId])->getRow();

                /* 2. Se il log è aperto (null), procedo alla chiusura formale */
                if ($logRow && is_null($logRow->logout_reason)):

                        /* Leggo last_activity PRIMA di eliminare il token */
                        $sqlSelect = "select last_activity from admins_tokens where token_hash = ? and token_type = ?";
                        $tokenRow = $this->db->query($sqlSelect, [$tokenHash, 'cookie'])->getRow();
                        
                        $logoutTime = ($tokenRow && $reason === 'timeout' && ! empty($tokenRow->last_activity)) ? $tokenRow->last_activity : date('Y-m-d H:i:s');

                        /* 3. Scrivo la disconnessione nel log */
                        $sqlLogUpdate = "update admins_logs set logout = ?, logout_reason = ? where id = ?";
                        $this->db->query($sqlLogUpdate, [$logoutTime, $reason, $logId]);

                endif;

                /* Rimuovo il tracciante locale a prescindere dall'esito */
                session()->remove('login_log_id');
            endif;

            /* 4. Elimino fisicamente il token (se non è già stato eliminato da altri) */
            $sqlDelete = "delete from admins_tokens where token_hash = ? and token_type = ?";
            $this->db->query($sqlDelete, [$tokenHash, 'cookie']);

        } catch (\Throwable $e) {
            log_message('error', lang('backend/auth.messages.logoutCookieError') . ' - ' . $e);
        }
    }
}