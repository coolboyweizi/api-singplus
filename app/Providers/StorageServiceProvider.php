<?php

namespace SingPlus\Providers;

use Aws\Common\RulesEndpointProvider;
use Aws\S3\S3Client;
use League\Flysystem\Filesystem;
use SingPlus\Support\Aws\AwsS3Adapter;
use Illuminate\Support\ServiceProvider;
use SingPlus\Exceptions\AppException;
use SingPlus\Support\Aws\Signatures\SignatureV2;

class StorageServiceProvider extends ServiceProvider
{
  /** 
   * Indicates if loading of the provider is deferred.
   *
   * @var bool
   */
  protected $defer = true;

  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot()
  {
    //
  }

  /**
   * Register any application services.
   *
   * @return void
   */
  public function register()
  {
    $this->app->singleton(
      \SingPlus\Contracts\Storages\Services\StorageService::class,
      \SingPlus\Domains\Storages\Services\StorageService::class
    );
    //$this->app->singleton(
    //  \SingPlus\Contracts\Storages\Services\StorageService::class,
    //  function ($app) {
    //    $config = $app['config']['storage.riakcs'];
    //    if (empty($config)) {
    //      throw new AppException('config storage first');
    //    }

    //    $riakConf = [
    //      'key'               => $config['key'],
    //      'secret'            => $config['secret'],
    //      'signature'         => $app->make(SignatureV2::class),
    //      'endpoint_provider' => new RulesEndpointProvider([
    //                                      'version' => 2,
    //                                      'endpoints' => [
    //                                        '*/*' => [
    //                                          'endpoint'  => $config['server'],
    //                                        ],
    //                                      ],
    //                              ]),
    //      'scheme'            => $config['scheme'],
    //      'command.params'    => [
    //        'PathStyle' => true,
    //      ],
    //    ];
    //    $client = S3Client::factory($riakConf);
    //    $adapter = new AwsS3Adapter($client, $config['bucket']);
    //    $fileSystem = new Filesystem($adapter, ['disable_asserts' => false]);
    //    return new \SingPlus\Domains\Storages\Services\StorageService($fileSystem, array_filter([
    //      'bucket'    => $config['bucket'],
    //      'base_url'  => $config['base_url'] ?: '',
    //    ]));
    //  }
    //);
  }

  /** 
   * Get the services provided by the provider.
   *
   * @return array
   */
  public function provides()
  {   
    return [
      \SingPlus\Contracts\Storages\Services\StorageService::class,
    ];  
  } 
}
