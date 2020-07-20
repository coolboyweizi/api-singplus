<?php

namespace SingPlus\SMS;

use Illuminate\Support\Str;
use Illuminate\Support\Manager;
use SingPlus\SMS\Transport\InfobipTransport;

class TransportManager extends Manager
{
  /**
   * Get a transport driver instance
   */
  public function driver($transport = null)
  {
    $transport = $transport ?: $this->getDefaultDriver();
    
    if ( ! isset($this->drivers[$transport])) {
      $this->drivers[$transport] = $this->createDriver($transport);
    }

    return $this->drivers[$transport];
  }

  protected function createDriver($transport)
  {
    // fetch driver
    $config = $this->app->make('config')->get('sms');
    $transportConf = array_get($config, sprintf('transports.%s', $transport));
    if (empty($transportConf)) {
      throw new InvalidArgumentException("Transport [$transport] not supported.");
    }
    $driver = array_get($transportConf, 'driver');
    if (empty($driver)) {
      throw new InvalidArgumentException("Transport driver [$transport][$driver] not supported.");
    }

    // We'll check to see if a creator method exists for the given driver. If not we
    // will check for a custom driver creator, which allows developers to create
    // drivers using their own customized driver creator Closure to create it.
    if (isset($this->customCreators[$transport])) {
        return $this->callCustomCreator($transport);
    } else {
        $method = 'create'.Str::studly($driver).'Driver';

        if (method_exists($this, $method)) {
            $transportConf['transport'] = $transport;
            return $this->$method($transportConf);
        }
    }

    throw new InvalidArgumentException("Driver [$driver] not supported.");
  }

  /**
   * Create an infobip transport driver
   *
   * @param array $config       transport config
   *
   * @return \SingPlus\Support\SMS\Transport\Transport
   */
  protected function createInfobipDriver(array $config)
  {
    if (array_get($config, 'authtype') == 'apikey') {
      $authConf = new \infobip\api\configuration\ApiKeyAuthConfiguration(
                            array_get($config, 'apikey'));
    } else {
      $authConf = new \infobip\api\configuration\BasicAuthConfiguration(
                            array_get($config, 'username'),
                            array_get($config, 'password'));
    }

    return new InfobipTransport(
                $this->app, array_get($config, 'transport'), $authConf,
                array_get($config, 'notifyurl'));
  }

  public function getDefaultDriver()
  {
    return $this->app->make('config')->get('sms.default');
  }
}
