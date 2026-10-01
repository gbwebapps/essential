<?php declare(strict_types = 1);

return [
	'title' => 'Galerie d\'images', 
    'labels' => [
        'removeCover' => 'Supprimer la couverture',
        'setCover' => 'Définir la couverture',
        'delete' => 'Supprimer l\'image',
        'viewImage' => 'Afficher l\'image',
        'noImagesFound' => 'Aucune image trouvée.',
        'id' => 'ID', 
        'uuid' => 'UUID', 
        'entity' => 'Entité', 
        'filename' => 'Nom du fichier', 
        'context' => 'Contexte', 
    ], 
    'buttons' => [
        'reload' => 'Recharger la galerie', 
    ], 
    'audits' => [
        'deleteImage' => 'Suppression de l\'image.',
        'setCover'    => 'Définition de la couverture.',
        'removeCover' => 'Suppression de la couverture.',
    ],
    'messages' => [
        'areYouSureRemoveCover' => 'Êtes-vous sûr de vouloir supprimer la couverture de cette image ?',
        'areYouSureSetCover' => 'Êtes-vous sûr de vouloir définir cette image comme couverture ?',
        'areYouSureDeleteImage' => 'Êtes-vous sûr de vouloir supprimer cette image ?',
        'setCoverError' => 'Erreur lors de la définition de la couverture.', 
        'setCoverSuccess' => 'Couverture définie avec succès.', 
        'removeCoverSuccess' => 'Couverture supprimée avec succès.', 
        'deleteError' => 'Erreur lors de la suppression de l\'image.', 
        'deleteSuccess' => 'Image supprimée avec succès.', 
        'removeCoverError' => 'Erreur lors de la suppression de la couverture.', 
        'validationToastErrors' => '%s', 
    ],
];
