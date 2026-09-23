<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

class LogsModel extends BackendModel
{
    protected ?string $module = 'admins_logs';

    protected ?string $defaultColumn = 'id';

    protected array $showAllAllowedFields = ['column', 'order', 'page', 'rows', 'searchFields'];

    protected array $allowedOrderColumns = ['username', 'login', 'logout', 'logout_reason']; 

    protected array $showAllSearchAllowedFields = ['username', 'logout_reason']; 

    protected array $showAllSearchAllowedDates = ['login'];

    protected ?string $getDataQuery = "select admins_logs.*, admins_tokens.token_expire, admins_tokens.last_activity, admins_tokens.id as token_id_val, admins.firstname, admins.lastname, admins.superadmin  
                                        from admins_logs 
                                        left join admins_tokens 
                                        on admins_tokens.id = admins_logs.token_id 
                                        left join admins 
                                        on admins.uuid = admins_logs.admin_uuid 
                                        where 1 = 1";

    protected ?string $getNumRowsQuery = "select count(*) as count from admins_logs where 1 = 1";

    protected ?string $getUUIDQuery = "select * from admins_logs where id = ?";

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
            'searchFields.username' => [
                'label' => lang('backend/logs.labels.username'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\']+$/u]'], 
            ],
            'searchFields.logout_reason' => [
                'label' => lang('backend/logs.labels.logoutReason'), 
                'rules' => ['permit_empty', 'in_list[manual,timeout,deleted,banned]'], 
            ],
            'searchDates.login-from' => [
                'label' => lang('backend/logs.labels.dateFrom'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
            'searchDates.login-to' => [
                'label' => lang('backend/logs.labels.dateTo'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
        ];
    }

    public function deleteToken(int $tokenId): array
    {
        try {
            /* 1. Recupero il token tramite il suo ID */
            $tokenSql = "select at.id, at.admin_uuid, at.last_activity, at.token_type, a.firstname, a.lastname, a.superadmin, a.deleted_at 
                         from admins_tokens as at 
                         join admins as a 
                         on at.admin_uuid = a.uuid 
                         where at.id = ?";
            $tokenRow = $this->db->query($tokenSql, [$tokenId])->getRow();

            if ($tokenRow):

                /* Scudo Enterprise: blocco immediato se il record si trova nel cestino */
                if ($tokenRow->deleted_at !== null):
                    return ['result' => false, 'message' => lang('backend/logs.messages.cannotModifyDeleted')]; /* Ricorda di creare la stringa lingua */
                endif;

                /* Scudo di sicurezza: blocchi subito se l'oggetto estratto è il superadmin */
                if ((int) $tokenRow->superadmin === 1):
                    return ['result'  => false, 'message' => lang('backend/logs.messages.protectedAdmin')];
                endif;
                
                /* 2. Aggiorno il log registrando la forzatura (banned) */
                if (in_array($tokenRow->token_type, ['cookie', 'session'])):
                    $logoutTime = ! empty($tokenRow->last_activity) ? $tokenRow->last_activity : date('Y-m-d H:i:s');
                    
                    $logUpdateSql = "update admins_logs set logout = ?, logout_reason = 'banned' where token_id = ?";
                    $this->db->query($logUpdateSql, [$logoutTime, $tokenRow->id]);
                endif;

                /* 3. Elimino fisicamente il token */
                $sqlDelete = "delete from admins_tokens where id = ?";
                $this->db->query($sqlDelete, [$tokenRow->id]);

                /* 4. Log dell'azione per l'audit di sistema (opzionale ma consigliato) */
                $currentAdmin = service('authorization')->currentAdmin();
                log_admin_activity('DELETE_TOKEN', 'logs', lang('backend/logs.audits.deleteToken'), $currentAdmin);

                return ['result' => true, 'message' => sprintf(lang('backend/logs.messages.deleteTokenSuccess'), esc($tokenRow->firstname), esc($tokenRow->lastname))];
                
            endif;

            return ['result' => false, 'message' => lang('backend/logs.messages.deleteTokenError')];

        } catch (\Throwable $e) {
            
            /* Tracciamento dell'errore tecnico */
            log_message('error', 'Errore ban sessione - ' . $e);
            return ['result' => false, 'message' => lang('backend/logs.messages.deleteTokenError')];
            
        }
    }
}