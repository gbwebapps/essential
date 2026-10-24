<?php declare(strict_types = 1);

return [
    'menu' => [
        'topRight' => [
            'admins' => 'Administratoren',
            'groups' => 'Gruppen',
            'audits' => 'Aktivitätsprotokoll', 
            'tokens' => 'Tokens', 
            'logs' => 'Zugriffe', 
            'account' => 'Profil',
            'logout' => 'Abmelden'
        ],
        'bottomLeft' => [
            'dashboard' => 'Panels',
            'brands' => 'Marken',
            'categories' => 'Kategorien',
            'products' => 'Produkte',
        ],
        'bottomRight' => [
            'settings' => 'Einstellungen',
            'tools' => 'Werkzeuge', 
            'warehouse' => 'Lagerverwaltung',
        ],
    ],
    'menuCounts' => [
        'active' => 'Aktiv',
        'trashed' => 'Gelöscht',
        'suspended' => 'Deaktiviert',
    ],
    'links' => [
        'selectAll' => 'Alle auswählen'
    ], 
    'labels' => [
        'select' => 'Auswählen'
    ], 
    'formats' => [
        'conversationalDate' => "EEEE, d. MMMM yyyy 'um' HH:mm:ss",
    ], 
    'buttons' => [
        'modules' => 'Module',
        'services' => 'Dienste',
        'backToTop' => 'Nach oben', 
        'expandScreen' => 'Vollbild aktivieren',
        'reduceScreen' => 'Vollbild beenden',
        'options' => 'Optionen', 
        'undo' => 'Abbrechen', 
        'export' => 'Exportieren', 
        'yes' => 'Ja', 
        'no' => 'Nein', 
        'close' => 'Schließen', 
        'exportPdf' => 'PDF exportieren', 
        'remove' => 'Entfernen', 
    ],
    'errors' => [
        'err403' => 'Sicherheitssitzung abgelaufen oder nicht autorisierter Zugriff. Neu laden...',
        'err404' => 'Die angeforderte Ressource wurde nicht gefunden oder entfernt.',
        'err500' => 'Ein kritischer Fehler ist bei der Verarbeitung der Anfrage aufgetreten.',
        'err504' => 'Der Server hat zu lange für eine Antwort gebraucht. Vorgang abgebrochen.',
        'errNetwork' => 'Keine Netzwerkverbindung. Bitte überprüfen Sie Ihre Internetverbindung.',
        'fullscreenError' => 'Der Vollbildmodus konnte nicht geändert werden.',
    ], 
    'modals' => [
        'exportPdfTitle' => 'PDF-Exporteinstellungen', 
        'exportPdfOrientation' => 'Ausrichtung', 
        'exportPdfOrientationVert' => 'Vertikal', 
        'exportPdfOrientationHoriz' => 'Horizontal', 
        'exportPdfFormat' => 'Format', 
        'exportPdfMargin' => 'Ränder', 
        'exportPdfMarginSup' => 'Oben', 
        'exportPdfMarginRgt' => 'Rechts', 
        'exportPdfMarginInf' => 'Unten', 
        'exportPdfMarginLft' => 'Links', 
        'exportPdfCompression' => 'Komprimierung', 
        'exportPdfImgQuality' => 'Bildqualität', 
        'globalTitle' => 'Bestätigung erforderlich'
    ], 
    'pagination' => [
        'messageLeft' => 'Seite %d von %d',
        'messageRight' => 'Von %d bis %d von %d',
        'first' => 'Erste',
        'last' => 'Letzte',
        'next' => 'Nächste',
        'previous' => 'Vorherige',
    ],
    'audits' => [
        'importData' => 'Datenimport in Tabelle %s. %d Datensätze eingefügt, %d Datensätze aktualisiert.',
    ],
    'messages' => [
        'getDataError' => 'Fehler beim Abrufen der Liste.',
        'getUUIDError' => 'Fehler beim Abrufen der Details.', 
        'UUIDNotFound' => 'Datensatz nicht gefunden.', 
        'permissionDenied' => 'Zugriff verweigert.',
        'unsavedChanges' => 'Es gibt nicht gespeicherte Änderungen. Möchten Sie die Seite verlassen, ohne zu speichern?'
    ]
];
