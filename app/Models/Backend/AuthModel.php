<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

/**
 * Modello transazionale per la gestione dei processi di autenticazione e sicurezza del backend.
 *
 * Questa classe governa i flussi ad alta sensibilità legati alla sicurezza degli accessi. Implementa
 * i meccanismi di verifica delle credenziali, la limitazione e il tracciamento dei tentativi falliti
 * per la mitigazione di attacchi Brute Force, l'intercettazione del secondo fattore di autenticazione (2FA),
 * il rilascio dei token di sessione/cookie e le procedure di ripristino sicuro delle password (procedura di reset).
 */
class AuthModel extends BackendModel
{
    private object $config;

    /**
     * Inizializza il modello ereditando i comportamenti base e caricando le configurazioni di sicurezza.
     *
     * Rinvigorisce l'istanza valorizzando l'espressione regolare per il controllo delle password
     * prelevandola centralmente dal file di configurazione della sicurezza backend.
     *
     * @return void
     */
    protected function initModel(): void 
    {
        parent::initModel();

        $this->config = setting('Backend\Auth');
    }

    /**
     * Elenco dei campi di input autorizzati per l'elaborazione del modulo di login.
     *
     * @var array
     */
    protected array $loginAllowedFields = ['email', 'password', 'rememberMe']; 

    /**
     * Elenco dei campi di input autorizzati per la richiesta di ripristino della password.
     *
     * @var array
     */
    protected array $resetPasswordAllowedFields = ['email'];

    /**
     * Elenco dei campi di input autorizzati per l'impostazione finale della nuova password.
     *
     * @var array
     */
    protected array $setPasswordAllowedFields = ['password', 'token'];

    /**
     * Elenco dei campi di input autorizzati per la verifica del secondo fattore di autenticazione.
     * @var array
     */
    protected array $verifyAllowedFields = ['code'];

    /**
     * Definisce le regole rigide di validazione per il modulo di autenticazione iniziale.
     *
     * Restituisce i vincoli per i campi email e password, applicando la regex di complessità
     * dinamica e associando messaggi di errore personalizzati per il backend.
     *
     * @return array Mappa delle regole e degli errori per il componente di validazione.
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
     * Definisce le regole di validazione per la richiesta di reset della password tramite email.
     *
     * Imposta i vincoli di obbligatorietà, formato standard dell'indirizzo email e pulizia
     * degli spazi vuoti tramite trim.
     *
     * @return array Mappa delle regole di validazione per l'email.
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
     * Definisce le regole di validazione per l'inserimento della nuova password di sblocco.
     *
     * Struttura i vincoli per la password (con regex), il controllo di uguaglianza del campo
     * di conferma e l'obbligatorietà del token di attivazione agganciato a una regola custom.
     *
     * @return array Mappa completa dei vincoli del modulo di impostazione password.
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
     * Definisce le regole di validazione per la verifica del codice del secondo fattore di autenticazione.
     * Imposta i vincoli di obbligatorietà, di essere un numero e di avere una lunghezza esatta di 6 cifre.
     * @return array Mappa delle regole di validazione per il codice.
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
     * Esegue la pipeline di controllo per l'autenticazione dell'amministratore nel sistema.
     *
     * Isola i campi consentiti, analizza i limiti di Brute Force basati sull'intervallo temporale,
     * interroga il database in modalità read-only (ottimizzazione delle prestazioni) e verifica l'hash
     * della password. Gestisce l'interruzione controllata per il Secondo Fattore (2FA) e traccia i fallimenti
     * aprendo transazioni atomiche solo in caso di effettiva scrittura sul database.
     *
     * @param array $posts I dati grezzi prelevati dal modulo di login.
     * @param \CodeIgniter\HTTP\IncomingRequest $request L'oggetto della richiesta HTTP corrente per IP e User Agent.
     * @return array Risultato dell'operazione contenente l'esito logico ed eventuali messaggi o stati (2fa).
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
                log_admin_activity(null, 'LOGIN_FAILED', 'auth', 'Tentativo di accesso con account inesistente');
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

                    log_admin_activity('LOGIN_BLOCKED', 'auth', sprintf('Accesso rifiutato, account bloccato %s %s', esc($admin->firstname), esc($admin->lastname)), $admin);

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

                log_admin_activity('LOGIN_FAILED', 'auth', sprintf('Tentativo di accesso fallito %s %s', esc($admin->firstname), esc($admin->lastname)), $admin);

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

                    log_admin_activity('2FA_REQUIRED', 'auth', sprintf('Richiesto codice di verifica 2FA (' . $twofa->method . ') %s %s', esc($admin->firstname), esc($admin->lastname)), $admin);

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
     * Finalizza la persistenza dello stato di login sul client e sul database ad autorizzazione avvenuta.
     *
     * Calcola i tempi di scadenza in base alla persistenza scelta (cookie/sessione), genera e inserisce un nuovo
     * token crittografico sul database, rigenera l'ID di sessione per prevenire attacchi di Session Fixation,
     * cifra il token per l'invio tramite cookie sicuro e imposta i messaggi flash di notifica interfaccia.
     *
     * @param object $admin Record anagrafico dell'amministratore autenticato.
     * @param bool $rememberMe Flag indicante la richiesta di persistenza a lungo termine via cookie.
     * @param \CodeIgniter\HTTP\IncomingRequest $request Oggetto della richiesta per l'estrazione dei metadati di tracciamento.
     * @return array Esito positivo della finalizzazione del login.
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
        $tokenExpire = date('Y-m-d H:i:s', time() + $time);

        /* 3. Pulizia dei vecchi token di tipo sessione se applicabile */
        if ($tokenType === 'session'):
            $sql = "delete from admins_tokens where admin_uuid = ? and token_type = ?";
            $this->db->query($sql, [$admin->uuid, 'session']);
        endif;

