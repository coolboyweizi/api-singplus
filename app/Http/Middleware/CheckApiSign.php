<?php

namespace SingPlus\Http\Middleware;

use Closure;
use SingPlus\Exceptions\AppException;

class CheckApiSign
{

  private $param = 'sign';

  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   */
  public function handle($request, Closure $next, $version = null)
  {
    if ($this->enable()) {
      if ($version == 'json') {
        if ( ! $this->verifyJson()) {
          throw new AppException('json signature not match');
        }
      } else {
        if ( ! $this->verify($request->input())) {
          throw new AppException('signature not match');
        }
      }
    }

    return $next($request);
  }

  private function verify(array $params) : bool
  {
    // filter array key, which not used for signature
    $params = array_where($params, function($key, $value) {
        return ! is_array($value);
    }); 

    if ( ! array_get($params, 'nonce')) {
      return false;
    }

    // try finding signature from $params
    if (array_has($params, $this->param)) {
        $signature = array_get($params, $this->param);
        unset($params[$this->param]); // remove the signature
    } else {
      return false;
    }
                                                                                                      
    if (empty($signature) || $signature != $this->sign($params)) {
        return false;
    }   
                                                                                                      
    return true;
  }

  private function verifyJson()
  {
    // todo
    return true;
  }

  private function sign(array $params) : string
  {
    $signStr = $this->morphArrayToString($params);

    // sign length equal 40
    return sha1($signStr, false);
  }

  /**
   * morph given value to string for signing
   *
   * @param array $data
   *
   * @return string               string prepared for signing
   */
  private function morphArrayToString(array $data)
  {
    ksort($data);
    $plain = '';
    foreach ($data as $k => $v) {
      $plain .= $k . '=' . $v . '&';
    }

    return $plain . config('admin.signature');
  }


  private function enable()
  {
    return ! app()->bound('middleware.request.sign.disable') ||
           app()->make('middleware.request.sign.disable') === false;
  }
}
