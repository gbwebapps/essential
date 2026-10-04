<?php declare(strict_types = 1);

namespace Config\Backend;

use CodeIgniter\Test\CIUnitTestCase;

use Config\Backend\Upload;

class UploadTest extends CIUnitTestCase
{
    public function testConfigurationCanBeInstantiated(): void
    {
        $config = new Upload();

        $this->assertInstanceOf(Upload::class, $config);

        $this->assertSame(0, $config->renameImages);
        $this->assertSame(0, $config->overwriteImages);

        $this->assertSame(960, $config->resizeMediumX);
        $this->assertSame(540, $config->resizeMediumY);
        $this->assertSame(96, $config->resizeSmallX);
        $this->assertSame(54, $config->resizeSmallY);

        $this->assertSame(4096, $config->maxFileSize);
        $this->assertSame(1920, $config->maxImageX);
        $this->assertSame(1080, $config->maxImageY);

        $this->assertSame('png|jpg|jpeg|webp', $config->allowedExtensions);
    }
}