<?php declare(strict_types = 1);

namespace App\Models\Backend;

use App\Models\Backend\BackendModel;

class AuditsModel extends BackendModel
{
    protected ?string $module = 'admins_audits';

    protected ?string $defaultColumn = 'id';

    protected array $showAllAllowedFields = ['column', 'order', 'page', 'rows', 'searchFields'];

    protected array $allowedOrderColumns = ['username', 'action', 'section', 'details']; 

    protected array $showAllSearchAllowedFields = ['username', 'section', 'action', 'details']; 

    protected array $showAllSearchAllowedDates = ['created_at'];

    protected ?string $getDataQuery = "select admins_audits.id, admin_uuid, username, action, section, details, ip_address, user_agent, admins_audits.created_at, superadmin 
                                       from admins_audits 
                                       join admins 
                                       on admins.uuid = admins_audits.admin_uuid 
                                       where 1 = 1";

    protected ?string $getNumRowsQuery = 'select count(*) as count from admins_audits where 1 = 1';

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
                'label' => lang('backend/admins.labels.username'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\']+$/u]'], 
            ],
            'searchFields.action' => [
                'label' => lang('backend/admins.labels.action'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\']+$/u]'], 
            ],
            'searchFields.section' => [
                'label' => lang('backend/admins.labels.section'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\']+$/u]'], 
            ],
            'searchFields.details' => [
                'label' => lang('backend/admins.labels.details'), 
                'rules' => ['permit_empty', 'regex_match[/^[a-zA-ZÀ-ÖØ-öø-ÿ\']+$/u]'], 
            ],
            'searchDates.created_at-from' => [
                'label' => lang('backend/audits.labels.dateFrom'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
            'searchDates.created_at-to' => [
                'label' => lang('backend/audits.labels.dateTo'),
                'rules' => ['permit_empty', 'valid_date[Y-m-d H:i:s]'],
            ],
        ];
    }

    public function logActivity(string $action, string $section, string $details, ?object $identity = null): bool
    {
        $request = \Config\Services::request();

        $adminUuid = $identity ? ($identity->uuid ?: null) : null;
        $username = $identity ? ($identity->email ?: 'Ospite') : 'Ospite';
        $ipAddress = $request->getIPAddress();
        $userAgent = (string) $request->getUserAgent();
        $createdAt = date('Y-m-d H:i:s');

        /* Scriviamo la query SQL nativa utilizzando i segnaposto ? */
        $sql = "insert into `admins_audits` (`admin_uuid`, `username`, `action`, `section`, `details`, `ip_address`, `user_agent`, `created_at`) values (?, ?, ?, ?, ?, ?, ?, ?)";

        /* Eseguiamo la query passando i parametri nell'array di binding */
        return $this->db->query($sql, [$adminUuid, $username, $action, $section, $details, $ipAddress, $userAgent, $createdAt]);
    }
}