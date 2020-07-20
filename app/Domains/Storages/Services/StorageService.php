<?php

namespace SingPlus\Domains\Storages\Services;

use Storage;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FileExistsException;
use Illuminate\Contracts\Filesystem\FileNotFoundException as ContractFileNotFoundException;
use SingPlus\Contracts\Storages\Services\StorageService as StorageServiceContract;
use SingPlus\Support\Helpers\Str;
use SingPlus\Support\Aws\S3PreSignedPost;
use SingPlus\Exceptions\AppException;
use SingPlus\Exceptions\Storages\StorageCommonException;
use SingPlus\Exceptions\Storages\StorageFileNotExistsException;
use SingPlus\Exceptions\Storages\StorageFileExistsException;
use SingPlus\Exceptions\Storages\StorageLocalFileNotExistsException;
use SingPlus\Exceptions\Storages\StoragePathIllegalException;

class StorageService implements StorageServiceContract
{
  const SCHEMA = '';

  /**
   * RiakCS Client
   */
  private $client;

  /**
   * default bucket name
   *
   * @var string
   */
  private $bucket;

  /**
   * base portal url
   *
   * @var string
   */
  private $baseUrl;

  /**
   * {@inheritdoc}
   */
  public function store(string $filePath, array $options = []) : string
  {
    if ( ! file_exists($filePath)) {
      throw new StorageLocalFileNotExistsException();
    }

    $key = $this->createFileKey($options);
    $mine = $this->guessMime($filePath, $options);
    $public = (bool) array_get($options, 'public', true);
    $config = [
      'visibility'  => $public ? 'public' : 'private',
      'mimetype'    => $mine,
    ];
    $resource = fopen($filePath, 'r');
    $success = Storage::disk('s3')->put($key, $resource, $config);
    is_resource($resource) && fclose($resource);
    if ($success) {
      return $key;
    }

    throw new StorageCommonException('store file failed');
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(string $key, array $options = []) : ?string
  {

    $key = ltrim($key, '/');
    $options = array_merge([
      'null_for_nonexistence' => true,
    ], $options);

    try {
      return Storage::disk('s3')->get($key);
    } catch (ContractFileNotFoundException $es) {
      if ($options['null_for_nonexistence']) {
        return null;
      } else {
        throw new StorageFileNotExistsException($key);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function toHttpUrl(?string $key, array $options = []) : ?string
  {
    if ( ! $key) {
      return null;
    }

    $key = ltrim($key, '/');
    $options = array_merge([
      'inspect' => false,
    ], $options);

    if ($options['inspect'] && ! $this->has($key)) {
      throw new StorageFileNotExistsException($key);
    }

    $url = Storage::disk('s3')->url($key);
    if (config('storage.cdn_host')) {
        $url = http_build_url($url, [
            'host'  => config('storage.cdn_host'),
        ]);
    }

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function remove(string $key, array $options = []) : bool
  {
    $key = ltrim($key, '/');
    $options = array_merge([
      'inspect' => false,
    ], $options);

    if ($options['inspect'] && ! $this->has($key)) {
      throw new StorageFileNotExistsException($key);
    }

    if ( ! Storage::disk('s3')->delete($key)) {
      throw new StorageCommonException('delete failed');
    }

    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function copy(string $source, array $options = [])
  {
    throw new AppException('function not implements');
  }


  /**
   * {@inheritdoc}
   */
  public function has(string $key) : bool
  {
    $key = ltrim($key, '/');
    return Storage::disk('s3')->exists($key);
  }

  /**
   * {@inheritdoc}
   */
  public function getS3PresignedPost(string $prefix, ?string $mimeType = null)
  {
    $key = $this->createFileKey([
      'prefix'  => $prefix
    ]);
    $options = [];
    $formInputs = [];
    if ($mimeType) {
      $options[]['Content-Type'] = $mimeType;
      $formInputs['Content-Type'] = $mimeType;
    }

    $s3Client = Storage::disk('s3')->getDriver()->getAdapter()->getClient();
    $bucket = config('filesystems.disks.s3.bucket');
    $postObject = new S3PreSignedPost($s3Client, $options);

    return (object) [
      'key'       => $key,
      'presinged' => $postObject->getForms($bucket, $key, $formInputs),
    ];
  }

  private function createFileKey(array $options = [])
  {
    $prefix = array_get($options, 'prefix', '');

    return implode('/', array_filter([
      trim($prefix, '/'), Str::uuid()
    ]));
  }

  private function guessMime(string $filePath, array $options = [])
  {
    if ($mime = array_get($options, 'mime')) {
      return $this->morphMime($filePath, $mime);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $filePath);
    finfo_close($finfo);

    return $this->morphMime($filePath, $mime);
  }

  private function morphMime(string $filePath, string $mime) : string
  {
    $mimeMapping = [
      'css' => 'text/css',
      'js'  => 'text/javascript',
    ];

    if ('text/plain' == $mime) {
      $ext = pathinfo($filePath, PATHINFO_EXTENSION);
      if (isset($mimeMapping[$ext])) {
        $mime = $mimeMapping[$ext];
      }
    }

    return $mime;
  }
}
