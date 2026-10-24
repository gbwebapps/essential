<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => '仪表板', 
	],
	'panels' => [
		'inventoryValue' => '库存总值',
		'valueByBrands' => '按品牌统计产品价值',
		'valueByCategories' => '按类别统计产品价值',
		'warehouse' => '仓库概览',
		'productsByBrands' => '按品牌统计产品',
		'productsByCategories' => '按类别统计产品',
	],
	'links' => ['showAll' => '查看列表'],
	'labels' => ['superadminOnly' => '仅超级管理员', 'total' => '总计'],
	'descriptions' => [
		'inventoryValue' => '数量 × 价格 · 未删除的产品，包括已停用的产品',
		'topValueBrands' => '价值最高的前 %d 个品牌 · 数量 × 价格',
		'topValueCategories' => '价值最高的前 %d 个类别 · 数量 × 价格',
		'valuesNotAdditive' => '请勿将这些金额相加作为库存总值。',
		'topBrands' => '前 %d 个品牌 · 未删除的产品',
		'topCategories' => '前 %d 个类别 · 未删除的产品',
		'multipleBrands' => '同一产品可以属于多个品牌。',
		'multipleCategories' => '同一产品可以属于多个类别。',
	],
	'messages' => [
		'noSections' => '此仪表板没有可用的版块。',
		'unavailable' => '统计数据暂时不可用。',
		'noAssociations' => '没有可用的关联。',
	],
	'formats' => ['thousandsSeparator' => ',', 'decimalSeparator' => '.'],
];
