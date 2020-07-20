<?php

namespace SingPlus\Providers;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Validator;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot()
  {
    $this->extendValidator();

    Blade::directive('escapeJson', function ($expression) {
      return "<?php echo str_replace(['\\\\', '\"'], ['\\\\\\\\', '\\\"'], $expression); ?>";
    });
  }

  /**
   * Register any application services.
   *
   * @return void
   */
  public function register()
  {
    //
  }

  private function extendValidator()
  {
    Validator::extend('mobile', function ($attribute, $value, $parameters) {
      return preg_match('/^\d{5,12}$/', $value);
    });
    Validator::extend('password', function ($attribute, $value, $parameters) {
      // length of password should range [6, 16]
      $len = strlen($value);
      return $len >= 6 && $len <= 16;
    });
    Validator::extend('uuid', function ($attribute, $value, $parameters) {
      return empty($value) || Uuid::isValid($value) || strlen($value) == 32;
    });
    Validator::extend('countrycode', function ($attribute, $value, $parameters) {
      $pattern = '(?:\d{1,3})|' .      // normal country code
                 '(?:1\d{2,4})|' .    // American dependant country code
                 '(?:44\d{2,4})';    // UK dependant country code
      return preg_match('/^(?:' . $pattern . ')$/', $value);
    });
    Validator::extend('workmimes', function ($attribute, $value, $parameters) {
      $validMimeTypes = [
        'audio/mp4',
        'audio/mpeg',
        'video/mp4',
        'audio/aac',
      ];

      return in_array($value, $validMimeTypes);
    });
    Validator::extend('appchannel', function ($attribute, $value, $parameters) {
      return array_key_exists($value, config('tudc.channels'));
    });
  }
}