        /* 4. Registrazione del nuovo token nel database con i metodi nativi di CI4 */
        $userAgent = $request->getUserAgent()->getAgentString();
        $ip_address = $request->getIPAddress();

        $sql = "insert into admins_tokens (admin_uuid, token_hash, token_create, token_expire, token_type, user_agent, ip_address, created_at) values(?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $admin->uuid,
            $tokenHash,
            $tokenCreate,
            $tokenExpire,
            $tokenType,
            $userAgent,
            $ip_address, 
            date('Y-m-d H:i:s')
        ]);

        /* Chiude la transazione aperta nel metodo principale prima di impostare gli stati del client */
        $this->db->transCommit();

        log_admin_activity('LOGIN_SUCCESS', 'auth', sprintf('Accesso effettuato %s %s ', esc($admin->firstname), esc($admin->lastname)), $admin);

        /* 5. Rigenerazione dell'ID di sessione per prevenire Session Fixation */
        session()->regenerate(true);

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
     * Applica la nuova password associata a un token di attivazione valido e verificato.
     *
     * Risolve l'identità dell'utente attraverso l'hash del token fornito. In caso di riscontro, esegue una
     * transazione per aggiornare l'hash della password (PASSWORD_DEFAULT), azzera la data di reset e
     * revoca il token di attivazione utilizzato per impedire riutilizzi fraudolenti.
     *
     * @param array $posts Array di input contenente la nuova password e il token di sblocco.
     * @return array Array di risposta con l'esito dell'operazione e il messaggio per il client.
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

                log_admin_activity('RESET_PASSWORD_AUTH', 'auth', sprintf('Reset password %s %s ', esc($admin->firstname), esc($admin->lastname)), $admin);

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
     * Applica la nuova password associata a un token di attivazione valido e verificato.
     *
     * Risolve l'identità dell'utente attraverso l'hash del token fornito. In caso di riscontro, esegue una
     * transazione per aggiornare l'hash della password (PASSWORD_DEFAULT), azzera la data di reset e
     * revoca il token di attivazione utilizzato per impedire riutilizzi fraudolenti.
     *
     * @param array $posts Array di input contenente la nuova password e il token di sblocco.
     * @return array Array di risposta con l'esito dell'operazione e il messaggio per il client.
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
                    return ['result' => false, 'message' => lang('backend/auth.messages.setPasswordError')];
                endif;

                $this->db->transCommit();

                log_admin_activity('SET_PASSWORD', 'auth', sprintf('Impostazione password %s %s ', esc($admin->firstname), esc($admin->lastname)), $admin);

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
     * Ispeziona l'integrità e la validità temporale di un token di attivazione.
     *
     * Calcola l'hash del token in chiaro e interroga il database verificando la corrispondenza tipologica
     * con il tipo 'activation' e controllando che la data corrente sia inferiore alla data di scadenza del token.
     *
     * @param string $token Stringa del token in chiaro ricevuto dal client.
     * @return bool True se il token è valido e attivo, false in tutti gli altri casi.
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
     * Controlla e valida il codice del secondo fattore (2FA).
     *
     * Funzionamento del metodo:
     * -> Verifica se il secondo fattore (2FA) è attivo nel sistema.
     * -> Controlla se la sessione temporanea dell'utente è ancora valida.
     * -> Blocca i tentativi continui di inserimento per evitare attacchi forzati (Brute-Force).
     * -> Verifica se il codice inserito esiste (per Email) o corrisponde alla chiave (per TOTP).
     * -> Controlla se il codice è scaduto (distinguendo tra codice sbagliato e scaduto nel tempo).
     * -> Se il codice è corretto, pulisce i dati temporanei e completa il login dell'utente.
     *
     * @param array $posts I dati inviati dal modulo (contiene il codice inserito dall'utente).
     * @param \CodeIgniter\HTTP\IncomingRequest $request La richiesta del browser (serve per prendere l'IP dell'utente).
     * @return array Restituisce un array con le chiavi 'result' (bool) e 'message' (string).
     * @throws \CodeIgniter\Exceptions\PageNotFoundException Mostra un errore 404 se il 2FA è disattivato.
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
                log_admin_activity(null, 'VERIFY_FAILED', 'auth', 'Tentativo di accesso con account inesistente');
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

                log_admin_activity('2FA_BLOCKED', 'auth', sprintf('Blocco 2FA %s %s ', esc($admin->firstname), esc($admin->lastname)), $admin);

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

                log_admin_activity('2FA_FAILED', 'auth', sprintf('Codice 2FA errato o scaduto %s %s ', esc($admin->firstname), esc($admin->lastname)), $admin);

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
     * Esegue la distruzione della sessione di autenticazione standard.
     *
     * Verifica la presenza della chiave in sessione, ne estrae il valore in chiaro per ricavarne
     * l'hash di memorizzazione, elimina il record corrispondente sul database per revocare l'autorizzazione
     * e rimuove la chiave dallo storage di sessione del server.
     *
     * @return void
     */
    public function logoutBySession(): void
    {
        try 
        {
            if (session()->has('backendSession')):
                
                /* Recupera il token in chiaro dalla sessione */
                $sessionValue = session()->get('backendSession');
                $token = new \App\Libraries\Token($sessionValue);
                $tokenHash = $token->getHash($this->config->hashKey);

                /* Elimina il record dal database */
                $sql = "delete from admins_tokens where token_hash = ? and token_type = ?";
                $this->db->query($sql, [$tokenHash, 'session']);

                /* Svuota ESCLUSIVAMENTE la chiave con i dati di login */
                session()->remove('backendSession');

                /* Rigenera l'ID per prevenire la Session Fixation */
                session()->regenerate(true);

            endif;
        } catch (\Throwable $e) {
            log_message('error', lang('backend/auth.messages.logoutSessionError') . ' - ' . $e);
        }
    }

    /**
     * Esegue la distruzione del cookie di persistenza a lungo termine (Remember Me).
     *
     * Riceve il valore cifrato del cookie, provvede alla sua decifratura mediante il servizio di crittografia,
     * risale all'hash del token per rimuovere permanentemente la riga dal database dei token e invia
     * l'istruzione di cancellazione fisica del cookie al browser del client.
     *
     * @param string $cookieValue Valore crittografato prelevato dal cookie del client.
     * @return void
     */
    public function logoutByCookie(string $cookieValue): void
    {
        try 
        {
            /* Decifra il valore del cookie */
            $decryptedValue = service('crypto')->decrypt($cookieValue);

            if ($decryptedValue):
                /* Ricava l'hash dal token decifrato */
                $token = new \App\Libraries\Token($decryptedValue);
                $tokenHash = $token->getHash($this->config->hashKey);

                /* Elimina il record dal database */
                $sql = "delete from admins_tokens where token_hash = ? and token_type = ?";
                $this->db->query($sql, [$tokenHash, 'cookie']);
            endif;

            /* Rimuove il cookie fisicamente dal browser */
            delete_cookie('backendRememberMe');

            /* Rigenera l'ID per prevenire la Session Fixation */
            session()->regenerate(true);

        } catch (\Throwable $e) {
            log_message('error', lang('backend/auth.messages.logoutCookieError') . ' - ' . $e);
        }
    }
}