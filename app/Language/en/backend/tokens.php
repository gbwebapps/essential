<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => 'Tokens',
	],
	'options' => [
		'first' => 'First option', 
		'second' => 'Second option', 
		'thirst' => 'Third option', 
		],
	'links' => [
		'filters' => 'Search', 
		'resetFilters' => 'Clear search', 
		'resetSorting' => 'Reset sorting', 
		'reloadList' => 'Reload list', 
		'export' => 'Export CSV', 
		],
	'labels' => [
		'id' => 'ID',
		'uuid' => 'UUID', 
		'username' => 'Username', 
		'tokenCreate' => 'Start Date', 
		'tokenExpire' => 'Expiration Date', 
		'tokenType' => 'Type', 
		'operatingSystem' => 'Operating System', 
		'browser' => 'Web Browser', 
		'ipAddress' => 'IP Address', 
		'dateFrom' => 'Start Date From', 
		'dateTo' => 'Start Date To',
		'createdAt' => 'Added on ', 
		'session' => 'Session', 
		'activation' => 'Activation', 
		'cookie' => 'Remember Me'
	],
	'errors' => [
		'uuid' => 'Invalid UUID.', 
		'id' => 'Invalid ID.', 
	], 
	'actions' => [
		'hardDelete' => 'Delete'
	], 
	'buttons' => [
		'actions' => 'Actions'
	], 
	'placeholders' => [
		'searchUsername' => 'Search by username...', 
		'searchType' => 'Search by type...', 
		'dateFrom' => 'Search by start date...', 
		'dateTo' => 'Search by start date...',
	], 
	'messages' => [
		'validationErrors' => 'Validation errors.', 
		'validationToastErrors' => '%s', 
		'noTokensFound' => 'No tokens found.', 
		'areYouSureHardDelete' => 'Are you sure you want to permanently delete the token for <b>%s %s</b>?', 
		'deleteTokenSuccess' => 'The token for %s %s was deleted successfully.', 
		'deleteTokenError' => 'Token deletion failed.', 
		'protectedAdmin' => 'Protected from modifications.', 
		'cannotModifyDeleted' => 'Cannot modify a deleted admin.', 
	]
];