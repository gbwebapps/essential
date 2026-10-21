<?php declare(strict_types = 1);

namespace Modules\Backend\Categories\Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Definisce i percorsi namespaced delle viste del modulo Categories.
 */
class Views extends BaseConfig
{
    public const INDEX_VIEW = 'Modules\\Backend\\Categories\\Views\\indexView';
    public const SHOW_ALL_VIEW = 'Modules\\Backend\\Categories\\Views\\showAllView';
    public const ADD_VIEW = 'Modules\\Backend\\Categories\\Views\\addView';
    public const EDIT_VIEW = 'Modules\\Backend\\Categories\\Views\\editView';
    public const SHOW_VIEW = 'Modules\\Backend\\Categories\\Views\\showView';

    public const INDEX_PARTIAL = 'Modules\\Backend\\Categories\\Views\\partials\\index\\indexPartial';
    public const SHOW_ALL_PARTIAL = 'Modules\\Backend\\Categories\\Views\\partials\\showAll\\showAllPartial';
    public const ADD_PARTIAL = 'Modules\\Backend\\Categories\\Views\\partials\\add\\addPartial';
    public const EDIT_PARTIAL = 'Modules\\Backend\\Categories\\Views\\partials\\edit\\editPartial';
    public const EDIT_GENERAL_DATA_PARTIAL = 'Modules\\Backend\\Categories\\Views\\partials\\edit\\generalDataPartial';
    public const SHOW_PARTIAL = 'Modules\\Backend\\Categories\\Views\\partials\\show\\showPartial';
    public const SHOW_GENERAL_DATA_PARTIAL = 'Modules\\Backend\\Categories\\Views\\partials\\show\\generalDataPartial';
    public const CHANGE_STATUS_PARTIAL = 'Modules\\Backend\\Categories\\Views\\partials\\show\\changeStatusPartial';
    public const META_DATA_PARTIAL = 'Modules\\Backend\\Categories\\Views\\partials\\common\\metaDataPartial';
    public const CATEGORY_TREE_PARTIAL = 'Modules\\Backend\\Categories\\Views\\partials\\common\\categoryTreePartial';
}
