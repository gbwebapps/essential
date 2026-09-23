<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

class AdminsModel extends BackendModel
{
    protected ?string $module = 'admins';

    protected bool $hasSoftDelete = true;

    protected ?string $defaultColumn = 'id';

    protected array $showAllAllowedFields = ['column', 'order', 'page', 'rows', 'searchFields', 'trash_filter'];

    protected array $addAllowedFields = ['firstname', 'lastname', 'email', 'phone', 'status', 'note', 'group_id', 'images'];

    protected array $editAllowedFields = ['uuid', 'firstname', 'lastname', 'email', 'phone', 'status', 'note', 'group_id', 'permissions', 'images'];

    protected array $delAllowedFields = ['uuid'];

    protected array $resetPasswordAllowedFields = ['uuid'];

    protected array $changeStatusAllowedFields = ['uuid'];

    protected array $changePermissionAllowedFields = ['uuid', 'permission'];

    protected array $deleteTokenAllowedFields = ['id', 'uuid'];

    protected array $allowedOrderColumns = ['firstname', 'lastname', 'email', 'phone', 'status']; 

    protected array $showAllSearchAllowedFields = ['firstname', 'lastname', 'email', 'phone']; 

    protected array $showAllSearchAllowedDates = ['created_at', 'updated_at'];

    protected array $toCompare = ['firstname', 'lastname', 'email', 'phone', 'status', 'group_id', 'note'];

    protected ?string $getDataQuery = "select uuid, firstname, lastname, email, phone, status, superadmin, created_at, updated_at, resetted_at, suspended_at, deleted_at,
                                        (select images.filename from images where images.entity_uuid = admins.uuid and images.entity = 'admins' and images.is_cover = 1 limit 1) as cover, 
                                        (select count(*) from images where images.entity_uuid = admins.uuid and images.entity = 'admins') as images_num 
                                        from admins where 1 = 1";

    protected ?string $getUUIDQuery = "select 
                                            admins_groups.name as groupName, 
                                            uuid, 
                                            firstname, 
                                            lastname, 
                                            email, 
                                            phone, 
                                            status, 
                                            superadmin, 
                                            group_id, 
                                            note, 
                                            admins.created_at, 
                                            admins.updated_at, 
                                            suspended_at, 
                                            resetted_at, 
                                            admins.deleted_at 
                                        from admins 
                                        join admins_groups 
                                        on admins.group_id = admins_groups.id 
                                        where admins.uuid = ? limit 1";

