<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

class TokensModel extends BackendModel
{
    protected ?string $module = 'admins_tokens';

    protected ?string $defaultColumn = 'id';

    protected array $showAllAllowedFields = ['column', 'order', 'page', 'rows', 'searchFields'];

    protected array $allowedOrderColumns = ['email', 'token_create', 'token_expire', 'token_type']; 

    protected array $delAllowedFields = ['id', 'uuid'];

    protected array $showAllSearchAllowedFields = ['email', 'token_type']; 

    protected array $showAllSearchAllowedDates = ['token_create'];

    protected ?string $getDataQuery = "select admins_tokens.*, admins.uuid, admins.firstname, admins.lastname, admins.email, admins.superadmin 
                                        from admins_tokens 
                                        join admins 
                                        on admins.uuid = admins_tokens.admin_uuid 
                                        where 1 = 1";

    protected ?string $getNumRowsQuery = "select count(*) as count 
                                            from admins_tokens 
                                            join admins 
                                            on admins.uuid = admins_tokens.admin_uuid 
                                            where 1 = 1";

    protected ?string $getUUIDQuery = "select admins_tokens.id, admins.uuid, admins.firstname, admins.lastname, admins.deleted_at, admins.superadmin 
                                        from admins_tokens 
                                        join admins 
                                        on admins.uuid = admins_tokens.admin_uuid 
                                        where admins_tokens.id = ?";

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
            'searchFields.email' => [
                'label' => lang('backend/tokens.labels.username'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\']+$/u]'], 
            ],
            'searchFields.token_type' => [
                'label' => lang('backend/tokens.labels.token_type'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\']+$/u]'], 
            ],
            'searchDates.token_create-from' => [
                'label' => lang('backend/tokens.labels.dateFrom'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
            'searchDates.token_create-to' => [
                'label' => lang('backend/tokens.labels.dateTo'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
        ];
    }

    public function delValidationRules(): array
    {
        return [
            'uuid' => [
                'label' => lang('backend/tokens.labels.uuid'),
                'rules' => ['required', 'regex_match[/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i]'],
                'errors' => [
                    'required' => lang('backend/tokens.errors.uuid'), 
                    'regex_match' => lang('backend/tokens.errors.uuid') 
                ]
            ],
            'id' => [
                'label' => lang('backend/tokens.labels.id'),
                'rules' => ['required', 'is_natural_no_zero'],
                'errors' => [
                    'required' => lang('backend/tokens.errors.id'), 
                    'is_natural_no_zero' => lang('backend/tokens.errors.id') 
                ]
            ],
        ];
    }

    public function hardDelete(array $posts): array
    {
        /* Match dei posts con i campi consentiti */
        $posts = $this->checkAllowedFields($posts, $this->delAllowedFields);

        try 
        {
            /* Recupero i dati dell'utente prima dell'eliminazione */
            $data = $this->getByUUID($posts['id']);

            if($data['result'] === false):
                return ['result' => false, 'message' => $data['message']];
            endif;

            /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
            if ($data['row']->deleted_at !== null):
                return ['result' => false, 'message' => lang('backend/tokens.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
            endif;

            /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
            if ((int) $data['row']->superadmin === 1):
                return ['result'  => false, 'message' => lang('backend/tokens.messages.protectedAdmin')];
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
                log_admin_activity('DELETE_TOKEN', 'tokens', sprintf(lang('backend/tokens.audits.deleteToken'), esc($data['row']->firstname), esc($data['row']->lastname)), $currentAdmin);

                return ['result' => true, 'message' => sprintf(lang('backend/tokens.messages.deleteTokenSuccess'), esc($data['row']->firstname), esc($data['row']->lastname)), 'admin' => $data['row']];
            endif;

            return ['result' => false, 'message' => lang('backend/tokens.messages.deleteTokenError')];

        } catch(\Throwable $e) {

            log_message('error', lang('backend/tokens.messages.deleteTokenError') . ' - ' . $e);
            return ['result' => false, 'message' => lang('backend/tokens.messages.deleteTokenError')];

        }
    }
}