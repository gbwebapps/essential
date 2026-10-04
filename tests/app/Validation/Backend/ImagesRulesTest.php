<?php declare(strict_types = 1);

namespace App\Validation\Backend;

use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

use App\Validation\Backend\ImagesRules;

class ImagesRulesTest extends CIUnitTestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();

        helper('settings');

        Services::reset();

        $this->testDir = WRITEPATH . 'tests/images_rules/';

        if ( ! is_dir($this->testDir)):
            mkdir($this->testDir, 0775, true);
        endif;
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testDir)):
            $files = array_diff(scandir($this->testDir), ['.', '..']);

            foreach ($files as $file):
                unlink($this->testDir . $file);
            endforeach;

            rmdir($this->testDir);
        endif;

        Services::reset();

        parent::tearDown();
    }

    public function testCheckImagesReturnsTrueWhenFilesAreEmpty(): void
    {
        $rules = new ImagesRules();

        $result = $rules->checkImages([]);

        $this->assertTrue($result);
    }

    public function testCheckImagesSkipsInvalidFile(): void
    {
        $file = $this->createMock(UploadedFile::class);

        $file->expects($this->once())->method('isValid')->willReturn(false);
        $file->expects($this->never())->method('getSizeByUnit');
        $file->expects($this->never())->method('getClientExtension');
        $file->expects($this->never())->method('getTempName');

        $rules = new ImagesRules();

        $result = $rules->checkImages([$file]);

        $this->assertTrue($result);
    }

    public function testCheckImagesAcceptsValidImage(): void
    {
        $source = $this->createImage('valid.png', 100, 100);

        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('getSizeByUnit')->with('kb')->willReturn(10);
        $file->method('getClientExtension')->willReturn('png');
        $file->method('getTempName')->willReturn($source);

        $rules = new ImagesRules();

        $result = $rules->checkImages(
            [$file],
            'size:1000,ext:png,width:500,height:500'
        );

        $this->assertTrue($result);
        $this->assertSame([], Services::validation()->getErrors());
    }

    public function testCheckImagesSetsErrorWhenFileExceedsMaximumSize(): void
    {
        $source = $this->createImage('size.png', 100, 100);

        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('getSizeByUnit')->with('kb')->willReturn(200);
        $file->method('getClientExtension')->willReturn('png');
        $file->method('getTempName')->willReturn($source);

        $rules = new ImagesRules();

        $result = $rules->checkImages(
            [$file],
            'size:100,ext:png,width:500,height:500'
        );

        $this->assertTrue($result);
        $this->assertNotSame('', Services::validation()->getError('images.0'));
    }

    public function testCheckImagesSetsErrorWhenExtensionIsNotAllowed(): void
    {
        $source = $this->createImage('extension.png', 100, 100);

        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('getSizeByUnit')->with('kb')->willReturn(10);
        $file->method('getClientExtension')->willReturn('png');
        $file->method('getTempName')->willReturn($source);

        $rules = new ImagesRules();

        $result = $rules->checkImages(
            [$file],
            'size:1000,ext:jpg|jpeg,width:500,height:500'
        );

        $this->assertTrue($result);
        $this->assertNotSame('', Services::validation()->getError('images.0'));
    }

    public function testCheckImagesSetsErrorWhenImageExceedsMaximumWidth(): void
    {
        $source = $this->createImage('width.png', 200, 100);

        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('getSizeByUnit')->with('kb')->willReturn(10);
        $file->method('getClientExtension')->willReturn('png');
        $file->method('getTempName')->willReturn($source);

        $rules = new ImagesRules();

        $result = $rules->checkImages(
            [$file],
            'size:1000,ext:png,width:100,height:500'
        );

        $this->assertTrue($result);
        $this->assertNotSame('', Services::validation()->getError('images.0'));
    }

    public function testCheckImagesSetsErrorWhenImageExceedsMaximumHeight(): void
    {
        $source = $this->createImage('height.png', 100, 200);

        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('getSizeByUnit')->with('kb')->willReturn(10);
        $file->method('getClientExtension')->willReturn('png');
        $file->method('getTempName')->willReturn($source);

        $rules = new ImagesRules();

        $result = $rules->checkImages(
            [$file],
            'size:1000,ext:png,width:500,height:100'
        );

        $this->assertTrue($result);
        $this->assertNotSame('', Services::validation()->getError('images.0'));
    }

    public function testCheckImagesIgnoresMalformedParameter(): void
    {
        $source = $this->createImage('malformed.png', 100, 100);

        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('getSizeByUnit')->with('kb')->willReturn(10);
        $file->method('getClientExtension')->willReturn('png');
        $file->method('getTempName')->willReturn($source);

        $rules = new ImagesRules();

        $result = $rules->checkImages(
            [$file],
            'invalidParameter,size:1000,ext:png,width:500,height:500'
        );

        $this->assertTrue($result);
        $this->assertSame([], Services::validation()->getErrors());
    }

    public function testCheckImagesIgnoresUnknownParameter(): void
    {
        $source = $this->createImage('unknown.png', 100, 100);

        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('getSizeByUnit')->with('kb')->willReturn(10);
        $file->method('getClientExtension')->willReturn('png');
        $file->method('getTempName')->willReturn($source);

        $rules = new ImagesRules();

        $result = $rules->checkImages(
            [$file],
            'unknown:123,size:1000,ext:png,width:500,height:500'
        );

        $this->assertTrue($result);
        $this->assertSame([], Services::validation()->getErrors());
    }

    public function testCheckImagesUsesFileArrayKeyForValidationError(): void
    {
        $source = $this->createImage('key.png', 100, 100);

        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('getSizeByUnit')->with('kb')->willReturn(200);
        $file->method('getClientExtension')->willReturn('png');
        $file->method('getTempName')->willReturn($source);

        $rules = new ImagesRules();

        $result = $rules->checkImages(
            [
                7 => $file
            ],
            'size:100,ext:png,width:500,height:500'
        );

        $this->assertTrue($result);
        $this->assertNotSame('', Services::validation()->getError('images.7'));
    }

    private function createImage(string $filename, int $width, int $height): string
    {
        $path = $this->testDir . $filename;

        $image = imagecreatetruecolor($width, $height);
        imagepng($image, $path);
        imagedestroy($image);

        return $path;
    }
}