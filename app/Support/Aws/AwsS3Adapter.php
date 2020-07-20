<?php

namespace SingPlus\Support\Aws;

use League\Flysystem\AwsS3v2\AwsS3Adapter as AwsS3AdapterBase;

class AwsS3Adapter extends AwsS3AdapterBase
{
  public function setBucket(string $bucket)
  {
    $this->bucket = $bucket;
  }
}
