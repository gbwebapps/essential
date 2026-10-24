<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => 'Tableau de bord', 
	],
	'panels' => [
		'inventoryValue' => 'Valeur du stock',
		'valueByBrands' => 'Valeur des produits par marque',
		'valueByCategories' => 'Valeur des produits par catégorie',
		'warehouse' => 'Vue de l’entrepôt',
		'productsByBrands' => 'Produits par marque',
		'productsByCategories' => 'Produits par catégorie',
	],
	'links' => ['showAll' => 'Voir la liste'],
	'labels' => ['superadminOnly' => 'Superadmin uniquement', 'total' => 'au total'],
	'descriptions' => [
		'inventoryValue' => 'Quantité × prix · produits non supprimés, y compris les produits suspendus',
		'topValueBrands' => 'Les %d premières marques par valeur · quantité × prix',
		'topValueCategories' => 'Les %d premières catégories par valeur · quantité × prix',
		'valuesNotAdditive' => 'Ces montants ne doivent pas être additionnés pour obtenir la valeur totale du stock.',
		'topBrands' => 'Les %d premières marques · produits non supprimés',
		'topCategories' => 'Les %d premières catégories · produits non supprimés',
		'multipleBrands' => 'Un produit peut appartenir à plusieurs marques.',
		'multipleCategories' => 'Un produit peut appartenir à plusieurs catégories.',
	],
	'messages' => [
		'noSections' => 'Aucune section disponible pour ce tableau de bord.',
		'unavailable' => 'Statistiques temporairement indisponibles.',
		'noAssociations' => 'Aucune association disponible.',
	],
	'formats' => ['thousandsSeparator' => ' ', 'decimalSeparator' => ','],
];
