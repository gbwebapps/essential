<?php declare(strict_types = 1);

namespace App\Libraries\Backend;

use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;

use App\Libraries\Backend\UploadClass;
use Tests\Support\Libraries\MocksSettings;

class UploadClassTest extends CIUnitTestCase
{
    use MocksSettings;

    private string $testDir;
    private string $entity = 'phpunit-upload';
    private string $uuid = 'test-uuid';

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSettings([
            'Backend\Upload' => [
                'renameImages' => true,
                'overwriteImages' => true,
                'resizeMediumX' => 100,
                'resizeMediumY' => 100,
                'resizeSmallX' => 50,
                'resizeSmallY' => 50
            ]
        ]);

        $this->testDir = WRITEPATH . 'tests/upload_class/';

        if ( ! is_dir($this->testDir)):
            mkdir($this->testDir, 0775, true);
        endif;
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->testDir);

        $uploadDir = rtrim(FCPATH, '/\\') . '/images/backend/' . $this->entity;

        $this->deleteDirectory($uploadDir);

        $this->resetSettingsMocks();

        parent::tearDown();
    }

    public function testDoUploadReturnsFalseWhenFileIsNotUploadedFile(): void
    {
        $upload = new UploadClass();

        $result = $upload->doUpload(['invalid-file'], $this->entity, $this->uuid);

        $this->assertFalse($result);
    }

    public function testDoUploadReturnsFalseWhenUploadedFileIsNotValid(): void
    {
        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(false);

        $upload = new UploadClass();

        $result = $upload->doUpload([$file], $this->entity, $this->uuid);

        $this->assertFalse($result);
    }

    public function testDoUploadReturnsFalseWhenUploadedFileHasMoved(): void
    {
        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('hasMoved')->willReturn(true);

        $upload = new UploadClass();

        $result = $upload->doUpload([$file], $this->entity, $this->uuid);

        $this->assertFalse($result);
    }

    public function testDoUploadReturnsFalseWhenFileIsNotImage(): void
    {
        $tmpFile = $this->testDir . 'invalid.txt';

        file_put_contents($tmpFile, 'not an image');

        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('hasMoved')->willReturn(false);
        $file->method('getTempName')->willReturn($tmpFile);

        $upload = new UploadClass();

        $result = $upload->doUpload([$file], $this->entity, $this->uuid);

        $this->assertFalse($result);
    }

    public function testDoUploadReturnsUploadedFilenameOnSuccess(): void
    {
        $tmpFile = $this->testDir . 'source.png';

        $image = imagecreatetruecolor(200, 100);
        imagepng($image, $tmpFile);
        imagedestroy($image);

        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('hasMoved')->willReturn(false);
        $file->method('getTempName')->willReturn($tmpFile);
		$file->expects($this->never())->method('getClientExtension');
        $file->method('getClientName')->willReturn('test image.png');
        $file->method('getRandomName')->willReturn('random-image.png');

        $file->expects($this->once())->method('move');

        $upload = $this->getMockBuilder(UploadClass::class)
            ->onlyMethods(['cropImage'])
            ->getMock();

        $upload->expects($this->exactly(2))->method('cropImage')->willReturn(true);

        $result = $upload->doUpload([$file], $this->entity, $this->uuid);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame('random-image.png', $result[0]);
    }

    public function testDoUploadReturnsFalseWhenMoveThrowsException(): void
    {
        $tmpFile = $this->testDir . 'source.png';

        $image = imagecreatetruecolor(200, 100);
        imagepng($image, $tmpFile);
        imagedestroy($image);

        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('hasMoved')->willReturn(false);
        $file->method('getTempName')->willReturn($tmpFile);
        $file->method('getClientExtension')->willReturn('png');
        $file->method('getClientName')->willReturn('test.png');
        $file->method('getRandomName')->willReturn('random-image.png');

        $file->method('move')->willThrowException(new \RuntimeException('Move failed'));

        $upload = new UploadClass();

        $result = $upload->doUpload([$file], $this->entity, $this->uuid);

        $this->assertFalse($result);
    }

    public function testCropImageReturnsFalseWhenSourceDoesNotExist(): void
    {
        $upload = $this->getUploadClassWithPublicCropImage();

        $result = $upload->cropImagePublic(
            $this->testDir . 'missing.jpg',
            $this->testDir . 'output.jpg',
            100,
            100
        );

        $this->assertFalse($result);
    }

    public function testCropImageReturnsFalseWhenSourceIsNotImage(): void
    {
        $source = $this->testDir . 'invalid.txt';

        file_put_contents($source, 'not an image');

        $upload = $this->getUploadClassWithPublicCropImage();

        $result = $upload->cropImagePublic(
            $source,
            $this->testDir . 'output.jpg',
            100,
            100
        );

        $this->assertFalse($result);
    }

    public function testCropImageCropsLandscapeJpeg(): void
    {
        $source = $this->testDir . 'landscape.jpg';
        $destination = $this->testDir . 'landscape-output.jpg';

        $image = imagecreatetruecolor(200, 100);
        imagejpeg($image, $source);
        imagedestroy($image);

        $upload = $this->getUploadClassWithPublicCropImage();

        $result = $upload->cropImagePublic($source, $destination, 100, 100);

        $this->assertTrue($result);
        $this->assertFileExists($destination);

        $imageInfo = getimagesize($destination);

        $this->assertSame(100, $imageInfo[0]);
        $this->assertSame(100, $imageInfo[1]);
    }

    public function testCropImageCropsPortraitPng(): void
    {
        $source = $this->testDir . 'portrait.png';
        $destination = $this->testDir . 'portrait-output.png';

        $image = imagecreatetruecolor(100, 200);
        imagepng($image, $source);
        imagedestroy($image);

        $upload = $this->getUploadClassWithPublicCropImage();

        $result = $upload->cropImagePublic($source, $destination, 100, 100);

        $this->assertTrue($result);
        $this->assertFileExists($destination);

        $imageInfo = getimagesize($destination);

        $this->assertSame(100, $imageInfo[0]);
        $this->assertSame(100, $imageInfo[1]);
    }

    private function getUploadClassWithPublicCropImage(): UploadClass
    {
        return new class extends UploadClass
        {
            public function cropImagePublic(string $srcPath, string $destPath, int $targetX, int $targetY): bool
            {
                return $this->cropImage($srcPath, $destPath, $targetX, $targetY);
            }
        };
    }

    private function deleteDirectory(string $directory): void
    {
        if ( ! is_dir($directory)):
            return;
        endif;

        $files = array_diff(scandir($directory), ['.', '..']);

        foreach ($files as $file):
            $path = $directory . DIRECTORY_SEPARATOR . $file;

            if (is_dir($path)):
                $this->deleteDirectory($path);
            else:
                unlink($path);
            endif;
        endforeach;

        rmdir($directory);
    }

    public function testDoUploadUsesRandomNameWhenRenameImagesIsTrue(): void
    {
        $tmpFile = $this->testDir . 'source.png';

        $image = imagecreatetruecolor(200, 100);
        imagepng($image, $tmpFile);
        imagedestroy($image);

        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('hasMoved')->willReturn(false);
        $file->method('getTempName')->willReturn($tmpFile);
        $file->method('getClientExtension')->willReturn('png');
        $file->method('getClientName')->willReturn('nome originale.png');
        $file->method('getRandomName')->willReturn('random-name.png');

        $file->expects($this->once())
            ->method('move')
            ->with(
                $this->stringContains('/large'),
                'random-name.png',
                true
            );

        $upload = $this->getMockBuilder(UploadClass::class)
            ->onlyMethods(['cropImage'])
            ->getMock();

        $property = new \ReflectionProperty(UploadClass::class, 'config');
        $property->setAccessible(true);
        $property->setValue($upload, (object) [
            'renameImages' => true,
            'overwriteImages' => true,
            'resizeMediumX' => 100,
            'resizeMediumY' => 100,
            'resizeSmallX' => 50,
            'resizeSmallY' => 50
        ]);

        $upload->method('cropImage')->willReturn(true);

        $result = $upload->doUpload([$file], $this->entity, $this->uuid);

        $this->assertSame(['random-name.png'], $result);
    }

    public function testDoUploadSanitizesOriginalNameWhenRenameImagesIsFalse(): void
    {
        $tmpFile = $this->testDir . 'source.png';

        $image = imagecreatetruecolor(200, 100);
        imagepng($image, $tmpFile);
        imagedestroy($image);

        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('hasMoved')->willReturn(false);
        $file->method('getTempName')->willReturn($tmpFile);
        $file->method('getClientExtension')->willReturn('png');
        $file->method('getClientName')->willReturn('Nome   File @# prova.png');

        $file->expects($this->once())
            ->method('move')
            ->with(
                $this->stringContains('/large'),
                'Nome-File-prova.png',
                true
            );

        $upload = $this->getMockBuilder(UploadClass::class)
            ->onlyMethods(['cropImage'])
            ->getMock();

        $property = new \ReflectionProperty(UploadClass::class, 'config');
        $property->setAccessible(true);
        $property->setValue($upload, (object) [
            'renameImages' => false,
            'overwriteImages' => true,
            'resizeMediumX' => 100,
            'resizeMediumY' => 100,
            'resizeSmallX' => 50,
            'resizeSmallY' => 50
        ]);

        $upload->method('cropImage')->willReturn(true);

        $result = $upload->doUpload([$file], $this->entity, $this->uuid);

        $this->assertSame(['Nome-File-prova.png'], $result);
    }

    public function testDoUploadAddsProgressiveSuffixWhenDestinationAlreadyExists(): void
    {
        $tmpFile = $this->testDir . 'source.png';

        $image = imagecreatetruecolor(200, 100);
        imagepng($image, $tmpFile);
        imagedestroy($image);

        $basePath = rtrim(FCPATH, '/\\') . '/images/backend/' . $this->entity . '/' . $this->uuid . '/large';

        if ( ! is_dir($basePath)):
            mkdir($basePath, 0775, true);
        endif;

        file_put_contents($basePath . '/test.png', 'existing');

        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('hasMoved')->willReturn(false);
        $file->method('getTempName')->willReturn($tmpFile);
        $file->method('getClientExtension')->willReturn('png');
        $file->method('getClientName')->willReturn('test.png');

        $file->expects($this->once())
            ->method('move')
            ->with(
                $basePath,
                'test(1).png',
                true
            );

        $upload = $this->getMockBuilder(UploadClass::class)
            ->onlyMethods(['cropImage'])
            ->getMock();

        $property = new \ReflectionProperty(UploadClass::class, 'config');
        $property->setAccessible(true);
        $property->setValue($upload, (object) [
            'renameImages' => false,
            'overwriteImages' => false,
            'resizeMediumX' => 100,
            'resizeMediumY' => 100,
            'resizeSmallX' => 50,
            'resizeSmallY' => 50
        ]);

        $upload->method('cropImage')->willReturn(true);

        $result = $upload->doUpload([$file], $this->entity, $this->uuid);

        $this->assertSame(['test(1).png'], $result);
    }

    public function testCropImageCropsGif(): void
    {
        $source = $this->testDir . 'source.gif';
        $destination = $this->testDir . 'output.gif';

        $image = imagecreatetruecolor(200, 100);
        imagegif($image, $source);
        imagedestroy($image);

        $upload = $this->getUploadClassWithPublicCropImage();

        $result = $upload->cropImagePublic($source, $destination, 100, 100);

        $this->assertTrue($result);
        $this->assertFileExists($destination);

        $imageInfo = getimagesize($destination);

        $this->assertSame(100, $imageInfo[0]);
        $this->assertSame(100, $imageInfo[1]);
    }

    public function testCropImageCropsWebp(): void
    {
        if ( ! function_exists('imagewebp') || ! function_exists('imagecreatefromwebp')):
            $this->markTestSkipped('WEBP non supportato dalla versione GD installata.');
        endif;

        $source = $this->testDir . 'source.webp';
        $destination = $this->testDir . 'output.webp';

        $image = imagecreatetruecolor(200, 100);
        imagewebp($image, $source);
        imagedestroy($image);

        $upload = $this->getUploadClassWithPublicCropImage();

        $result = $upload->cropImagePublic($source, $destination, 100, 100);

        $this->assertTrue($result);
        $this->assertFileExists($destination);

        $imageInfo = getimagesize($destination);

        $this->assertSame(100, $imageInfo[0]);
        $this->assertSame(100, $imageInfo[1]);
    }

    public function testCropImageCropsBmp(): void
    {
        if ( ! function_exists('imagebmp') || ! function_exists('imagecreatefrombmp')):
            $this->markTestSkipped('BMP non supportato dalla versione GD installata.');
        endif;

        $source = $this->testDir . 'source.bmp';
        $destination = $this->testDir . 'output.bmp';

        $image = imagecreatetruecolor(200, 100);
        imagebmp($image, $source);
        imagedestroy($image);

        $upload = $this->getUploadClassWithPublicCropImage();

        $result = $upload->cropImagePublic($source, $destination, 100, 100);

        $this->assertTrue($result);
        $this->assertFileExists($destination);

        $imageInfo = getimagesize($destination);

        $this->assertSame(100, $imageInfo[0]);
        $this->assertSame(100, $imageInfo[1]);
    }

    public function testCropImageCropsAvif(): void
    {
        if ( ! function_exists('imageavif') || ! function_exists('imagecreatefromavif')):
            $this->markTestSkipped('AVIF non supportato dalla versione GD installata.');
        endif;

        $source = $this->testDir . 'source.avif';
        $destination = $this->testDir . 'output.avif';

        $image = imagecreatetruecolor(200, 100);
        imageavif($image, $source);
        imagedestroy($image);

        $upload = $this->getUploadClassWithPublicCropImage();

        $result = $upload->cropImagePublic($source, $destination, 100, 100);

        $this->assertTrue($result);
        $this->assertFileExists($destination);

        $imageInfo = getimagesize($destination);

        $this->assertSame(100, $imageInfo[0]);
        $this->assertSame(100, $imageInfo[1]);
    }

    public function testCropImageReturnsFalseForUnsupportedImageType(): void
    {
        if ( ! function_exists('imagewbmp')):
            $this->markTestSkipped('WBMP non supportato dalla versione GD installata.');
        endif;

        $source = $this->testDir . 'source.wbmp';

        $image = imagecreatetruecolor(100, 100);
        imagewbmp($image, $source);
        imagedestroy($image);

        $upload = $this->getUploadClassWithPublicCropImage();

        $result = $upload->cropImagePublic(
            $source,
            $this->testDir . 'output.wbmp',
            50,
            50
        );

        $this->assertFalse($result);
    }

    public function testCropImageReturnsFalseWhenImageCreationFails(): void
    {
        if ( ! function_exists('imagecreatefrombmp')):
            $this->markTestSkipped('BMP non supportato dalla versione GD installata.');
        endif;

        $source = $this->testDir . 'corrupted.bmp';

        $header = "BM";
        $header .= pack('V', 54);
        $header .= pack('V', 0);
        $header .= pack('V', 54);
        $header .= pack('V', 40);
        $header .= pack('V', 100);
        $header .= pack('V', 100);
        $header .= pack('v', 1);
        $header .= pack('v', 24);
        $header .= pack('V', 0);
        $header .= pack('V', 30000);
        $header .= pack('V', 0);
        $header .= pack('V', 0);
        $header .= pack('V', 0);
        $header .= pack('V', 0);

        file_put_contents($source, $header);

        $upload = $this->getUploadClassWithPublicCropImage();

        set_error_handler(function() {
            return true;
        });

        try {
            $result = $upload->cropImagePublic(
                $source,
                $this->testDir . 'output.bmp',
                100,
                100
            );
        } finally {
            restore_error_handler();
        }

        $this->assertFalse($result);
    }
}
