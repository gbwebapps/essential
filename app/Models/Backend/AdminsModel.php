<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

class AdminsModel extends BackendModel
{
    protected ?string $module = 'admins';

    protected ?string $defaultColumn = 'created_at';

    /* @var array Campi consentiti per la visualizzazione Tabella */
    protected array $showAllAllowedFields = ['column', 'order', 'page', 'rows', 'searchFields'];

    /* @var array Campi consentiti per la creazione di un nuovo record */
    protected array $addAllowedFields = ['firstname', 'lastname', 'email', 'phone', 'status', 'note', 'permissions', 'images', 'documents'];

    /* @var array Campi consentiti per l'aggiornamento di un record */
    protected array $editAllowedFields = ['uuid', 'firstname', 'lastname', 'email', 'phone', 'status', 'note', 'permissions', 'images', 'documents'];

    /* @var array Campi consentiti per l'operazione di eliminazione */
    protected array $delAllowedFields = ['uuid'];

    /* @var array Campi consentiti per l'operazione di eliminazione */
    protected array $resetPasswordAllowedFields = ['uuid'];

    /* @var array Campi consentiti per il cambio di stato attivo/inattivo */
    protected array $changeStatusAllowedFields = ['uuid'];

    /* @var array Campi consentiti per il cambio permesso on fly */
    protected array $changePermissionAllowedFields = ['uuid', 'permission'];

    /* @var array Mapping tra indici ShowAll e colonne reali del database */
    protected array $allowedOrderColumns = ['firstname', 'lastname', 'email', 'phone', 'status']; 

    /* @var array Campi di ricerca consentiti in showAll */
    protected array $showAllSearchAllowedFields = ['firstname', 'lastname', 'email', 'phone']; 

    protected array $toCompare = ['firstname', 'lastname', 'email', 'phone', 'status', 'note'];

    /* @var string Query per selezionare tutti gli admins */
    protected ?string $getDataQuery = "select uuid, firstname, lastname, email, phone, status, created_at, updated_at, resetted_at, suspended_at,
                                        (select images.filename from images where images.entity_uuid = admins.uuid and images.entity = 'admins' and images.is_cover = 1 limit 1) as cover, 
                                        (select count(*) from images where images.entity_uuid = admins.uuid and images.entity = 'admins') as images_num, 
                                        (select count(*) from documents where documents.entity_uuid = admins.uuid and documents.entity = 'admins') as docs_num 
                                        from admins where master <> ? and uuid <> ?";

    /* @var string Query per selezionare un admin */
    protected ?string $getUUIDQuery = "select uuid, firstname, lastname, email, phone, status, master, note, created_at, updated_at, suspended_at, resetted_at from admins where uuid = ? limit 1";

    protected ?string $getNumRowsQuery = 'select count(*) as num from admins where master <> ? and uuid <> ?';

    protected function initModel(): void 
    {
        parent::initModel();
    }

    public function showAllValidationRules(): array
    {
        return [
            'column' => [
                'rules' => ['required', 'alpha_dash'] 
            ],
            'order' => [
                'rules' => ['required', 'in_list[asc,desc]'] 
            ],
            'page' => [
                'rules' => ['required', 'is_natural_no_zero'] 
            ],
            'rows' => [
                'rules' => ['required', 'is_natural_no_zero'] 
            ],
        ];
    }

