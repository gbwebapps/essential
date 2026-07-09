<?php declare(strict_types = 1); 

namespace Config\Backend;

use CodeIgniter\Config\BaseConfig;

class Upload extends BaseConfig
{
	public int $renameImages = 0;
	public int $overwriteImages = 0;
	public int $cropCenter = 1;
	public int $resizeMediumX = 960;
	public int $resizeMediumY = 540;
	public int $resizeSmallX = 96;
	public int $resizeSmallY = 54;
}