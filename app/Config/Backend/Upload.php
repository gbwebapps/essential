<?php declare(strict_types = 1); 

namespace Config\Backend;

use CodeIgniter\Config\BaseConfig;

class Upload extends BaseConfig
{
	public int $renameImages = 0;
	public int $overwriteImages = 0;
	public string $cropImage = 'center';
	public int $resizeMediumX = 960;
	public int $resizeMediumY = 540;
	public int $resizeSmallX = 96;
	public int $resizeSmallY = 54;
	public int $maxFileSize = 4096;
	public int $maxImageX = 1920;
	public int $maxImageY = 1080;
	public string $allowedExtensions = 'png|jpg|jpeg|webp';
}