<?php declare(strict_types = 1);

return [
	'titles' => [
		'index' => 'Panel de control',
	],
	'panels' => [
		'inventoryValue' => 'Valor del almacén',
		'valueByBrands' => 'Valor de productos por marca',
		'valueByCategories' => 'Valor de productos por categoría',
		'warehouse' => 'Dentro del almacén',
		'productsByBrands' => 'Productos por marca',
		'productsByCategories' => 'Productos por categoría',
	],
	'links' => ['showAll' => 'Ver listado'],
	'labels' => ['superadminOnly' => 'Solo superadmin', 'total' => 'en total'],
	'descriptions' => [
		'inventoryValue' => 'Cantidad × precio · productos no eliminados, incluidos los suspendidos',
		'topValueBrands' => 'Primeras %d marcas por valor · cantidad × precio',
		'topValueCategories' => 'Primeras %d categorías por valor · cantidad × precio',
		'valuesNotAdditive' => 'Estos importes no deben sumarse para obtener el total del almacén.',
		'topBrands' => 'Primeras %d marcas · productos no eliminados',
		'topCategories' => 'Primeras %d categorías · productos no eliminados',
		'multipleBrands' => 'Un producto puede pertenecer a varias marcas.',
		'multipleCategories' => 'Un producto puede pertenecer a varias categorías.',
	],
	'messages' => [
		'noSections' => 'No hay secciones disponibles para este panel.',
		'unavailable' => 'Estadísticas temporalmente no disponibles.',
		'noAssociations' => 'No hay asociaciones disponibles.',
	],
	'formats' => ['thousandsSeparator' => '.', 'decimalSeparator' => ','],
];
