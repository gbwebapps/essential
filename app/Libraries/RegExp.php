<?php declare(strict_types = 1); 

namespace App\Libraries; 

class RegExp 
{
	public function validateUUID(string $uuid): bool
	{
		return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
	}

	public function validatePassword(string $password): bool
	{
	    return (bool) preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password);
	}
}