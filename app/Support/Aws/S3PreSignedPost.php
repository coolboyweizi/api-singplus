<?php

namespace SingPlus\Support\Aws;

use Aws\S3\S3Client;
use Aws\S3\PostObjectV4;

class S3PreSignedPost
{
  /**
   * @var Aws\S3\S3Client
   */
  private $s3Client;

  /**
   * @var array
   */
  private $options;

  /**
   * @var string
   */
  private $expires = '+2 hours';

  public function __construct(
    S3Client $client,
    array $options = []
  ) {
    $this->s3Client = $client;

    $this->options = array_merge([
      ['acl' => 'public-read'],
    ], $options);
  }

  public function getForms(string $bucket, string $fileKey, array $formInputs = [])
  {
    $options = array_merge($this->options, [
      ['bucket' => $bucket],
      ['eq', '$key', $fileKey],
    ]);
    $formInputs = array_merge([
      'key' => $fileKey,
      'acl' => 'public-read',
    ], $formInputs);
    $postObject = new PostObjectV4(
      $this->s3Client,
      $bucket,
      $formInputs,
      $options,
      $this->expires
    );

    $formInputs = [];
    foreach ($postObject->getFormInputs() as $key => $value) {
      $formInputs[] = [$key, $value];
    }

    return (object) [
      'formAttributes'  => $postObject->getFormAttributes(),
      'formInputs'      => $formInputs,
    ];
  }
}
