<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => 'Logs', 
	], 
	'links' => [
		'filters' => 'Search', 
		'resetFilters' => 'Clear search', 
		'resetSorting' => 'Reset sorting', 
		'reloadList' => 'Reload list', 
	], 
	'errors' => [
		'uuid' => 'Invalid UUID.', 
		'id' => 'Invalid ID.', 
	], 
	'actions' => [
		'disconnectSession' => 'Disconnect'
	],
	'labels' => [
		'username' => 'Username', 
		'typeToken' => 'Type', 
		'createdAt' => 'Added on ', 
		'operatingSystem' => 'Operating System', 
		'browser' => 'Web Browser', 
		'ipAddress' => 'IP Address', 
		'dateFrom' => 'From Date', 
		'dateTo' => 'To Date',
		'login' => 'Login', 
		'logout' => 'Logout',  
		'logoutReason' => 'Logout way', 
		'manual' => 'Manual', 
		'timeout' => 'Timeout', 
		'deleted' => 'Deleted', 
		'banned' => 'Banned', 
		'undefined' => 'Undefined', 
		'pending' => 'Pending...', 
		'duration' => 'Duration',
		'mobile' => 'Mobile device',
		'robot' => 'Robot',
		'filtersAndOptions' => 'Options and filters',
		'showSystemDetails' => 'Show system details'
	],
	'placeholders' => [
		'searchUsername' => 'Search by username...', 
		'dateFrom' => 'Search by start date...', 
		'dateTo' => 'Search by end date...',
		'searchLogoutReason' => 'Search by logout reason...'
	], 
	'audits' => [
	    'disconnectSession' => 'Forced termination of current session',
	],
	'messages' => [
		'noLogsFound' => 'No logs found.', 
		'areYouSureDisconnectSession' => 'Are you sure you want to disconnect <b>%s %s</b>?', 
		'validationErrors' => 'Validation errors.', 
		'validationToastErrors' => '%s', 
		'disconnectSessionSuccess' => '%s %s was disconnected successfully.', 
		'disconnectSessionError' => 'Disconnection failed.', 
		'protectedAdmin' => 'Protected from modifications.', 
		'cannotModifyDeleted' => 'Cannot modify a deleted admin.', 
	]
];
