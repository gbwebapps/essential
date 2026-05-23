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

    protected $showAllSearchFieldsAllowed = ['firstname', 'lastname', 'email', 'phone'];

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
                'rules' => ['required', 'valid_email', 'is_unique[auth_identities.secret]'],
            ],
            'phone' => [
                'label' => 'Telefono',
                'rules' => ['required', 'is_unique[admins_details.phone]', 'regex_match[/^[0-9]{9,10}$/]'],
            ],
            'active' => [
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
                'rules' => ['required', "is_unique[admins_details.uuid,uuid,{$posts['uuid']}, 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]']"],
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
                'rules' => ['required', "is_unique[admins_details.phone,uuid,{$posts['uuid']},'regex_match[/^[0-9]{9,10}$/]']" 
                ],
            ],
            'active' => [
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

    public function add(array $posts): array
    {
        try 
        {
            /* Utilizza la connessione nativa del model per la transazione */
            $this->db->transBegin();

            /* Match dei posts con i campi consentiti */
            $posts = $this->checkAllowedFields($posts, $this->addAllowedFields);


                // some code here...


            if ($this->db->transStatus() === false):
                $this->db->transRollback();
                return ['result' => false, 'message' => lang('backend/admins.messages.addError')];
            endif;

            $this->db->transCommit();
            return ['result' => true, 'message' => lang('backend/admins.messages.addSuccess')];

        } catch (\Exception $e) {
            $this->db->transRollback();
            return ['result' => false, 'message' => $e->getMessage()];
        }
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