<?php declare(strict_types = 1);

return [
    'menu' => [
        'topRight' => [
            'admins' => 'Administrators',
            'groups' => 'Groups',
            'audits' => 'Activity Log', 
            'tokens' => 'Tokens', 
            'logs' => 'Logs',
            'account' => 'Profile',
            'logout' => 'Logout'
        ],
        'bottomLeft' => [
            'dashboard' => 'Dashboards',
            'users' => 'Users',
            'messages' => 'Messages',
        ],
        'bottomRight' => [
            'settings' => 'Settings',
            'tools' => 'Tools', 
        ],
    ],
    'links' => [
        'selectAll' => 'Select All'
    ],
    'labels' => [
        'select' => 'Select'
    ],  
    'formats' => [
        'conversationalDate' => "EEEE d MMMM yyyy 'at' HH:mm:ss",
    ], 
    'buttons' => [
        'modules' => 'Modules',
        'services' => 'Services',
        'backToTop' => 'Back to Top', 
        'options' => 'Options', 
        'undo' => 'Undo', 
        'export' => 'Export', 
        'yes' => 'Yes', 
        'no' => 'No', 
        'close' => 'Close', 
        'exportPdf' => 'Export PDF', 
        'remove' => 'Remove', 
    ],
    'errors' => [
        'err403' => 'Security session expired or unauthorized access. Reloading...',
        'err404' => 'The requested resource was not found or has been removed.',
        'err500' => 'A critical error occurred while processing the request.',
        'err504' => 'The server took too long to respond. Operation cancelled.',
        'errNetwork' => 'Network connection unavailable. Check your internet connection status.',
    ], 
    'modals' => [
        'exportPdfTitle' => 'PDF Export Settings', 
        'exportPdfOrientation' => 'Orientation', 
        'exportPdfOrientationVert' => 'Vertical', 
        'exportPdfOrientationHoriz' => 'Horizontal', 
        'exportPdfFormat' => 'Format', 
        'exportPdfMargin' => 'Margins', 
        'exportPdfMarginSup' => 'Top', 
        'exportPdfMarginRgt' => 'Right', 
        'exportPdfMarginInf' => 'Bottom', 
        'exportPdfMarginLft' => 'Left', 
        'exportPdfCompression' => 'Compression', 
        'exportPdfImgQuality' => 'Image Quality', 
        'globalTitle' => 'Confirmation Request'
    ], 
    'pagination' => [
        'messageLeft' => 'Page %d of %d',
        'messageRight' => 'From %d to %d of %d',
        'first' => 'First',
        'last' => 'Last',
        'next' => 'Next',
        'previous' => 'Previous',
    ],
    'messages' => [
        'getDataError' => 'Error during list retrieval operation.',
        'getUUIDError' => 'Error during detail retrieval operation.', 
        'UUIDNotFound' => 'Record not found.', 
        'permissionDenied' => 'Access denied.'
    ]
];