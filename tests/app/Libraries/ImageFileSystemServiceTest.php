<?php declare(strict_types = 1);

namespace App\Libraries;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;

class ImageFileSystemServiceTest extends CIUnitTestCase
{
	public function testRemoveSingleImageDeletesFiles()
    {
        $entity
            =
            'test_entity';

        $uuid
            =
            'test_uuid';

        $filename
            =
            'test.jpg';

        $basePath
            =
            rtrim(
                FCPATH,
                '/\\'
            );

        $basePath
            =
            sprintf(
                '%s/images/backend/%s/%s',
                $basePath,
                $entity,
                $uuid
            );

        foreach (
            ['large', 'medium', 'small']
            as
            $size
        ):
            $dirPath
                =
                sprintf(
                    '%s/%s',
                    $basePath,
                    $size
                );

            if (
                ! is_dir(
                    $dirPath
                )
            ):
                mkdir(
                    $dirPath,
                    0755,
                    true
                );
            endif;

            $targetFile
                =
                sprintf(
                    '%s/%s',
                    $dirPath,
                    $filename
                );

            file_put_contents(
                $targetFile,
                'dummy content'
            );
        endforeach;

        \App\Libraries\ImageFileSystemService::removeSingleImage(
            $entity,
            $uuid,
            $filename
        );

        foreach (
            ['large', 'medium', 'small']
            as
            $size
        ):
            $filePath
                =
                sprintf(
                    '%s/%s/%s',
                    $basePath,
                    $size,
                    $filename
                );

            $this
                ->assertFalse(
                    is_file(
                        $filePath
                    )
                );
        endforeach;

        \App\Libraries\ImageFileSystemService::removeAllImages(
            $entity,
            $uuid
        );
    }

    public function testRemoveAllImagesDeletesDirectoryTree()
    {
        $entity
            =
            'test_entity_all';

        $uuid
            =
            'test_uuid_all';

        $basePath
            =
            rtrim(
                FCPATH,
                '/\\'
            );

        $basePath
            =
            sprintf(
                '%s/images/backend/%s/%s',
                $basePath,
                $entity,
                $uuid
            );

        $subDir
            =
            sprintf(
                '%s/large',
                $basePath
            );

        if (
            ! is_dir(
                $subDir
            )
        ):
            mkdir(
                $subDir,
                0755,
                true
            );
        endif;

        $filePath
            =
            sprintf(
                '%s/image.jpg',
                $subDir
            );

        file_put_contents(
            $filePath,
            'content'
        );

        \App\Libraries\ImageFileSystemService::removeAllImages(
            $entity,
            $uuid
        );

        $this
            ->assertFalse(
                is_dir(
                    $basePath
                )
            );
    }

    public function testRemoveAllImagesDoesNothingWhenDirDoesNotExist()
    {
        $entity
            =
            'non_existent_entity';

        $uuid
            =
            'non_existent_uuid';

        $basePath
            =
            rtrim(
                FCPATH,
                '/\\'
            )
            .
            '/images/backend/'
            .
            $entity
            .
            '/'
            .
            $uuid;

        \App\Libraries\ImageFileSystemService::removeAllImages(
            $entity,
            $uuid
        );

        $this
            ->assertDirectoryDoesNotExist(
                $basePath
            );
    }
}
