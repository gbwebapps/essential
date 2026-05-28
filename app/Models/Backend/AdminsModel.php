<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

class AdminsModel extends BackendModel
{
    protected ?string $module = 'admins';

    /* @var array Campi consentiti per la visualizzazione Tabella */
    protected array $showAllAllowedFields = ['column', 'order', 'page', 'rows', 'search_fields'];

    /* @var array Campi consentiti per la creazione di un nuovo record */
    protected array $addAllowedFields = ['firstname', 'lastname', 'email', 'phone', 'status', 'note', 'permissions', 'images', 'documents'];

    /* @var array Campi consentiti per l'aggiornamento di un record */
    protected array $editAllowedFields = ['uuid', 'firstname', 'lastname', 'email', 'phone', 'status', 'note', 'permissions', 'images', 'documents'];

    /* @var array Campi consentiti per l'operazione di eliminazione */
    protected array $delAllowedFields = ['uuid'];

    /* @var array Campi consentiti per il cambio di stato attivo/inattivo */
    protected array $changeStatusAllowedFields = ['uuid'];

    /* @var array Mapping tra indici ShowAll e colonne reali del database */
    protected array $allowedOrderColumns = ['firstname', 'lastname', 'email', 'phone', 'status']; 

    /* @var array Campi di ricerca consentiti in showAll */
    protected $showAllSearchFieldsAllowed = ['firstname', 'lastname', 'email', 'phone'];

    /* @var string Query per selezionare un admin */
    protected ?string $getUUIDQuery = "select uuid, firstname, lastname, email, phone, status, master, note, created_at, updated_at, suspended_at, resetted_at from admins where uuid = ? limit 1";

    protected function initModel(): void 
    {
        parent::initModel();
    }

    public function showAllValidationRules(): array
    {
        return [
            'column' => [
                'rules' => ['required'] 
            ],
            'order' => [
                'rules' => ['required'] 
            ],
            'page' => [
                'rules' => ['required'] 
            ],
            'rows' => [
                'rules' => ['required'] 
            ],
            'search_fields' => [
                'rules' => ['permit_empty']
            ],
        ];
    }

    public function showAllSearchValidationRules(): array
    {
        return [
            'search_fields.firstname' => [
                'rules' => ['permit_empty'] 
            ],
            'search_fields.lastname' => [
                'rules' => ['permit_empty'] 
            ],
            'search_fields.email' => [
                'rules' => ['permit_empty'] 
            ],
            'search_fields.phone' => [
                'rules' => ['permit_empty'] 
            ],
        ];
    }

    public function addValidationRules(): array
    {
        return [
            'firstname' => [
                'label' => 'Nome',
                'rules' => ['required', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'],
            ],
            'lastname' => [
                'label' => 'Cognome',
                'rules' => ['required', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'],
            ],
            'email' => [
                'label' => 'Email',
                'rules' => ['required', 'valid_email', 'is_unique[admins.email]'],
            ],
            'phone' => [
                'label' => 'Telefono',
                'rules' => ['required', 'is_unique[admins.phone]', 'regex_match[/^[0-9]{9,10}$/]'],
            ],
            'status' => [
                'label' => 'Stato',
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'notes' => [
                'label' => 'Note',
                'rules' => ['permit_empty', 'max_length[500]', 'regex_match[/^[^<>\x60]*$/su]'],
            ],
        ];
    }

    public function editValidationRules(array $posts): array
    {
        return [
            'uuid' => [
                'label' => 'UUID',
                'rules' => ['required', "is_unique[admins.uuid,uuid,{$posts['uuid']}, 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]']"],
            ],
            'firstname' => [
                'label' => 'Nome',
                'rules' => ['required', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'], 
            ],
            'lastname' => [
                'label' => 'Cognome',
                'rules' => ['required', 'min_length[2]', 'max_length[30]', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\' ]+$/u]'], 
            ],
            'email' => [
                'label' => 'Email',
                'rules' => ['required', 'valid_email'],
            ],
            'phone' => [
                'label' => 'Telefono',
                'rules' => ['required', "is_unique[admins.phone,uuid,{$posts['uuid']},'regex_match[/^[0-9]{9,10}$/]']" 
                ],
            ],
            'status' => [
                'label' => 'Stato',
                'rules' => ['required', 'in_list[0,1]'],
            ],
            'notes' => [
                'label' => 'Note',
                'rules' => ['permit_empty','max_length[500]','regex_match[/^[^<>\x60]*$/su]'],
            ],
        ];
    }

    public function delValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => 'UUID',
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
        ];
    }

    public function changeStatusValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => 'UUID',
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
        ];
    }

