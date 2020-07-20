<?php

namespace SingPlus\Support\Aws\Signatures;

use Aws\Common\Credentials\CredentialsInterface;
use Guzzle\Http\Message\RequestInterface;
use Aws\Common\Enum\DateFormat;
use Aws\Common\Signature\AbstractSignature;

class SignatureV2 extends AbstractSignature
{
  public function signRequest(RequestInterface $request, CredentialsInterface $credentials)
  {
    // refresh the cached timestamp
    $timestamp = $this->getTimestamp(true);
    $request->setHeader('Accept-Encoding', 'identity');
    $request->setHeader('Date', gmdate(DateFormat::RFC1123, $timestamp));

    if ($token = $credentials->getSecurityToken()) {
      $this->addParameter($request, 'SecurityToken', $token);
    }

    // Get the path and ensure it's absolute
    $path = '/' . ltrim($request->getUrl(true)->normalizePath()->getPath(), '/');
    
    $sign = $request->getMethod() . "\n"
          . $request->getHeader('content-md5') . "\n"
          . $request->getHeader('content-type') . "\n"
          . $request->getHeader('date') . "\n";

    $headers = $request->getHeaders()->getAll();
    ksort($headers);
    foreach ($headers as $k => $v) {
      if (starts_with($k, 'x-amz-') || starts_with($k, 'x-emc-')) {
        $sign .= $k . ":" . (string) $v . "\n";
      }
    }

    $sign .= $path;

    // Add the string to sign to the request for debugging purposes
    $request->getParams()->set('aws.string_to_sign', $sign);

    $signature = base64_encode(
      hash_hmac(
        'sha1',
        $sign,
        $credentials->getSecretKey(),
        true
      )
    );

    //$this->addParameter($request, 'Signature', $signature);
    $request->removeHeader('Authorization');
    $request->setHeader('Authorization', 'AWS ' . $credentials->getAccessKeyId() . ":" . $signature);
  }

  /**
   * Add a parameter key and value to the request according to type
   *
   * @param RequestInterface $request The request
   * @param string           $key     The name of the parameter
   * @param string           $value   The value of the parameter
   */
  public function addParameter(RequestInterface $request, $key, $value)
  {
    if ($request->getMethod() == 'POST') {
      $request->setPostField($key, $value);
    } else {
      $request->getQuery()->set($key, $value);
    }
  }
}
