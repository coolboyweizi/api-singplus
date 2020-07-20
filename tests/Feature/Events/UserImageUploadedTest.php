<?php

namespace FeatureTest\SingPlus\Events;

use FeatureTest\SingPlus\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use SingPlus\Events\UserImageUploaded as EventUserImageUploaded;

class UserImageUploadedTest extends TestCase
{
  public function testSuccess()
  {
    $mock = new MockHandler([
      new Response(200),
      new Response(500, [], 'wrong'),
      new RequestException('Error communicating with server', new Request('POST', 'http://tailor.sing.plus')),
    ]);
    $handler = HandlerStack::create($mock);
    $client = new \GuzzleHttp\Client(['handler' => $handler]);

    $this->app[\GuzzleHttp\Client::class] = $client;

    event(new EventUserImageUploaded('http://tailor.sing.plus'));
    event(new EventUserImageUploaded('http://tailor.sing.plus'));
    event(new EventUserImageUploaded('http://tailor.sing.plus'));
  }
}
