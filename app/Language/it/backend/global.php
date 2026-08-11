<?php declare(strict_types = 1);

return [
    'menu' => [
        'topRight' => [
            'admins' => 'Amministratori',
            'groups' => 'Gruppi',
            'audits' => 'Registro attività', 
            'tokens' => 'Tokens', 
            'account' => 'Profilo',
            'logout' => 'Esci'
        ],
        'bottomLeft' => [
            'dashboard' => 'Pannelli',
            'users' => 'Utenti',
            'messages' => 'Messaggi',
        ],
        'bottomRight' => [
            'settings' => 'Impostazioni',
            'tools' => 'Strumenti', 
        ],
    ],
    'links' => [
        'selectAll' => 'Seleziona tutti'
    ], 
    'buttons' => [
        'modules' => 'Moduli',
        'services' => 'Servizi',
        'backToTop' => 'Torna in alto', 
        'options' => 'Opzioni', 
        'undo' => 'Annulla', 
        'export' => 'Esporta', 
        'yes' => 'Si', 
        'no' => 'No'
    ],
    'modals' => [
        'exportPdfTitle' => 'Impostazioni Esportazione PDF', 
        'exportPdfOrientation' => 'Orientamento', 
        'exportPdfOrientationVert' => 'Verticale', 
        'exportPdfOrientationHoriz' => 'Orizzontale', 
        'exportPdfFormat' => 'Formato', 
        'exportPdfMargin' => 'Margini', 
        'exportPdfMarginSup' => 'Superiore', 
        'exportPdfMarginRgt' => 'Destro', 
        'exportPdfMarginInf' => 'Inferiore', 
        'exportPdfMarginLft' => 'Sinistro', 
        'exportPdfCompression' => 'Compressione', 
        'exportPdfImgQuality' => 'Qualità immagine', 
        'globalTitle' => 'Richiesta di conferma'
    ], 
    'pagination' => [
        'messageLeft' => 'Pagina %d di %d',
        'messageRight' => 'Da %d a %d di %d',
        'first' => 'Primo',
        'last' => 'Ultimo',
        'next' => 'Prossimo',
        'previous' => 'Precedente',
    ],
    'messages' => [
        'getDataError' => 'Errore durante operazione di recupero lista.',
        'getUUIDError' => 'Errore durante operazione di recupero dettaglio.', 
        'UUIDNotFound' => 'Record non trovato.', 
        'permissionDenied' => 'Accesso negato.'
    ]
];