    public function showAllSearchValidationRules(): array
    {
        return [
            'searchFields.firstname' => [
                'label' => lang('backend/admins.labels.firstname'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'], 
            ],
            'searchFields.lastname' => [
                'label' => lang('backend/admins.labels.lastname'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'], 
            ],
            'searchFields.email' => [
                'label' => lang('backend/admins.labels.email'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-Z0-9@._-]+$/]'], 
            ],
            'searchFields.phone' => [
                'label' => lang('backend/admins.labels.phone'), 
                'rules' => ['permit_empty', 'regex_match[/^[0-9+\-\s()]+$/]'], 
            ],
        ];
    }

    public function addValidationRules(): array
    {
        return [
            'firstname' => [
                'label' => lang('backend/admins.labels.firstname'),
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'],
            ],
            'lastname' => [
                'label' => lang('backend/admins.labels.lastname'),
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'],
            ],
            'email' => [
                'label' => lang('backend/admins.labels.email'),
                'rules' => ['required', 'trim', 'valid_email', 'max_length[255]', 'is_unique[admins.email]'],
            ],
            'phone' => [
                'label' => lang('backend/admins.labels.phone'),
                'rules' => ['required', 'trim', 'is_unique[admins.phone]', 'regex_match[/^\+?[0-9]{9,15}$/]'],
            ],
            'status' => [
                'label' => lang('backend/admins.labels.status'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'note' => [
                'label' => lang('backend/admins.labels.note'),
                'rules' => ['permit_empty', 'trim', 'max_length[500]', 'safeText'],
                'errors' => [
                    'safeText' => 'caratteri non ammessi'
                ]
            ],
            'permissions.*' => [
                'label' => lang('backend/admins.labels.permissions'), 
                'rules' => ['permit_empty', 'alpha_dash'], 
                'errors' => [
                    'alpha_dash' => lang('backend/admins.messages.wrongPermissionsFormat')
                ]
            ]
        ];
    }

    public function editValidationRules(array $posts): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', "is_unique[admins.uuid,uuid,{$posts['uuid']}, 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]']"],
            ],
            'firstname' => [
                'label' => lang('backend/admins.labels.firstname'),
                'rules' => ['required', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'], 
            ],
            'lastname' => [
                'label' => lang('backend/admins.labels.lastname'),
                'rules' => ['required', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'], 
            ],
            'email' => [
                'label' => lang('backend/admins.labels.email'),
                'rules' => ['required', 'valid_email'],
            ],
            'phone' => [
                'label' => lang('backend/admins.labels.phone'),
                'rules' => ['required', "is_unique[admins.phone,uuid,{$posts['uuid']},'regex_match[/^[0-9]{9,10}$/]']" 
                ],
            ],
            'status' => [
                'label' => lang('backend/admins.labels.status'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'notes' => [
                'label' => lang('backend/admins.labels.note'),
                'rules' => ['permit_empty','max_length[500]','regex_match[/^[^<>\x60]*$/su]'],
            ],
        ];
    }

    public function delValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
        ];
    }

    public function resetPasswordValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
        ];
    }

    public function changeStatusValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
            'context' => [
                'label' => lang('backend/admins.labels.context'),
                'rules' => ['permit_empty', 'in_list[show]'],
            ],
        ];
    }

    /* Validazione per il cambio del permesso on fly */
    public function changePermissionValidationRules(): array 
    {
        /* Recupero l'array multidimensionale dalla configurazione */
        $rawPermissions = config('BackendPermissions')->getPermissions();

        /* Estraggo solo le chiavi (es. 'users_index') ciclando i gruppi */
        $validKeys = [];
        foreach ($rawPermissions as $group):
            $validKeys = array_merge($validKeys, array_keys($group['perms']));
        endforeach;

        /* Implodo l'array piatto ottenuto per formare la stringa richiesta da in_list */
        $inListString = implode(',', $validKeys);

        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
            'permission' => [
                'label' => lang('backend/admins.labels.permissions'),
                'rules' => ['required', 'in_list[' . $inListString . ']'],
            ]
        ];
    }

    public function generalDataValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
            'context' => [
                'label' => lang('backend/admins.labels.context'),
                'rules' => ['required', 'in_list[show,edit]'],
            ],
        ];
    }

    public function metaDataValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
        ];
    }

    public function getPermissionsValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
            'context' => [
                'label' => lang('backend/admins.labels.context'),
                'rules' => ['required', 'in_list[show,edit]'],
            ],
        ];
    }

    public function getTokensValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
        ];
    }

    public function getPermissions(string $uuid): array
    {
        /* Estrazione permessi assegnati all'admin */
        $sql = "select * from admins_permissions where admin_uuid = ?";
        return $this->db->query($sql, [$uuid])->getResult();
    }

    public function getTokens(string $uuid): array
    {
        /* Estrazione log dei tokens di sessione o reset */
        $sql = "select * from admins_tokens where admin_uuid = ?";
        return $this->db->query($sql, [$uuid])->getResult();
    }

    public function getAttempts(string $uuid): array
    {
        /* Estrazione log dei tentativi di accesso standard */
        $sql = "select * from admins_attempts where admin_uuid = ?";
        return $this->db->query($sql, [$uuid])->getResult();
    }

    public function getTwoFaAttempts(string $uuid): array
    {
        /* Estrazione log dei tentativi di accesso 2FA */
        $sql = "select * from admins_2fa_attempts where admin_uuid = ?";
        return $this->db->query($sql, [$uuid])->getResult();
    }

    public function getTwoFaCodes(string $uuid): array
    {
        /* Estrazione codici di backup 2FA attivi o consumati */
        $sql = "select * from admins_2fa_codes where admin_uuid = ?";
        return $this->db->query($sql, [$uuid])->getResult();
    }

    public function getTwoFa(string $uuid): ?object
    {
        /* Estrazione configurazione principale 2FA (record singolo, uso getRow) */
        $sql = "select * from admins_2fa where admin_uuid = ?";
        return $this->db->query($sql, [$uuid])->getRow();
    }

    public function add(array $posts, \CodeIgniter\HTTP\IncomingRequest $request): array
    {
        try 
        {
            /* Filtro campi post ammessi */
            $posts = $this->checkAllowedFields($posts, $this->addAllowedFields);

            /* Genero uuid */
            $uuid = $this->generateUUID();

            /* Istanzio la classe request (prima mancava) per ricavare User Agent e IP */
            $request = service('request');
            $userAgent = $request->getUserAgent()->getAgentString();
            $ip = $request->getIPAddress();

            /* 1. Avvio la transazione PRIMA di eseguire qualsiasi query */
            $this->db->transBegin();

            /* Inserimento dati nella tabella principale */
            $sql = "insert into admins (uuid, firstname, lastname, email, phone, status, note, created_at) values (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$uuid, $posts['firstname'], $posts['lastname'], $posts['email'], $posts['phone'], $posts['status'], $posts['note'], date('Y-m-d H:i:s')]);

            /* Gestione dei permessi se esistenti */
            if ( ! empty($posts['permissions'])):
                $this->insertPermissions($posts['permissions'], $uuid);
            endif;

            /* Generazione token di attivazione */
            $token = new \App\Libraries\Token();
            $tokenHash = $token->getHash(config('BackendAuth')->hashKey);

            /* 2. Calcolo corretto della scadenza lavorando sui secondi (timestamp) */
            $expireTime = date('Y-m-d H:i:s', time() + config('BackendAuth')->activationTime);

            /* Scrittura del token di attivazione */
            $sql = "insert into admins_tokens (admin_uuid, token_hash, token_create, token_expire, token_type, user_agent, ip) values (?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$uuid, $tokenHash, date('Y-m-d H:i:s'), $expireTime, 'activation', $userAgent, $ip]);

            /* Metodo email di default */
            $sql = "insert into admins_2fa (admin_uuid, method, secret, enabled) values (?, 'email', NULL, 1)";
            $this->db->query($sql, [$uuid]);

            /* 3. Verifico eventuali errori SQL prima di fare il commit */
            if ($this->db->transStatus() === false):
                $this->db->transRollback();

                log_message('error', lang('backend/admins.messages.addError'));
                return ['result' => false, 'message' => lang('backend/admins.messages.addError')];
            endif;

            /* Se le 3 query sono andate a buon fine, salvo definitivamente */
            $this->db->transCommit();

            /* Recupero dati utente appena inseriti */
            $data = $this->getByUUID($uuid);

            if($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

        } catch (\Throwable $e) {
            
            $this->db->transRollback();

            log_message('error', lang('backend/admins.messages.addError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.addError')];
        }

        /* Istanzio il servizio email dedicato e tento l'invio */
        $emailService = new \App\Libraries\EmailService();

        /* Configuro i parametri dinamici per questa specifica chiamata */
        $module = $this->module;
        $template = 'emailCreateAdminPartial';
        $subjectLangKey = 'backend/email.admins.createAdmin.subjectCreateAdminEmail';

        /* Chiamata al metodo con i nuovi parametri separati */
        if ( ! $emailService->sendActivationEmail($data['row'], $token->getValue(), $module, $template, $subjectLangKey)):

            $message = sprintf(lang('backend/admins.messages.addSuccessNoEmail'), esc($data['row']->firstname), esc($data['row']->lastname));
            return ['result' => false, 'message' => $message];
            
        else:
            
            $message = sprintf(lang('backend/admins.messages.addSuccess'), esc($data['row']->firstname), esc($data['row']->lastname));
            return ['result' => true, 'message' => $message];
            
        endif;
    }

    public function edit(array $posts): array
    {
        try 
        {
            /* Match dei posts con i campi consentiti */
            $posts = $this->checkAllowedFields($posts, $this->editAllowedFields);

            /* Recupero i dati dell'utente prima dell'aggiornamento */
            $data = $this->getByUUID($posts['uuid']);

            if($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

            /* Se non è stato effettuato alcun cambio... */
            if( ! $this->hasAdminChanged($posts, $data['row'])):
                return ['result' => false, 'message' => lang('backend/admins.messages.noDataChanged')];
            endif;

            $updated_at = date('Y-m-d H:i:s');

            $this->db->transBegin();

            /* Aggiorno la tabella principale dell'utente */
            $sql = 'update admins set firstname = ?, lastname = ?, email = ?, phone = ?, status = ?, note = ?, updated_at = ? where uuid = ?';
            $this->db->query($sql, [$posts['firstname'], $posts['lastname'], $posts['email'], $posts['phone'], $posts['status'], $posts['note'], $updated_at, $posts['uuid']]);

            /* Gestione Permessi: Eliminazione incondizionata seguita da eventuale reinserimento */
            $this->deletePermissions($posts['uuid']);

            if ( ! empty($posts['permissions'])):
                /* Sfruttiamo il metodo ottimizzato che accetta array piatto e UUID */
                $this->insertPermissions($posts['permissions'], $posts['uuid']);
            endif;

            if ($this->db->transStatus() === false):
                $this->db->transRollback();
                log_message('error', lang('backend/admins.messages.editError'));
                return ['result' => false, 'message' => lang('backend/admins.messages.editError')];
            endif;

            $this->db->transCommit();

            /* Aggiornamento dell'oggetto in memoria */
            $data['row']->firstname  = $posts['firstname'];
            $data['row']->lastname   = $posts['lastname'];
            $data['row']->email      = $posts['email'];
            $data['row']->phone      = $posts['phone'];
            $data['row']->status     = $posts['status'];
            $data['row']->note       = $posts['note'];
            $data['row']->updated_at = $updated_at;

            return [
                'result'  => true, 
                'message' => sprintf(lang('backend/admins.messages.editSuccess'), esc($posts['firstname']), esc($posts['lastname'])), 
                'row'     => $data['row']
            ];

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', lang('backend/admins.messages.editError') . ' - ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Riga: ' . $e->getLine());
            return ['result' => false, 'message' => lang('backend/admins.messages.editError')];
        }
    }

    public function hasAdminChanged(array $posts, object $original): bool
    {
        /* 1. Controlla prima i campi base e i file usando il metodo globale */
        if ($this->hasDataChanged($posts, $original)):
            return true;
        endif;

        /* 2. Recupero i vecchi permessi dal database tramite il metodo dedicato */
        $rawOldPermissions = $this->getPermissions($original->uuid);

        /* 3. Preparo gli array per il confronto */
        $newPermissions = $posts['permissions'] ?? [];
        $oldPermissions = [];

        /* 4. Se ci sono vecchi permessi, li appiattisco in un array di stringhe */
        if ( ! empty($rawOldPermissions)):
            $oldPermissions = array_map(function($perm) {
                return $perm->permission;
            }, $rawOldPermissions);
        endif;

        /* 5. Ordino entrambi gli array per garantire un confronto coerente */
        sort($newPermissions);
        sort($oldPermissions);

        /* 6. Confronto finale */
        if ($newPermissions !== $oldPermissions):
            return true;
        endif;

        return false;
    }

    public function del(array $posts): array
    {
        try 
        {
            /* Match dei posts con i campi consentiti */
            $posts = $this->checkAllowedFields($posts, $this->delAllowedFields);

            /* Recupero i dati dell'utente prima dell'eliminazione */
            $data = $this->getByUUID($posts['uuid']);

            if($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

            $this->db->transBegin();

            /* Eliminazione utente */
            $sql = "delete from admins where uuid = ?";
            $this->db->query($sql, [$posts['uuid']]);

            if ($this->db->transStatus() === false):

                $this->db->transRollback();
                log_message('error', lang('backend/admins.messages.delError'));

                return ['result' => false, 'message' => lang('backend/admins.messages.delError')];
            endif;

            $this->db->transCommit();
            return ['result' => true, 'message' => sprintf(lang('backend/admins.messages.delSuccess'), esc($data['row']->firstname), esc($data['row']->lastname))];

        } catch (\Exception $e) {

            /* Rollback incondizionato: se c'è un'eccezione, si annulla sempre */
            $this->db->transRollback();

            log_message('error', lang('backend/admins.messages.delError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.delError')];

        }
    }

    public function resetPassword(array $posts, \CodeIgniter\HTTP\IncomingRequest $request): array
    {
        try 
        {
            /* Match dei posts con i campi consentiti */
            $posts = $this->checkAllowedFields($posts, $this->resetPasswordAllowedFields);

            /* Recupero i dati dell'utente prima dell'eliminazione */
            $data = $this->getByUUID($posts['uuid']);

            if($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

            $userAgent = $request->getUserAgent()->getAgentString();
            $ip = $request->getIPAddress();

            /* Generazione token di attivazione */
            $token = new \App\Libraries\Token();
            $tokenHash = $token->getHash(config('BackendAuth')->hashKey);

            /* 2. Calcolo corretto della scadenza lavorando sui secondi (timestamp) */
            $expireTime = date('Y-m-d H:i:s', time() + config('BackendAuth')->activationTime);

            $this->db->transBegin();

            /* Scrittura Data di Reset nella tabella admins */
            $sql = "update admins set resetted_at = ? where uuid = ?";
            $this->db->query($sql, [date('Y-m-d H:i:s'), $posts['uuid']]);

            /* Scrittura del token di attivazione */
            $sql = "insert into admins_tokens (admin_uuid, token_hash, token_create, token_expire, token_type, user_agent, ip) values (?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$posts['uuid'], $tokenHash, date('Y-m-d H:i:s'), $expireTime, 'activation', $userAgent, $ip]);

            if ($this->db->transStatus() === false):

                $this->db->transRollback();
                log_message('error', lang('backend/admins.messages.resetPasswordError'));

                return ['result' => false, 'message' => lang('backend/admins.messages.resetPasswordError')];
            endif;

            $this->db->transCommit();

        } catch (\Exception $e) {

            $this->db->transRollback();

            log_message('error', lang('backend/admins.messages.resetPasswordError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.resetPasswordError')];

        }

        /* Istanzio il servizio email dedicato e tento l'invio */
        $emailService = new \App\Libraries\EmailService();

        /* Configuro i parametri dinamici per questa specifica chiamata */
        $module = $this->module;
        $template = 'emailResetPasswordAdminPartial';
        $subjectLangKey = 'backend/email.admins.resetPasswordAdmin.subjectResetPasswordAdminEmail';

        /* Chiamata al metodo con i nuovi parametri separati */
        if ( ! $emailService->sendActivationEmail($data['row'], $token->getValue(), $module, $template, $subjectLangKey)):

            $message = sprintf(lang('backend/admins.messages.resetPasswordSuccessNoEmail'), esc($data['row']->firstname), esc($data['row']->lastname));
            return ['result' => false, 'message' => $message];
            
        else:
            
            $message = sprintf(lang('backend/admins.messages.resetPasswordSuccess'), esc($data['row']->firstname), esc($data['row']->lastname));
            return ['result' => true, 'message' => $message];
            
        endif;
    }

    public function changeStatus(array $posts): array
    {
        try 
        {
            /* Match dei posts con i campi consentiti */
            $posts = $this->checkAllowedFields($posts, $this->changeStatusAllowedFields);

            /* Recupero i dati dell'utente prima dell'eliminazione */
            $data = $this->getByUUID($posts['uuid']);

            if($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

            $currentStatus = (int) $data['row']->status;

            /* Converte il risultato in un intero (0 o 1) per MySQL */
            if($currentStatus === 0):

                $newStatus = 1;
                $suspendedAt = null;
                $data['row']->status = 1;
                $data['row']->suspended_at = null;

            elseif($currentStatus === 1):

                $newStatus = 0;
                $suspendedAt = date('Y-m-d H:i:s');
                $data['row']->status = 0;
                $data['row']->suspended_at = $suspendedAt;

            endif;

            $updatedAt = date('Y-m-d H:i:s');
            $data['row']->updated_at = $updatedAt;

            $this->db->transBegin();

            /* cambio status utente */
            $sql = "update admins set status = ?, updated_at = ?, suspended_at = ? where uuid = ?";
            $this->db->query($sql, [$newStatus, $updatedAt, $suspendedAt, $posts['uuid']]);

            if ($this->db->transStatus() === false):

                $this->db->transRollback();
                log_message('error', lang('backend/admins.messages.changeStatusError'));

                return ['result' => false, 'message' => lang('backend/admins.messages.changeStatusError')];
            endif;

            $this->db->transCommit();
            return ['result' => true, 'message' => sprintf(lang('backend/admins.messages.changeStatusSuccess'), esc($data['row']->firstname), esc($data['row']->lastname)), 'admin' => $data['row']];

        } catch (\Exception $e) {

            /* Rollback incondizionato: se c'è un'eccezione, si annulla sempre */
            $this->db->transRollback();

            log_message('error', lang('backend/admins.messages.changeStatusError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.changeStatusError')];

        }
    }

    /* Metodo per il cambio permesso on fly */
    public function changePermission(array $posts): array
    {
        try 
        {
            /* Match dei posts con i campi consentiti */
            $posts = $this->checkAllowedFields($posts, $this->changePermissionAllowedFields);

            /* Recupero i dati dell'utente prima dell'eliminazione */
            $data = $this->getByUUID($posts['uuid']);

            if($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

            /* Query per definire l'esistenza del permesso per questo utente */
            $sql = "select id from admins_permissions where admin_uuid = ? and permission = ?";
            $query = $this->db->query($sql, [$posts['uuid'], $posts['permission']]);

            $this->db->transBegin();

            /* Se il permesso è stato trovato, lo eliminiamo... */
            if($query->getNumRows() > 0):
                $sql = "delete from admins_permissions where admin_uuid = ? and permission = ?";
                $this->db->query($sql, [$posts['uuid'], $posts['permission']]);
            /* ...se invece il permesso non è stato trovato, lo inseriamo. */
            else:
                $sql = "insert into admins_permissions (admin_uuid, permission) values (?, ?)";
                $this->db->query($sql, [$posts['uuid'], $posts['permission']]);
            endif;

            /* Aggiorno nella tabella users il campo updated_at */
            $updatedAt = date('Y-m-d H:i:s');
            $sql = 'update admins set updated_at = ? where uuid = ?';
            $this->db->query($sql, [$updatedAt, $posts['uuid']]);

            if ($this->db->transStatus() === false):

                $this->db->transRollback();
                log_message('error', lang('backend/admins.messages.changePermissionError'));

                return ['result' => false, 'message' => lang('backend/admins.messages.changePermissionError')];
            endif;

            $this->db->transCommit();

            /* Aggiornamento dati dell'utente */
            $data['row']->updated_at = $updatedAt;

            return ['result' => true, 'message' => sprintf(lang('backend/admins.messages.changePermissionSuccess'), esc($data['row']->firstname), esc($data['row']->lastname)), 'admin' => $data['row']];

        } catch (\Exception $e) {

            /* Rollback incondizionato: se c'è un'eccezione, si annulla sempre */
            $this->db->transRollback();

            log_message('error', lang('backend/admins.messages.changePermissionError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.changePermissionError')];

        }
    }

    /* Metodo per l'inserimento in batch dei permessi. Utilizzato in add() ed edit() */
    protected function insertPermissions(array $permissions, string $uuid): void
    {
        if (empty($permissions)):
            return;
        endif;

        $rows = [];
        $params = [];

        /* Costruisco la query dinamicamente unendo il permesso e l'UUID passato */
        foreach ($permissions as $permission):
            $rows[] = "(?, ?)";
            $params[] = $permission;
            $params[] = $uuid;
        endforeach;

        $sql = "insert into admins_permissions (permission, admin_uuid) values " . implode(", ", $rows);
        $this->db->query($sql, $params);
    }

    /* Metodo per l'eliminazione dei permessi. Usato in edit() e delete(). */
    protected function deletePermissions($admin_uuid)
    {
        $sql = "delete from admins_permissions where admin_uuid = ?";
        $this->db->query($sql, [$admin_uuid]);
    }
}