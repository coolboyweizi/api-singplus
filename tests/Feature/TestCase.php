<?php

namespace FeatureTest\SingPlus;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
  use CreatesApplication;
  use VersionCheckTrait;
  use RequestSignCheckTrait;
  use AuthUserLastInfoTrait;
  use ApiTaskIdTrait;
  use NationOperationTrait;
  use CheckActivityTrait;

  protected function setUpTraits()
  {
    parent::setUpTraits();

    $uses = array_flip(class_uses_recursive(static::class));
    if (isset($uses[MongodbClearTrait::class])) {
      $this->enableMongodbClearForTest();
    }
  }

  /**
   * create an uploaded file from file on file system
   *
   * @param string $path        path of file to be uploaded (MUST exist)
   * @param string|null $name   file name. if not specified, basename of $path will be applied
   * @param string|null $mime   client mime/content-type of file
   */
  protected final function makeUploadFile(
    string $path,
    string $name = null,
    string $mime = null
  ) : UploadedFile {
    if (empty($name)) { 
      $name = pathinfo($path, PATHINFO_BASENAME);
    }

    return new UploadedFile($path, $name, $mime, filesize($path), null, true);
  }
}
