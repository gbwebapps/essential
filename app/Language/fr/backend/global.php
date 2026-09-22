<?php declare(strict_types = 1);

return [
    'menu' => [
        'topRight' => [
            'admins' => 'Administrateurs',
            'groups' => 'Groupes',
            'audits' => 'Registre d\'activités', 
            'tokens' => 'Jetons', 
            'logs' => 'Accès', 
            'account' => 'Profil',
            'logout' => 'Se déconnecter'
        ],
        'bottomLeft' => [
            'dashboard' => 'Panneaux',
            'users' => 'Utilisateurs',
            'messages' => 'Messages',
        ],
        'bottomRight' => [
            'settings' => 'Paramètres',
            'tools' => 'Outils', 
        ],
    ],
    'links' => [
        'selectAll' => 'Tout sélectionner'
    ], 
    'labels' => [
        'select' => 'Sélectionner'
    ], 
    'formats' => [
        'conversationalDate' => "EEEE d MMMM yyyy 'à' HH:mm:ss",
    ], 
    'buttons' => [
        'modules' => 'Modules',
        'services' => 'Services',
        'backToTop' => 'Retour en haut', 
        'options' => 'Options', 
        'undo' => 'Annuler', 
        'export' => 'Exporter', 
        'yes' => 'Oui', 
        'no' => 'Non', 
        'close' => 'Fermer', 
        'exportPdf' => 'Exporter en PDF', 
        'remove' => 'Retirer', 
    ],
    'errors' => [
        'err403' => 'Session de sécurité expirée ou accès non autorisé. Rechargement en cours...',
        'err404' => 'La ressource demandée est introuvable ou a été supprimée.',
        'err500' => 'Une erreur critique est survenue lors du traitement de la demande.',
        'err504' => 'Le serveur a mis trop de temps à répondre. Opération annulée.',
        'errNetwork' => 'Aucune connexion réseau. Veuillez vérifier l\'état de votre connexion Internet.',
    ], 
    'modals' => [
        'exportPdfTitle' => 'Paramètres d\'exportation PDF', 
        'exportPdfOrientation' => 'Orientation', 
        'exportPdfOrientationVert' => 'Verticale', 
        'exportPdfOrientationHoriz' => 'Horizontale', 
        'exportPdfFormat' => 'Format', 
        'exportPdfMargin' => 'Marges', 
        'exportPdfMarginSup' => 'Supérieure', 
        'exportPdfMarginRgt' => 'Droite', 
        'exportPdfMarginInf' => 'Inférieure', 
        'exportPdfMarginLft' => 'Gauche', 
        'exportPdfCompression' => 'Compression', 
        'exportPdfImgQuality' => 'Qualité de l\'image', 
        'globalTitle' => 'Demande de confirmation'
    ], 
    'pagination' => [
        'messageLeft' => 'Page %d sur %d',
        'messageRight' => 'De %d à %d sur %d',
        'first' => 'Premier',
        'last' => 'Dernier',
        'next' => 'Suivant',
        'previous' => 'Précédent',
    ],
    'audits' => [
        'importData' => 'Importation de données dans la table %s. Enregistrements insérés : %d, mis à jour : %d.',
    ],
    'messages' => [
        'getDataError' => 'Erreur lors de la récupération de la liste.',
        'getUUIDError' => 'Erreur lors de la récupération du détail.', 
        'UUIDNotFound' => 'Enregistrement introuvable.', 
        'permissionDenied' => 'Accès refusé.'
    ]
];
