<?php declare(strict_types = 1); 

return [
	'titles' => [
		'index' => 'Groups', 
	],
	'panels' => [
		'addGroup' => 'Add group', 
		'listGroup' => 'Group list', 
		'exceptionsPerms' => 'Permission exceptions'
	], 
	'options' => [
		'first' => 'First option', 
		'second' => 'Second option', 
		'thirst' => 'Third option', 
	], 
	'labels' => [
		'id' => 'ID', 
		'groupName' => 'Group name', 
		'groupDescription' => 'Group description', 
		'assignedToGroup' => 'Assigned to GROUP', 
		'notAssignedToGroup' => 'Not assigned to GROUP', 
		'admins' => 'Search administrator', 
		'name' => 'Group name',
		'description' => 'Group description',
		'permissions' => 'Permissions', 
		'query' => 'Search field'
	], 
	'placeholders' => [
		'groupName' => 'Enter group name...', 
		'groupDescription' => 'Enter group description...', 
		'admins' => 'Type the first three letters...'
	], 
	'buttons' => [
		'delete' => 'Delete', 
		'refreshData' => 'Reload data', 
		'resetData' => 'Reset data', 
		'sendData' => 'Save data', 
	], 
	'errors' => [
		'permission' => 'Invalid permission.', 
		'wrongUUID' => 'Incorrect UUID format.', 
		'wrongID' => 'Incorrect ID format', 
	],
	'messages' => [
		'areYouSureDeleteGroup' => 'Are you sure you want to delete this group?', 
		'areYouSureToReload' => 'Are you sure you want to reload the data?', 
		'areYouSureToResetData' => 'Are you sure you want to reset the data?', 
		'noAdminFound' => 'No administrator found.', 
		'noGroupFound' => 'No group found.', 
		'noDataChanged' => 'No changes were made.', 
		'validationErrors' => 'Validation errors.', 
		'validateToastErrors' => '%s', 
		'addError' => 'Group addition failed.', 
		'addSuccess' => 'Group added successfully.', 
		'editError' => 'Group update failed.',
		'editSuccess' => 'Group updated successfully.',
		'delError' => 'Group deletion failed.', 
		'delSuccess' => 'Group deleted successfully.', 
		'saveExceptionsSuccess' => 'Exception successfully added to %s %s.', 
		'saveExceptionsError' => 'Exception addition failed.', 
		'protectedAdmin' => 'Protected from modifications.', 
		'cannotModifyDeleted' => 'Cannot modify a deleted admin.', 
		'hasAdminsAttached' => 'Cannot delete a group that has administrators attached.'
	]
];