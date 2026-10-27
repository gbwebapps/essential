<?php declare(strict_types = 1);

namespace Modules\Backend\Warehouse\Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Definisce i percorsi namespaced delle viste del modulo Warehouse.
 */
class Views extends BaseConfig
{
    /* VISTE PRINCIPALI --------------------------------------------------------------------------- */

    public const INDEX_VIEW = 'Modules\\Backend\\Warehouse\\Views\\indexView';

    /* VISTE PARZIALI ----------------------------------------------------------------------------- */

    public const INDEX_PARTIAL = 'Modules\\Backend\\Warehouse\\Views\\partials\\index\\indexPartial';
    public const IMPORT_PREVIEW_PARTIAL = 'Modules\\Backend\\Warehouse\\Views\\partials\\import\\previewPartial';
    public const IMPORT_MODAL = 'backend/components/import/showModalView';
    public const IMPORT_ERRORS_PARTIAL = 'backend/components/import/errorsModalPartial';
    public const IMPORT_LOADING_PARTIAL = 'backend/components/import/loadingModalPartial';
    public const EXPORT_MODAL = 'backend/components/export/showModalView';
}