    public function generalDataValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => 'UUID',
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
        ];
    }

    public function metaDataValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => 'UUID',
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
            ],
        ];
    }

    public function getByUUID(string $uuid): array 
    {
        /* 1. Richiamo il metodo universale del genitore (BackendModel) */
        $data = parent::getByUUID($uuid);

        /* Se il genitore non trova il record o restituisce un errore, interrompo e restituisco l'errore */
        if ($data['result'] === false):
            return $data;
        endif;

        /* 2. Prendo i dati anagrafici standardizzati */
        $result = ['result' => true,'row' => $data['row']];

        try 
        {
            /* 3. Aggiungo le query specifiche per l'admin (tutte minuscole) */
            
            /* Uso getResult() per le tabelle che possono contenere righe multiple */
            $sql = "select * from admins_permissions where admin_uuid = ?";
            $result['permissions'] = $this->db->query($sql, [$uuid])->getResult();

            $sql = "select * from admins_tokens where admin_uuid = ?";
            $result['tokens'] = $this->db->query($sql, [$uuid])->getResult();

            $sql = "select * from admins_attempts where admin_uuid = ?";
            $result['attempts'] = $this->db->query($sql, [$uuid])->getResult();

            /* Uso getRow() per il 2FA, essendo un record singolo per utente */
            $sql = "select * from admins_2fa where admin_uuid = ?";
            $result['twofa'] = $this->db->query($sql, [$uuid])->getRow();

            return $result;

        } catch(\Throwable $e) {
            log_message('error', lang('backend/global.messages.getUUIDError') . ' - ' . $e->getMessage());
            return ['result' => false, 'message' => lang('backend/global.messages.getUUIDError')];
        }
    }

    public function add(array $posts): array
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

                log_message('error', lang('backend/admins.messages.createAdminFailed') . ' - ' . $e->getMessage());
                return ['result' => 'createAdminFailed', 'message' => lang('backend/admins.messages.createAdminFailed')];
            endif;

            /* Se le 3 query sono andate a buon fine, salvo definitivamente */
            $this->db->transCommit();

            /* Recupero dati utente appena inseriti */
            $data = $this->getByUUID($uuid);

            if($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

        } catch (\Throwable $e) {
            
            /* 4. Aggiunto il rollback dentro il catch per sicurezza */
            if ($this->db->transStatus() !== true):
                $this->db->transRollback();
            endif;

            log_message('error', lang('backend/admins.messages.createAdminFailed') . ' - ' . $e->getMessage());
            return ['result' => 'createAdminFailed', 'message' => lang('backend/admins.messages.createAdminFailed')];
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
            return ['result' => 'emailFailed', 'message' => $message];
            
        else:
            
            $message = sprintf(lang('backend/admins.messages.addSuccess'), esc($data['row']->firstname), esc($data['row']->lastname));
            return ['result' => true, 'message' => $message];
            
        endif;
    }

    public function edit(array $posts): array
    {
        try 
        {
            /* Utilizza la connessione nativa del model per la transazione */
            $this->db->transBegin();

            /* Match dei posts con i campi consentiti */
            $posts = $this->checkAllowedFields($posts, $this->editAllowedFields);


                // some code here...


            if ($this->db->transStatus() === false):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/admins.messages.editError')];
            endif;

            $this->db->transCommit();
            return ['result' => true, 'message' => lang('backend/admins.messages.editSuccess')];

        } catch (\Exception $e) {
            $this->db->transRollback();
            return ['result' => false, 'message' => $e->getMessage()];
        }
    }

    public function del(array $posts): array
    {
        try 
        {
            /* Utilizza la connessione nativa del model per la transazione */
            $this->db->transBegin();

            /* Match dei posts con i campi consentiti */
            $posts = $this->checkAllowedFields($posts, $this->delAllowedFields);


                // some code here...


            if ($this->db->transStatus() === false):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/admins.messages.delError')];
            endif;

            $this->db->transCommit();
            return ['result' => true, 'message' => lang('backend/admins.messages.delSuccess')];

        } catch (\Exception $e) {
            $this->db->transRollback();
            return ['result' => false, 'message' => $e->getMessage()];
        }
    }

    public function changeStatus(array $posts): array
    {
        try 
        {
            /* Utilizza la connessione nativa del model per la transazione */
            $this->db->transBegin();

            /* Match dei posts con i campi consentiti */
            $posts = $this->checkAllowedFields($posts, $this->changeStatusAllowedFields);


                // some code here...


            if ($this->db->transStatus() === false):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/admins.messages.changeStatusError')];
            endif;

            $this->db->transCommit();
            return ['result' => true, 'message' => lang('backend/admins.messages.changeStatusSuccess')];

        } catch (\Exception $e) {
            $this->db->transRollback();
            return ['result' => false, 'message' => $e->getMessage()];
        }
    }
}