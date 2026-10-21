<?php declare(strict_types = 1);

namespace Modules\Backend\Brands\Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Definisce i percorsi namespaced delle viste del modulo Brands.
 */
class Views extends BaseConfig
{
    public const INDEX_VIEW = 'Modules\\Backend\\Brands\\Views\\indexView';
    public const SHOW_ALL_VIEW = 'Modules\\Backend\\Brands\\Views\\showAllView';
    public const ADD_VIEW = 'Modules\\Backend\\Brands\\Views\\addView';
    public const EDIT_VIEW = 'Modules\\Backend\\Brands\\Views\\editView';
    public const SHOW_VIEW = 'Modules\\Backend\\Brands\\Views\\showView';

    public const INDEX_PARTIAL = 'Modules\\Backend\\Brands\\Views\\partials\\index\\indexPartial';
    public const SHOW_ALL_PARTIAL = 'Modules\\Backend\\Brands\\Views\\partials\\showAll\\showAllPartial';
    public const ADD_PARTIAL = 'Modules\\Backend\\Brands\\Views\\partials\\add\\addPartial';
    public const EDIT_PARTIAL = 'Modules\\Backend\\Brands\\Views\\partials\\edit\\editPartial';
    public const EDIT_GENERAL_DATA_PARTIAL = 'Modules\\Backend\\Brands\\Views\\partials\\edit\\generalDataPartial';
    public const SHOW_PARTIAL = 'Modules\\Backend\\Brands\\Views\\partials\\show\\showPartial';
    public const SHOW_GENERAL_DATA_PARTIAL = 'Modules\\Backend\\Brands\\Views\\partials\\show\\generalDataPartial';
    public const CHANGE_STATUS_PARTIAL = 'Modules\\Backend\\Brands\\Views\\partials\\show\\changeStatusPartial';
    public const META_DATA_PARTIAL = 'Modules\\Backend\\Brands\\Views\\partials\\common\\metaDataPartial';
}
