<?php

return [
  /*
   |--------------------------------------------------------------
   | AOP Debug Mode
   |--------------------------------------------------------------
   |
   | When AOP is in debug mode, then breakpoints in the original
   | source code. Also engine will refresh cache files if the
   | original files were
   | For production mode, no extra filemtime checks and better
   | integration with opcache
   |
   */
   'debug'  => env('APP_DEBUG', false),


  /*
   |--------------------------------------------------------------
   | Application root directory
   |--------------------------------------------------------------
   |
   | Aop will be applied only to the files in this directory,
   | change it to app_path() if needed
   |
   */
  'appDir'  => app_path(),

  /*
   |--------------------------------------------------------------
   | Aop cache directory
   |--------------------------------------------------------------
   |
   | AOP engine will put all transformed files and caches in
   | that directory
   |
   */
   'cacheDir' => app_path('Aop/cache'),


  /*
   |--------------------------------------------------------------------------
   | Cache file mode
   |--------------------------------------------------------------------------
   |
   | If configured then will be used as cache file mode for chmod
   */
  'cacheFileMode' => 0777 & ~umask(),

  /*
   |--------------------------------------------------------------------------
   | Controls miscellaneous features of AOP engine
   |--------------------------------------------------------------------------
   |
   | See \Go\Aop\Features enumeration for bit mask
   */
  'features' => 0,

  /*
   |--------------------------------------------------------------------------
   | White list of directories
   |--------------------------------------------------------------------------
   |
   | AOP will check this list to apply an AOP to selected directories only,
   | leave it empty if you want AOP to be applied to all files in the appDir
   */
  'includePaths' => [],


  /*
   |--------------------------------------------------------------------------
   | Black list of directories
   |--------------------------------------------------------------------------
   |
   | AOP will check this list to disable AOP for selected directories
   */
  'excludePaths' => [],

  /*
   |--------------------------------------------------------------------------
   | AOP container class
   |--------------------------------------------------------------------------
   |
   | This option can be useful for extension and fine-tuning of services
   */
  'containerClass' => \Go\Core\GoAspectContainer::class,
];
