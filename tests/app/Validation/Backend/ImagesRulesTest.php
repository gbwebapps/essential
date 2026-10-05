<?php declare(strict_types = 1);

namespace App\Validation\Backend;

use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use Tests\Support\Libraries\MocksSettings;

use App\Validation\Backend\ImagesRules;

class ImagesRulesTest extends CIUnitTestCase
{
    use MocksSettings;

    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();

        helper('settings');

        Services::reset();

        $this->mockSettings([
            'Backend\Upload' => [
                'maxFileSize' => 100,
                'maxImageX' => 500,
                'maxImageY' => 500,
                'allowedExtensions' => 'png|jpg|jpeg|webp',
            ],
        ]);

        $this->testDir = WRITEPATH . 'tests/images_rules/' . bin2hex(random_bytes(8)) . '/';

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
        $this->resetSettingsMocks();

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
        $file->expects($this->once())->method('getError')->willReturn(UPLOAD_ERR_NO_FILE);
        $file->expects($this->never())->method('getSizeByUnit');
        $file->expects($this->never())->method('guessExtension');
        $file->expects($this->never())->method('getTempName');

        $rules = new ImagesRules();

        $result = $rules->checkImages([$file]);

        $this->assertTrue($result);
        $this->assertSame([], Services::validation()->getErrors());
    }

    public function testCheckImagesAcceptsValidImage(): void
    {
        $source = $this->createImage('valid.png', 100, 100);

        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('getSizeByUnit')->with('kb')->willReturn(10);
        $file->method('guessExtension')->willReturn('png');
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
        $file->method('guessExtension')->willReturn('png');
        $file->method('getTempName')->willReturn($source);

        $rules = new ImagesRules();

        $result = $rules->checkImages(
            [$file],
            'size:100,ext:png,width:500,height:500'
        );

        $this->assertTrue($result);
        $this->assertSame(
            lang('backend/upload.maxSize', [100]),
            Services::validation()->getError('images.0')
        );
    }

    public function testCheckImagesSetsErrorWhenExtensionIsNotAllowed(): void
    {
        $source = $this->createImage('extension.png', 100, 100);

        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('getSizeByUnit')->with('kb')->willReturn(10);
        $file->method('guessExtension')->willReturn('png');
        $file->method('getTempName')->willReturn($source);

        $rules = new ImagesRules();

        $result = $rules->checkImages(
            [$file],
            'size:1000,ext:jpg|jpeg,width:500,height:500'
        );

        $this->assertTrue($result);
        $this->assertSame(
            lang('backend/upload.extIn', ['jpg, jpeg']),
            Services::validation()->getError('images.0')
        );
    }

    public function testCheckImagesSetsErrorWhenImageExceedsMaximumWidth(): void
    {
        $source = $this->createImage('width.png', 200, 100);

        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('getSizeByUnit')->with('kb')->willReturn(10);
        $file->method('guessExtension')->willReturn('png');
        $file->method('getTempName')->willReturn($source);

        $rules = new ImagesRules();

        $result = $rules->checkImages(
            [$file],
            'size:1000,ext:png,width:100,height:500'
        );

        $this->assertTrue($result);
        $this->assertSame(
            lang('backend/upload.maxWidth', [100]),
            Services::validation()->getError('images.0')
        );
    }

    public function testCheckImagesSetsErrorWhenImageExceedsMaximumHeight(): void
    {
        $source = $this->createImage('height.png', 100, 200);

        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('getSizeByUnit')->with('kb')->willReturn(10);
        $file->method('guessExtension')->willReturn('png');
        $file->method('getTempName')->willReturn($source);

        $rules = new ImagesRules();

        $result = $rules->checkImages(
            [$file],
            'size:1000,ext:png,width:500,height:100'
        );

        $this->assertTrue($result);
        $this->assertSame(
            lang('backend/upload.maxHeight', [100]),
            Services::validation()->getError('images.0')
        );
    }

    public function testCheckImagesIgnoresMalformedParameter(): void
    {
        $source = $this->createImage('malformed.png', 100, 100);

        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('getSizeByUnit')->with('kb')->willReturn(10);
        $file->method('guessExtension')->willReturn('png');
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
        $file->method('guessExtension')->willReturn('png');
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
        $file->method('guessExtension')->willReturn('png');
        $file->method('getTempName')->willReturn($source);

        $rules = new ImagesRules();

        $result = $rules->checkImages(
            [
                7 => $file
            ],
            'size:100,ext:png,width:500,height:500'
        );

        $this->assertTrue($result);
        $this->assertSame(
            lang('backend/upload.maxSize', [100]),
            Services::validation()->getError('images.7')
        );
    }

    public function testCheckImagesReportsUploadErrorsOtherThanMissingFile(): void
    {
        $file = $this->createMock(UploadedFile::class);

        $file->expects($this->once())->method('isValid')->willReturn(false);
        $file->expects($this->once())->method('getError')->willReturn(UPLOAD_ERR_PARTIAL);
        $file->expects($this->once())->method('getErrorString')->willReturn('Partial upload');
        $file->expects($this->never())->method('getSizeByUnit');
        $file->expects($this->never())->method('guessExtension');
        $file->expects($this->never())->method('getTempName');

        $result = (new ImagesRules())->checkImages([$file]);

        $this->assertTrue($result);
        $this->assertSame('Partial upload', Services::validation()->getError('images.0'));
    }

    public function testCheckImagesRejectsContentThatIsNotAnImage(): void
    {
        $source = $this->createTextFile('fake.png', 'not an image');
        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('getSizeByUnit')->with('kb')->willReturn(1);
        $file->method('getTempName')->willReturn($source);
        $file->expects($this->never())->method('guessExtension');

        $result = (new ImagesRules())->checkImages(
            [$file],
            'size:1000,ext:png,width:500,height:500'
        );

        $this->assertTrue($result);
        $this->assertSame(
            lang('Validation.is_image', ['images.0']),
            Services::validation()->getError('images.0')
        );
    }

    public function testCheckImagesUsesTrustedExtensionInsteadOfClientExtension(): void
    {
        $source = $this->createImage('trusted-extension.png', 100, 100);
        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('getSizeByUnit')->with('kb')->willReturn(10);
        $file->method('getTempName')->willReturn($source);
        $file->method('guessExtension')->willReturn('jpg');
        $file->expects($this->never())->method('getClientExtension');

        $result = (new ImagesRules())->checkImages(
            [$file],
            'size:1000,ext:png,width:500,height:500'
        );

        $this->assertTrue($result);
        $this->assertSame(
            lang('backend/upload.extIn', ['png']),
            Services::validation()->getError('images.0')
        );
    }

    public function testCheckImagesUsesGlobalUploadSettingsWhenParametersAreMissing(): void
    {
        $source = $this->createImage('global-settings.png', 100, 100);
        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('getSizeByUnit')->with('kb')->willReturn(200);
        $file->expects($this->never())->method('getTempName');
        $file->expects($this->never())->method('guessExtension');

        $result = (new ImagesRules())->checkImages([$file]);

        $this->assertTrue($result);
        $this->assertSame(
            lang('backend/upload.maxSize', [100]),
            Services::validation()->getError('images.0')
        );
    }

    public function testCheckImagesTreatsZeroDimensionsAsDisabledLimits(): void
    {
        $source = $this->createImage('dimensions-disabled.png', 600, 600);
        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('getSizeByUnit')->with('kb')->willReturn(10);
        $file->method('getTempName')->willReturn($source);
        $file->method('guessExtension')->willReturn('png');

        $result = (new ImagesRules())->checkImages(
            [$file],
            'size:1000,ext:png,width:0,height:0'
        );

        $this->assertTrue($result);
        $this->assertSame([], Services::validation()->getErrors());
    }

    public function testCheckImagesMakesValidationServiceFailForInvalidImage(): void
    {
        $source = $this->createImage('validation-service.png', 200, 100);
        $file = $this->createMock(UploadedFile::class);

        $file->method('isValid')->willReturn(true);
        $file->method('getSizeByUnit')->with('kb')->willReturn(10);
        $file->method('getTempName')->willReturn($source);
        $file->method('guessExtension')->willReturn('png');

        $validation = Services::validation();
        $validation->setRule(
            'images',
            'Images',
            'checkImages[size:1000,ext:png,width:100,height:500]'
        );

        $this->assertFalse($validation->run(['images' => [$file]]));
        $this->assertSame(
            lang('backend/upload.maxWidth', [100]),
            $validation->getError('images.0')
        );
    }

    private function createImage(string $filename, int $width, int $height): string
    {
        $path = $this->testDir . $filename;

        $image = imagecreatetruecolor($width, $height);
        imagepng($image, $path);
        imagedestroy($image);

        return $path;
    }

    private function createTextFile(string $filename, string $contents): string
    {
        $path = $this->testDir . $filename;

        file_put_contents($path, $contents);

        return $path;
    }
}
