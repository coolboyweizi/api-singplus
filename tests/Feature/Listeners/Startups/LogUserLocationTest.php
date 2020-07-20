<?php

namespace FeatureTest\SingPlus\Listeners\Feeds;

use Mockery;
use Cache;
use LogLocation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Stevebauman\Location\Position;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Events\Startups\CommonInfoFetched as CommonInfoFetchedEvent;

class LogUserLocationTest extends TestCase
{
  use MongodbClearTrait; 

  public function testSuccess_DriverIpInfo()
  {
    $position = new Position;
    $position->contryName = 'Tanzania';
    $position->countryCode = 'TZ';
    $position->regionCode = 'Simiyu';
    $position->regionName = 'Simiyu';
    $position->cityName = 'Bariadi';
    $position->zipCode = '12344';
    $position->driver = '\Stevebauman\Location\Drivers\IpInfoDriver';

    $mock = \Mockery::mock(\SingPlus\Support\Locations\Location::class);
    $mock->shouldReceive('get')
         ->once()
         ->with('169.225.184.21')
         ->andReturn($position);
    $this->app['location'] = $mock;

    LogLocation::shouldReceive('info')
               ->once()
               ->with(
                  Mockery::on(function (string $message) {
                    return $message == 'ip: 169.225.184.21, abbr: TZ, reportAbbr: ';
                  }),
                  Mockery::on(function (array $context) {
                    return array_get($context, 'location.countryCode') == 'TZ';
                  })
               );

    $event = new CommonInfoFetchedEvent('', (object) [
      'ip'  => '169.225.184.21', 
    ]);
    $res = $this->getListener()->handle($event);
  }

  private function getListener()
  {
    return $this->app->make(\SingPlus\Listeners\Startups\LogUserLocation::class);
  }
}