    protected ?string $getNumRowsQuery = 'select count(*) as count from admins where 1 = 1';

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
            'trash_filter' => [
                'rules' => ['in_list[active,trashed,all]'] 
            ],
        ];
    }

    public function showAllSearchValidationRules(): array
    {
        return [
            'searchFields.firstname' => [
                'label' => lang('backend/admins.labels.firstname'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'], 
            ],
            'searchFields.lastname' => [
                'label' => lang('backend/admins.labels.lastname'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'], 
            ],
            'searchFields.email' => [
                'label' => lang('backend/admins.labels.email'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-Z0-9@._-]+$/]'], 
            ],
            'searchFields.phone' => [
                'label' => lang('backend/admins.labels.phone'), 
                'rules' => ['permit_empty', 'regex_match[/^[0-9+\-\s()]+$/]'], 
            ],
            'searchDates.created_at-from' => [
                'label' => lang('backend/admins.labels.dateFrom'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
            'searchDates.created_at-to' => [
                'label' => lang('backend/admins.labels.dateTo'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
        ];
    }

    public function addValidationRules(): array
    {
        return [
            'firstname' => [
                'label' => lang('backend/admins.labels.firstname'),
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'],
            ],
            'lastname' => [
                'label' => lang('backend/admins.labels.lastname'),
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'],
            ],
            'email' => [
                'label' => lang('backend/admins.labels.email'),
                'rules' => ['required', 'trim', 'valid_email', 'max_length[255]', 'is_unique[admins.email]'],
            ],
            'phone' => [
                'label' => lang('backend/admins.labels.phone'),
                'rules' => ['required', 'trim', 'regex_match[/^\+[0-9]{9,15}$/]'], 
            ],
            'status' => [
                'label' => lang('backend/admins.labels.status'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'note' => [
                'label' => lang('backend/admins.labels.note'),
                'rules' => ['permit_empty', 'trim', 'max_length[500]', 'safeText'],
                'errors' => [
                    'safeText' => 'Caratteri non ammessi.'
                ]
            ],
            'group_id' => [
                'label' => lang('backend/admins.labels.group'),
                'rules' => ['required', 'is_natural_no_zero', 'is_not_unique[admins_groups.id]'],
            ],
            'images' => [
                'label' => lang('backend/admins.labels.images'),
                'rules' => ['permit_empty', 'checkImages'] // checkImages[size:2048,ext:png|jpg|jpeg|webp]
            ]
        ];
    }

    public function editValidationRules(array $posts): array
    {
        /* Recuperiamo l'array multidimensionale dalla configurazione per estrarre le chiavi valide */
        $rawPermissions = config(\Config\Backend\Permissions::class)->getPermissions();

        $validKeys = [];
        foreach ($rawPermissions as $group):
            $validKeys = array_merge($validKeys, array_keys($group['perms']));
        endforeach;

        $inListString = implode(',', $validKeys);

        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', "is_unique[admins.uuid,uuid,{$posts['uuid']}]", 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
            'firstname' => [
                'label' => lang('backend/admins.labels.firstname'),
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'],
            ],
            'lastname' => [
                'label' => lang('backend/admins.labels.lastname'),
                'rules' => ['required', 'trim', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\'\’\‘\` ]+$/u]'],
            ],
            'email' => [
                'label' => lang('backend/admins.labels.email'),
                'rules' => ['required', 'trim', 'valid_email', 'max_length[255]', "is_unique[admins.email,uuid,{$posts['uuid']}]"],
            ],
            'phone' => [
                'label' => lang('backend/admins.labels.phone'),
                'rules' => ['required', 'trim', 'regex_match[/^\+[0-9]{9,15}$/]'], 
            ],
            'status' => [
                'label' => lang('backend/admins.labels.status'),
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'note' => [
                'label' => lang('backend/admins.labels.note'),
                'rules' => ['permit_empty', 'trim', 'max_length[500]', 'safeText'],
                'errors' => [
                    'safeText' => 'Caratteri non ammessi.'
                ]
            ],
            'group_id' => [
                'label' => lang('backend/admins.labels.group'),
                'rules' => ['required', 'is_natural_no_zero', 'is_not_unique[admins_groups.id]'],
            ],
            /* Validazione di ogni singolo elemento contenuto nell'array delle eccezioni */
            'permissions.*' => [
                'label' => lang('backend/admins.labels.permissions'),
                'rules' => ['permit_empty', 'in_list[' . $inListString . ']'],
                'errors' => [
                    'in_list' => lang('Backend/admins.errors.permission')
                ]
            ],
            'images' => [
                'label' => lang('backend/admins.labels.images'),
                'rules' => ['permit_empty', 'checkImages'] // checkImages[size:2048,ext:png|jpg|jpeg|webp]
            ]
        ];
    }

    public function changeGroupValidationRules(): array
    {
        return [
            'group_id' => [
                'label' => lang('backend/admins.labels.group'),
                'rules' => ['required', 'is_natural_no_zero', 'is_not_unique[admins_groups.id]'],
            ],
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ]
        ];
    }

    public function delValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'], 
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'), 
                    'regex_match' => lang('Backend/admins.errors.uuid')
                ]
            ],
        ];
    }

    public function resetPasswordValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'), 
                    'regex_match' => lang('Backend/admins.errors.uuid')
                ]
            ],
        ];
    }

    public function changeStatusValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'], 
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'), 
                    'regex_match' => lang('Backend/admins.errors.uuid')
                ]
            ],
            'context' => [
                'label' => lang('backend/admins.labels.context'),
                'rules' => ['permit_empty', 'in_list[show]'],
                'errors' => [
                    'in_list' => lang('Backend/admins.errors.context'), 
                ]
            ],
        ];
    }

    public function getTokensValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'), 
                    'regex_match' => lang('Backend/admins.errors.uuid') 
                ]
            ],
        ]; 
    }

    public function generalDataValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'], 
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'), 
                    'regex_match' => lang('Backend/admins.errors.uuid') 
                ]
            ],
            'context' => [
                'label' => lang('backend/admins.labels.context'),
                'rules' => ['required', 'in_list[show,edit]'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.context'), 
                    'in_list' => lang('Backend/admins.errors.context') 
                ]
            ],
        ];
    }

    public function metaDataValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'), 
                    'regex_match' => lang('Backend/admins.errors.uuid') 
                ]
            ],
        ];
    }

    public function getPermissionsValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'], 
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'), 
                    'regex_match' => lang('Backend/admins.errors.uuid') 
                ]
            ],
            'context' => [
                'label' => lang('backend/admins.labels.context'),
                'rules' => ['required', 'in_list[show,edit]'], 
                'errors' => [
                    'required' => lang('Backend/admins.errors.context'), 
                    'in_list' => lang('Backend/admins.errors.context') 
                ]
            ],
        ];
    }

    public function deleteTokenValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/admins.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'), 
                    'regex_match' => lang('Backend/admins.errors.uuid') 
                ]
            ],
            'id' => [
                'label' => lang('backend/admins.labels.id'),
                'rules' => ['required', 'is_natural_no_zero'],
                'errors' => [
                    'required' => lang('Backend/admins.errors.id'), 
                    'is_natural_no_zero' => lang('Backend/admins.errors.id') 
                ]
            ],
        ];
    }

    public function changePermissionValidationRules(): array 
    {
        /* Recupero l'array multidimensionale dalla configurazione */
        $rawPermissions = config(\Config\Backend\Permissions::class)->getPermissions();

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
                'errors' => [
                    'required' => lang('Backend/admins.errors.uuid'), 
                    'regex_match' => lang('Backend/admins.errors.uuid') 
                ]
            ],
            'permission' => [
                'label' => lang('backend/admins.labels.permissions'),
                'rules' => ['required', 'in_list[' . $inListString . ']'], 
                'errors' => [
                    'required' => lang('Backend/admins.errors.permission'), 
                    'in_list' => lang('Backend/admins.errors.permission') 
                ]
            ]
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

    public function getGroups(): array
    {
        $sql = "select * from admins_groups";
        return $this->db->query($sql)->getResult();
    }

    public function getGroupPermissions(int $groupId): array
    {
        $sql = "select permission from admins_groups_permissions where group_id = ?";
        $result = $this->db->query($sql, [$groupId])->getResultObject();

        if ( ! $result):
            return [];
        endif;

        /* Appiattisco l'array di oggetti in un array di stringhe */
        return array_map(function($row) {
            return $row->permission;
        }, $result);
    }

    public function getAdminExceptions(string $uuid): array
    {
        $sql = "select permission, allow from admins_permissions where admin_uuid = ?";
        $result = $this->db->query($sql, [$uuid])->getResultObject();

        if ( ! $result):
            return [];
        endif;

        $exceptions = [];
        foreach ($result as $row):
            /* Mappo il nome del permesso come chiave e il valore di allow (0 o 1) come stato dell'eccezione */
            $exceptions[$row->permission] = (int) $row->allow;
        endforeach;

        return $exceptions;
    }

    public function add(array $posts, \CodeIgniter\HTTP\IncomingRequest $request): array
    {
        try 
        {
            /* Filtro campi post ammessi */
            $posts = $this->checkAllowedFields($posts, $this->addAllowedFields);

            /* Genero uuid */
            $uuid = $this->generateUUID();

            /* Istanzio la classe request per ricavare User Agent e IP */
            $request = service('request');
            $userAgent = $request->getUserAgent()->getAgentString();
            $ip_address = $request->getIPAddress();

            /* 1. Avvio la transazione PRIMA di eseguire qualsiasi query */
            $this->db->transBegin();

            /* Inserimento dati nella tabella principale con l'aggiunta di group_id */
            $sql = "insert into admins (uuid, firstname, lastname, email, phone, status, group_id, note, created_at) values (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$uuid, $posts['firstname'], $posts['lastname'], $posts['email'], $posts['phone'], $posts['status'], $posts['group_id'], (trim($posts['note']) !== '' ? $posts['note'] : null), date('Y-m-d H:i:s')]);

            /* Generazione token di attivazione */
            $token = new \App\Libraries\Token();
            $tokenHash = $token->getHash(setting('Backend\Auth')->hashKey);

            /* 2. Calcolo corretto della scadenza lavorando sui secondi (timestamp) */
            $expireTime = date('Y-m-d H:i:s', time() + setting('Backend\Auth')->activationTime);

            /* Scrittura del token di attivazione */
            $sql = "insert into admins_tokens (admin_uuid, token_hash, token_create, token_expire, token_type, user_agent, ip_address, created_at) values (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$uuid, $tokenHash, date('Y-m-d H:i:s'), $expireTime, 'activation', $userAgent, $ip_address, date('Y-m-d H:i:s')]);

            /* Metodo email di default */
            $sql = "insert into admins_2fa (admin_uuid, method, secret, enabled) values (?, 'email', NULL, 1)";
            $this->db->query($sql, [$uuid]);

            /* Gestione Upload e Scrittura Immagini nel flusso transazionale */
            if ( ! empty($posts['images'])):
                $uploadService = new \App\Libraries\Backend\UploadClass();
                $filenames = $uploadService->doUpload($posts['images'], 'admins', $uuid);
                
                if ($filenames):
                    $this->insertImages($filenames, $uuid, 'admins', 'add');
                endif;
            endif;

            /* 3. Verifico eventuali errori SQL prima di fare il commit */
            if ($this->db->transStatus() === false):
                $this->db->transRollback();

                log_message('error', lang('backend/admins.messages.addError'));
                return ['result' => false, 'message' => lang('backend/admins.messages.addError')];
            endif;

            /* Se le query sono andate a buon fine, salvo definitivamente */
            $this->db->transCommit();

            /* Recupero dati utente appena inseriti */
            $data = $this->getByUUID($uuid);

            if($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('ADD_ADMIN', 'admins', sprintf(lang('backend/admins.audits.addAdmin'), esc($data['row']->firstname), esc($data['row']->lastname)), $currentAdmin);

        } catch (\Throwable $e) {
            
            $this->db->transRollback();

            log_message('error', lang('backend/admins.messages.addError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.addError')];
        }

        /* Istanzio il servizio email dedicato e tento l'invio */
        $emailService = new \App\Libraries\Backend\EmailService();

        /* Configuro i parametri dinamici per questa specifica chiamata */
        $template = 'emailCreateAdminPartial';
        $subjectLangKey = 'backend/email.admins.add.subjectCreateAdminEmail';

        /* Chiamata al metodo con i parametri separati */
        if ( ! $emailService->sendActivationEmail($data['row'], $token->getValue(), $this->module, $template, $subjectLangKey)):

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
            /* Match dei posts con i campi consentiti (ricordati di inserire group_id ed eliminare permissions in $editAllowedFields) */
            $posts = $this->checkAllowedFields($posts, $this->editAllowedFields);

            /* Recupero i dati dell'utente prima dell'aggiornamento */
            $data = $this->getByUUID($posts['uuid']);

            if($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($data['row']->deleted_at !== null):
                return ['result' => false, 'message' => lang('backend/admins.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
            if ((int) $data['row']->superadmin === 1):
                return ['result'  => false, 'message' => lang('backend/admins.messages.protectedAdmin')];
            endif;

            /* Se non è stato effettuato alcun cambio sui dati gestiti, interrompiamo subito */
            if( ! $this->hasAdminChanged($posts, $data['row'])):
                return ['result' => false, 'message' => lang('backend/admins.messages.noDataChanged')];
            endif;

            $updated_at = date('Y-m-d H:i:s');

            $this->db->transBegin();

            /* Aggiorno la tabella principale dell'utente includendo il group_id */
            $sql = 'update admins set firstname = ?, lastname = ?, email = ?, phone = ?, status = ?, group_id = ?, note = ?, updated_at = ? where uuid = ?';
            $this->db->query($sql, [$posts['firstname'], $posts['lastname'], $posts['email'], $posts['phone'], $posts['status'], $posts['group_id'], (trim($posts['note']) !== '' ? $posts['note'] : null), $updated_at, $posts['uuid']]);

            /* Eliminazione incondizionata delle vecchie eccezioni dell'utente */
            $this->deletePermissions($posts['uuid']);

            /* Recupero i permessi nativi del gruppo appena assegnato per calcolare le eccezioni */
            $groupPermissions = $this->getGroupPermissions((int)$posts['group_id']);
            $submittedPermissions = $posts['permissions'] ?? [];

            /* 1. Calcolo eccezioni positive (Permessi extra): presenti nel form MA non nel gruppo */
            $extraPermissions = array_diff($submittedPermissions, $groupPermissions);

            /* 2. Calcolo eccezioni negative (Revoche): presenti nel gruppo MA non nel form */
            $revokedPermissions = array_diff($groupPermissions, $submittedPermissions);

            /* Scrittura delle eccezioni positive (allow = 1) */
            if ( ! empty($extraPermissions)):
                foreach ($extraPermissions as $perm):
                    $sqlInsert = "insert into admins_permissions (permission, admin_uuid, allow) values (?, ?, 1)";
                    $this->db->query($sqlInsert, [$perm, $posts['uuid']]);
                endforeach;
            endif;

            /* Scrittura delle eccezioni negative (allow = 0) */
            if ( ! empty($revokedPermissions)):
                foreach ($revokedPermissions as $perm):
                    $sqlInsert = "insert into admins_permissions (permission, admin_uuid, allow) values (?, ?, 0)";
                    $this->db->query($sqlInsert, [$perm, $posts['uuid']]);
                endforeach;
            endif;

            /* Gestione Upload e Scrittura Immagini nel flusso transazionale */
            if ( ! empty($posts['images'])):
                $uploadService = new \App\Libraries\Backend\UploadClass();
                $filenames = $uploadService->doUpload($posts['images'], 'admins', $posts['uuid']);
                
                if ($filenames):
                    $this->insertImages($filenames, $posts['uuid'], 'admins', 'edit');
                endif;
            endif;

            if ($this->db->transStatus() === false):
                $this->db->transRollback();
                log_message('error', lang('backend/admins.messages.editError'));
                return ['result' => false, 'message' => lang('backend/admins.messages.editError')];
            endif;

            $this->db->transCommit();

            /* Aggiornamento dell'oggetto in memoria da restituire alla vista */
            $data['row']->firstname  = $posts['firstname'];
            $data['row']->lastname   = $posts['lastname'];
            $data['row']->email      = $posts['email'];
            $data['row']->phone      = $posts['phone'];
            $data['row']->status     = $posts['status'];
            $data['row']->group_id   = $posts['group_id'];
            $data['row']->note       = $posts['note'];
            $data['row']->updated_at = $updated_at;

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('EDIT_ADMIN', 'admins', sprintf(lang('backend/admins.audits.editAdmin'), esc($data['row']->firstname), esc($data['row']->lastname)), $currentAdmin);

            return [
                'result'  => true, 
                'message' => sprintf(lang('backend/admins.messages.editSuccess'), esc($posts['firstname']), esc($posts['lastname'])), 
                'row'     => $data['row']
            ];

        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', lang('backend/admins.messages.editError') . ' - ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Riga: ' . $e->getLine());
            return ['result' => false, 'message' => lang('backend/admins.messages.editError')];
        }
    }

    public function hasAdminChanged(array $posts, object $original): bool
    {
        /* 1. Controlla i campi base (incluso group_id presente in $toCompare) e i file */
        if ($this->hasDataChanged($posts, $original)):
            return true;
        endif;

        /* 2. Recupero i permessi ereditati dal gruppo originale dell'utente */
        $groupPerms = $this->getGroupPermissions((int) $original->group_id);

        /* 3. Recupero le eccezioni attuali dell'utente dal database */
        $userExceptions = $this->getAdminExceptions($original->uuid);

        /* 4. Calcolo la lista reale e attiva dei permessi attuali dell'utente */
        $oldPermissions = [];
        
        /* Prendo la configurazione globale dei permessi atomici per ciclare tutti i permessi possibili */
        $globalPermissions = config(\Config\Backend\Permissions::class)->getPermissions();

        foreach ($globalPermissions as $group):
            foreach ($group['perms'] as $code => $title):
                /* Se esiste un'eccezione esplicita nel DB, comanda lei */
                if (array_key_exists($code, $userExceptions)):
                    if ($userExceptions[$code] === 1):
                        $oldPermissions[] = $code;
                    endif;
                else:
                    /* Altrimenti l'utente eredita lo stato del suo gruppo */
                    if (in_array($code, $groupPerms)):
                        $oldPermissions[] = $code;
                    endif;
                endif;
            endforeach;
        endforeach;

        /* 5. Preparo l'array dei nuovi permessi inviati dal form */
        $newPermissions = $posts['permissions'] ?? [];

        /* 6. Ordino entrambi gli array per garantire un confronto coerente */
        sort($newPermissions);
        sort($oldPermissions);

        /* 7. Confronto finale tra lo stato reale precedente e quello nuovo inviato */
        if ($newPermissions !== $oldPermissions):
            return true;
        endif;

        return false;
    }

    public function hardDelete(array $posts): array
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

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
            if ((int) $data['row']->superadmin === 1):
                return ['result'  => false, 'message' => lang('backend/admins.messages.protectedAdmin')];
            endif;

            $this->db->transBegin();

            /* Eliminazione utente */
            $sql = "delete from admins where uuid = ?";
            $this->db->query($sql, [$posts['uuid']]);

            /* Eliminazione immagini dal database */
            $sql = "delete from images where entity_uuid = ?";
            $this->db->query($sql, [$posts['uuid']]);

            if ($this->db->transStatus() === false):

                $this->db->transRollback();
                log_message('error', lang('backend/admins.messages.hardDeleteError'));

                return ['result' => false, 'message' => lang('backend/admins.messages.hardDeleteError')];
            endif;

            $this->db->transCommit();

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('HARD_DELETE_ADMINS', 'admins', sprintf(lang('backend/admins.audits.hardDeleteAdmin'), esc($data['row']->firstname), esc($data['row']->lastname)), $currentAdmin);

            \App\Libraries\ImageFileSystemService::removeAllImages('admins', $posts['uuid']);

            return ['result' => true, 'message' => sprintf(lang('backend/admins.messages.hardDeleteSuccess'), esc($data['row']->firstname), esc($data['row']->lastname))];

        } catch (\Throwable $e) {

            /* Rollback incondizionato: se c'è un'eccezione, si annulla sempre */
            $this->db->transRollback();

            log_message('error', lang('backend/admins.messages.hardDeleteError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.hardDeleteError')];

        }
    }

    public function softDelete(array $posts): array
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

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($data['row']->deleted_at !== null):
                return ['result' => false, 'message' => lang('backend/admins.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
            if ((int) $data['row']->superadmin === 1):
                return ['result'  => false, 'message' => lang('backend/admins.messages.protectedAdmin')];
            endif;

            $this->db->transBegin();

            /* Generazione marcatore per offuscare l'email ed evitare conflitti UNIQUE */
            $deletedMarker = '.deleted.' . time();

            /* Cestinamento e offuscamento email */
            $sql = "update admins set email = CONCAT(email, ?), deleted_at = NOW() where uuid = ?";
            $this->db->query($sql, [$deletedMarker, $posts['uuid']]);

            /* Revoca immediata degli accessi attivi (disconnessione forzata) */
            $this->db->query("delete from admins_tokens where admin_uuid = ?", [$posts['uuid']]);
            $this->db->query("delete from admins_2fa_codes where admin_uuid = ?", [$posts['uuid']]);

            if ($this->db->transStatus() === false):

                $this->db->transRollback();
                log_message('error', lang('backend/admins.messages.softDeleteError'));

                return ['result' => false, 'message' => lang('backend/admins.messages.softDeleteError')];
            endif;

            $this->db->transCommit();

            /* Registrazione attività */
            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('SOFT_DELETE_ADMINS', 'admins', sprintf(lang('backend/admins.audits.softDeleteAdmin'), esc($data['row']->firstname), esc($data['row']->lastname)), $currentAdmin);

            /* Nota: usa una stringa di lingua dedicata come softDelSuccess se l'hai creata */
            return ['result' => true, 'message' => sprintf(lang('backend/admins.messages.softDeleteSuccess'), esc($data['row']->firstname), esc($data['row']->lastname))];

        } catch (\Throwable $e) {

            /* Rollback incondizionato: se c'è un'eccezione, si annulla sempre */
            $this->db->transRollback();

            log_message('error', lang('backend/admins.messages.softDeleteError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.softDeleteError')];

        }
    }

    public function restoreDelete(array $posts): array
    {
        try 
        {
            /* Match dei posts con i campi consentiti */
            $posts = $this->checkAllowedFields($posts, $this->delAllowedFields);

            /* Recupero diretto dal DB per aggirare eventuali filtri sui record attivi */
            $sql = "select * from admins where uuid = ?";
            $row = $this->db->query($sql, [$posts['uuid']])->getRow();

            if (empty($row)):
                return ['result' => false, 'message' => lang('backend/admins.messages.notFound')];
            endif;

            /* Ripulisco l'email dal marcatore generato durante il soft delete */
            if (strpos($row->email, '.deleted.') !== false):
                $cleanEmail = explode('.deleted.', $row->email)[0];
            else:
                $cleanEmail = $row->email;
            endif;

            /* Scudo di sicurezza: verifico se nel frattempo l'email è stata presa da un utente attivo */
            $sqlCheck = "select uuid from admins where email = ? and deleted_at IS NULL";
            $emailExists = $this->db->query($sqlCheck, [$cleanEmail])->getRow();

            $this->db->transBegin();

            if ( ! empty($emailExists)):
                
                /* CONFLITTO: Ripristino forzando a inattivo e mantenendo la mail offuscata */
                $tempEmail = time() . '@temp.local';
                $sqlUpdate = "update admins set email = ?, status = 0, deleted_at = NULL where uuid = ?";
                $this->db->query($sqlUpdate, [$tempEmail, $posts['uuid']]);
                
                $message = lang('backend/admins.messages.restoreDeleteConflict'); 
                
            else:
                
                /* NESSUN CONFLITTO: Ripristino dell'utente e della sua email originale */
                $sqlUpdate = "update admins set email = ?, deleted_at = NULL where uuid = ?";
                $this->db->query($sqlUpdate, [$cleanEmail, $posts['uuid']]);
                
                $message = sprintf(lang('backend/admins.messages.restoreDeleteSuccess'), esc($row->firstname), esc($row->lastname));
                
            endif;

            if ($this->db->transStatus() === false):

                $this->db->transRollback();
                log_message('error', lang('backend/admins.messages.restoreDeleteError'));

                return ['result' => false, 'message' => lang('backend/admins.messages.restoreDeleteError')];
            endif;

            $this->db->transCommit();

            /* Registrazione attività */
            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('RESTORE_DELETE_ADMINS', 'admins', sprintf(lang('backend/admins.audits.restoreDeleteAdmin'), esc($row->firstname), esc($row->lastname)), $currentAdmin);

            return ['result' => true, 'message' => $message];

        } catch (\Throwable $e) {

            /* Rollback incondizionato: se c'è un'eccezione, si annulla sempre */
            $this->db->transRollback();

            log_message('error', lang('backend/admins.messages.restoreDeleteError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.restoreDeleteError')];

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

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($data['row']->deleted_at !== null):
                return ['result' => false, 'message' => lang('backend/admins.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
            if ((int) $data['row']->superadmin === 1):
                return ['result'  => false, 'message' => lang('backend/admins.messages.protectedAdmin')];
            endif;

            $userAgent = $request->getUserAgent()->getAgentString();
            $ip_address = $request->getIPAddress();

            /* Generazione token di attivazione */
            $token = new \App\Libraries\Token();
            $tokenHash = $token->getHash(setting('Backend\Auth')->hashKey);

            /* 2. Calcolo corretto della scadenza lavorando sui secondi (timestamp) */
            $expireTime = date('Y-m-d H:i:s', time() + setting('Backend\Auth')->activationTime);

            $this->db->transBegin();

            /* Scrittura Data di Reset nella tabella admins */
            $sql = "update admins set resetted_at = ? where uuid = ?";
            $this->db->query($sql, [date('Y-m-d H:i:s'), $posts['uuid']]);

            /* Eliminiamo eventuali token di attivazione precedenti ancora attivi o scaduti per questo specifico admin */
            $sql = "delete from admins_tokens where admin_uuid = ? and token_type = ?";
            $this->db->query($sql, [$posts['uuid'], 'activation']);

            /* Scrittura del token di attivazione */
            $sql = "insert into admins_tokens (admin_uuid, token_hash, token_create, token_expire, token_type, user_agent, ip_address, created_at) values (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$posts['uuid'], $tokenHash, date('Y-m-d H:i:s'), $expireTime, 'activation', $userAgent, $ip_address, date('Y-m-d_H-i-s')]);

            if ($this->db->transStatus() === false):

                $this->db->transRollback();
                log_message('error', lang('backend/admins.messages.resetPasswordError'));

                return ['result' => false, 'message' => lang('backend/admins.messages.resetPasswordError')];
            endif;

            $this->db->transCommit();

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('RESET_PASSWORD_ADMIN', 'admins', sprintf(lang('backend/admins.audits.resetPasswordAdmin'), esc($data['row']->firstname), esc($data['row']->lastname)), $currentAdmin);

        } catch (\Throwable $e) {

            $this->db->transRollback();

            log_message('error', lang('backend/admins.messages.resetPasswordError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.resetPasswordError')];

        }

        /* Istanzio il servizio email dedicato e tento l'invio */
        $emailService = new \App\Libraries\Backend\EmailService();

        /* Configuro i parametri dinamici per questa specifica chiamata */
        $template = 'emailResetPasswordAdminPartial';
        $subjectLangKey = 'backend/email.admins.resetPassword.subjectResetPasswordEmail';

        /* Chiamata al metodo con i nuovi parametri separati */
        if ( ! $emailService->sendActivationEmail($data['row'], $token->getValue(), $this->module, $template, $subjectLangKey)):

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

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($data['row']->deleted_at !== null):
                return ['result' => false, 'message' => lang('backend/admins.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
            if ((int) $data['row']->superadmin === 1):
                return ['result'  => false, 'message' => lang('backend/admins.messages.protectedAdmin')];
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

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('CHANGE_STATUS_ADMIN', 'admins', sprintf(lang('backend/admins.audits.changeStatusAdmin'), esc($data['row']->firstname), esc($data['row']->lastname)), $currentAdmin);

            return ['result' => true, 'message' => sprintf(lang('backend/admins.messages.changeStatusSuccess'), esc($data['row']->firstname), esc($data['row']->lastname)), 'admin' => $data['row']];

        } catch (\Throwable $e) {

            /* Rollback incondizionato: se c'è un'eccezione, si annulla sempre */
            $this->db->transRollback();

            log_message('error', lang('backend/admins.messages.changeStatusError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.changeStatusError')];

        }
    }

    public function changePermission(array $posts): array
    {
        try 
        {
            $posts = $this->checkAllowedFields($posts, $this->changePermissionAllowedFields);

            $data = $this->getByUUID($posts['uuid']);

            if ($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($data['row']->deleted_at !== null):
                return ['result' => false, 'message' => lang('backend/admins.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
            if ((int) $data['row']->superadmin === 1):
                return ['result'  => false, 'message' => lang('backend/admins.messages.protectedAdmin')];
            endif;

            $admin = $data['row'];
            $permissionCode = $posts['permission'];

            /* 1. Recuperiamo lo stato nativo del gruppo e le eccezioni attuali */
            $groupPerms = $this->getGroupPermissions((int) $admin->group_id);
            $userExceptions = $this->getAdminExceptions($admin->uuid);

            $isBelongingToGroup = in_array($permissionCode, $groupPerms);
            $hasException = array_key_exists($permissionCode, $userExceptions);

            $this->db->transBegin();

            if ($isBelongingToGroup):
                /* Il permesso appartiene al gruppo */
                if ($hasException):
                    /* C'era un'eccezione (era a 0 per bloccarlo), cliccando lo ripristiniamo al gruppo (elimina eccezione) */
                    $sql = "delete from admins_permissions where admin_uuid = ? and permission = ?";
                    $this->db->query($sql, [$admin->uuid, $permissionCode]);
                else:
                    /* Non c'era eccezione (era attivo da gruppo), cliccando creiamo un'eccezione negativa (allow = 0) */
                    $sql = "insert into admins_permissions (admin_uuid, permission, allow) values (?, ?, 0)";
                    $this->db->query($sql, [$admin->uuid, $permissionCode]);
                endif;
            else:
                /* Il permesso NON appartiene al gruppo */
                if ($hasException):
                    /* C'era un'eccezione (era a 1 per sbloccarlo), cliccando lo ripristiniamo al gruppo (elimina eccezione) */
                    $sql = "delete from admins_permissions where admin_uuid = ? and permission = ?";
                    $this->db->query($sql, [$admin->uuid, $permissionCode]);
                else:
                    /* Non c'era eccezione (era spento da gruppo), cliccando creiamo un'eccezione positiva (allow = 1) */
                    $sql = "insert into admins_permissions (admin_uuid, permission, allow) values (?, ?, 1)";
                    $this->db->query($sql, [$admin->uuid, $permissionCode]);
                endif;
            endif;

            /* Aggiorno nella tabella admins il campo updated_at */
            $updatedAt = date('Y-m-d H:i:s');
            $sql = 'update admins set updated_at = ? where uuid = ?';
            $this->db->query($sql, [$updatedAt, $admin->uuid]);

            if ($this->db->transStatus() === false):
                $this->db->transRollback();
                log_message('error', lang('backend/admins.messages.changePermissionError'));
                return ['result' => false, 'message' => lang('backend/admins.messages.changePermissionError')];
            endif;

            $this->db->transCommit();

            $currentAdmin = service('authorization')->currentAdmin();
            log_admin_activity('CHANGE_PERMISSION_ADMIN', 'admins', sprintf(lang('backend/admins.audits.changePermissionAdmin'), esc($admin->firstname), esc($admin->lastname)), $currentAdmin);

            $admin->updated_at = $updatedAt;

            return [
                'result'  => true, 
                'message' => sprintf(lang('backend/admins.messages.changePermissionSuccess'), esc($admin->firstname), esc($admin->lastname)), 
                'admin'   => $admin
            ];

        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', lang('backend/admins.messages.changePermissionError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.changePermissionError')];
        }
    }

    public function deleteToken(array $posts): array
    {
        /* Match dei posts con i campi consentiti */
        $posts = $this->checkAllowedFields($posts, $this->deleteTokenAllowedFields);

        try {

            /* Recupero i dati dell'utente prima dell'eliminazione */
            $data = $this->getByUUID($posts['uuid']);

            if($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($data['row']->deleted_at !== null):
                return ['result' => false, 'message' => lang('backend/admins.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
            if ((int) $data['row']->superadmin === 1):
                return ['result'  => false, 'message' => lang('backend/admins.messages.protectedAdmin')];
            endif;

            /* 1. Recupero il token per leggere last_activity */
            $tokenSql = "select id, last_activity, token_type from admins_tokens where admin_uuid = ? and id = ?";
            $tokenRow = $this->db->query($tokenSql, [$posts['uuid'], $posts['id']])->getRow();

            if ($tokenRow):
                /* 2. Aggiorno il log registrando la forzatura (banned) */
                if (in_array($tokenRow->token_type, ['cookie', 'session'])):
                    $logoutTime = ! empty($tokenRow->last_activity) ? $tokenRow->last_activity : date('Y-m-d H:i:s');
                    $logUpdateSql = "update admins_logs set logout = ?, logout_reason = 'banned' where token_id = ?";
                    $this->db->query($logUpdateSql, [$logoutTime, $tokenRow->id]);
                endif;
            endif;

            /* 3. Elimino fisicamente il token */
            $sql = "delete from admins_tokens where admin_uuid = ? and id = ?";
            $this->db->query($sql, [$posts['uuid'], $posts['id']]);

            if($this->db->affectedRows() > 0):

                $currentAdmin = service('authorization')->currentAdmin();
                log_admin_activity('DELETE_TOKEN_ADMIN', 'admins', sprintf(lang('backend/admins.audits.deleteTokenAdmin'), esc($data['row']->firstname), esc($data['row']->lastname)), $currentAdmin);

                return ['result' => true, 'message' => sprintf(lang('backend/admins.messages.deleteTokenSuccess'), esc($data['row']->firstname), esc($data['row']->lastname)), 'admin' => $data['row']];
            endif;

            return ['result' => false, 'message' => lang('backend/admins.messages.deleteTokenError')];

        } catch(\Throwable $e) {

            log_message('error', lang('backend/admins.messages.deleteTokenError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/admins.messages.deleteTokenError')];

        }
    }

    public function deletePermissions($admin_uuid)
    {
        $sql = "delete from admins_permissions where admin_uuid = ?";
        $this->db->query($sql, [$admin_uuid]);
    }
}