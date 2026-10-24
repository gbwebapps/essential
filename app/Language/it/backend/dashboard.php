<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => 'Dashboard', 
	],
	'panels' => [
		'inventoryValue' => 'Valore del magazzino',
		'valueByBrands' => 'Valore prodotti per marca',
		'valueByCategories' => 'Valore prodotti per categoria',
		'warehouse' => 'Dentro il magazzino',
		'productsByBrands' => 'Prodotti per marca',
		'productsByCategories' => 'Prodotti per categoria',
	],
	'links' => ['showAll' => 'Visualizza elenco'],
	'labels' => ['superadminOnly' => 'Solo superadmin', 'total' => 'totali'],
	'descriptions' => [
		'inventoryValue' => 'Quantità × prezzo · prodotti non eliminati, inclusi i sospesi',
		'topValueBrands' => 'Prime %d marche per valore · quantità × prezzo',
		'topValueCategories' => 'Prime %d categorie per valore · quantità × prezzo',
		'valuesNotAdditive' => 'Questi importi non vanno sommati per ottenere il totale del magazzino.',
		'topBrands' => 'Prime %d marche · prodotti non eliminati',
		'topCategories' => 'Prime %d categorie · prodotti non eliminati',
		'multipleBrands' => 'Uno stesso prodotto può appartenere a più marche.',
		'multipleCategories' => 'Uno stesso prodotto può appartenere a più categorie.',
	],
	'messages' => [
		'noSections' => 'Non ci sono sezioni disponibili per questa dashboard.',
		'unavailable' => 'Statistiche temporaneamente non disponibili.',
		'noAssociations' => 'Nessuna associazione disponibile.',
	],
	'formats' => ['thousandsSeparator' => '.', 'decimalSeparator' => ','],
];
