<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => 'Dashboard', 
	],
	'panels' => [
		'inventoryValue' => 'Inventory value',
		'valueByBrands' => 'Product value by brand',
		'valueByCategories' => 'Product value by category',
		'warehouse' => 'Inside the warehouse',
		'productsByBrands' => 'Products by brand',
		'productsByCategories' => 'Products by category',
	],
	'links' => ['showAll' => 'View list'],
	'labels' => ['superadminOnly' => 'Superadmin only', 'total' => 'total'],
	'descriptions' => [
		'inventoryValue' => 'Quantity × price · non-deleted products, including suspended products',
		'topValueBrands' => 'Top %d brands by value · quantity × price',
		'topValueCategories' => 'Top %d categories by value · quantity × price',
		'valuesNotAdditive' => 'Do not add these amounts to obtain the inventory total.',
		'topBrands' => 'Top %d brands · non-deleted products',
		'topCategories' => 'Top %d categories · non-deleted products',
		'multipleBrands' => 'A product can belong to more than one brand.',
		'multipleCategories' => 'A product can belong to more than one category.',
	],
	'messages' => [
		'noSections' => 'No sections are available for this dashboard.',
		'unavailable' => 'Statistics are temporarily unavailable.',
		'noAssociations' => 'No associations available.',
	],
	'formats' => ['thousandsSeparator' => ',', 'decimalSeparator' => '.'],
];
