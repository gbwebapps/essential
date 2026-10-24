<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => 'Dashboard', 
	],
	'panels' => [
		'inventoryValue' => 'Lagerwert',
		'valueByBrands' => 'Produktwert nach Marke',
		'valueByCategories' => 'Produktwert nach Kategorie',
		'warehouse' => 'Lagerübersicht',
		'productsByBrands' => 'Produkte nach Marke',
		'productsByCategories' => 'Produkte nach Kategorie',
	],
	'links' => ['showAll' => 'Liste anzeigen'],
	'labels' => ['superadminOnly' => 'Nur Superadmin', 'total' => 'gesamt'],
	'descriptions' => [
		'inventoryValue' => 'Menge × Preis · nicht gelöschte Produkte, einschließlich gesperrter Produkte',
		'topValueBrands' => 'Top %d Marken nach Wert · Menge × Preis',
		'topValueCategories' => 'Top %d Kategorien nach Wert · Menge × Preis',
		'valuesNotAdditive' => 'Diese Beträge dürfen nicht zum Gesamtlagerwert addiert werden.',
		'topBrands' => 'Top %d Marken · nicht gelöschte Produkte',
		'topCategories' => 'Top %d Kategorien · nicht gelöschte Produkte',
		'multipleBrands' => 'Ein Produkt kann mehreren Marken zugeordnet sein.',
		'multipleCategories' => 'Ein Produkt kann mehreren Kategorien zugeordnet sein.',
	],
	'messages' => [
		'noSections' => 'Für dieses Dashboard sind keine Bereiche verfügbar.',
		'unavailable' => 'Statistiken sind vorübergehend nicht verfügbar.',
		'noAssociations' => 'Keine Zuordnungen vorhanden.',
	],
	'formats' => ['thousandsSeparator' => '.', 'decimalSeparator' => ','],
];
