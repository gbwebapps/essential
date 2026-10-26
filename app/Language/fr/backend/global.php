<?php declare(strict_types = 1);

return [
    'interface' => [
        'showPassword' => 'Afficher le mot de passe',
        'hidePassword' => 'Masquer le mot de passe',
        'themes' => 'Thèmes',
        'changeError' => 'Impossible de modifier la préférence. Réessayez.',
    ],
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
            'brands' => 'Marques',
            'categories' => 'Catégories',
            'products' => 'Produits',
        ],
        'bottomRight' => [
            'settings' => 'Paramètres',
            'tools' => 'Outils', 
            'warehouse' => 'Entrepôt',
        ],
    ],
    'menuCounts' => [
        'active' => 'Actifs',
        'trashed' => 'Supprimés',
        'suspended' => 'Désactivés',
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
        'expandScreen' => 'Agrandir l’écran',
        'reduceScreen' => 'Réduire l’écran',
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
        'fullscreenError' => 'Impossible de modifier le mode plein écran.',
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
        'permissionDenied' => 'Accès refusé.',
        'unsavedChanges' => 'Des modifications ne sont pas enregistrées. Voulez-vous quitter sans enregistrer ?'
    ]
];
