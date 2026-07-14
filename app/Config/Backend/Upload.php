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
	public int $maxFileSize = 8000;
	public int $maxImageX = 4500;
	public int $maxImageY = 4001;
	public string $allowedExtensions = 'png|jpg|jpeg|webp';
}