<?php

namespace SingPlus\Support\Helpers;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str as IlluminateStr;

class Str
{

  /** 
   * generate random text
   *
   * @param int $length     length of the generated text
   * @param string $pool    character pool
   *
   * @return string  generated random text
   */
  public static function quickRandom($length = 6, $pool = null)
  {   
    if (empty($pool)) {
      return IlluminateStr::quickRandom($length);
    }   

    $repeats = ceil($length / strlen($pool));
    return substr(str_shuffle(str_repeat($pool, $repeats)), 0, $length);
  }

  /**
   * Generate uuid version 4 (random) uuid
   */
  public static function uuid()
  {
    return Uuid::uuid4()->getHex();
  }

  /**
   * generate nick name
   *
   * @param int $len
   *
   * @return string generated nickname
   * */
  public static function generateNickName(int $len = 9):string
  {
      $result = "Sing_";
      $a = "abcdefghigklmnopqrstuvxwyzABCDEFGHIGKLMNOPQRSTUVXWYZ0123456789";
      $length = strlen($a);
      for ($i = 0; $i < $len; $i++)
      {
        $result .= $a{mt_rand(0, $length-1)};
      }
      return $result;
  }

  /**
   * generate uniqStr
   *
   * @param int $len
   *
   * @return string generated uniqStr
   * */
  public static function randUniqStr(int $len = 9):string
  {
      $in = time();
      $to_num = false;
      $passKey = mt_rand(0, time());
      $pad_up = $len;
      $index = "abcdefghigklmnopqrstuvxwyzABCDEFGHIGKLMNOPQRSTUVXWYZ0123456789";
      if ($passKey !== null) {
          for ($n = 0; $n<strlen($index); $n++) {
              $i[] = substr( $index,$n ,1);
          }

          $passhash = hash('sha256',$passKey);
          $passhash = (strlen($passhash) < strlen($index))
              ? hash('sha512',$passKey)
              : $passhash;

          for ($n=0; $n < strlen($index); $n++) {
              $p[] =  substr($passhash, $n ,1);
          }

          array_multisort($p,  SORT_DESC, $i);
          $index = implode($i);
      }

      $base  = strlen($index);

      if ($to_num) {
          $in  = strrev($in);
          $out = 0;
          $len = strlen($in) - 1;
          for ($t = 0; $t <= $len; $t++) {
              $bcpow = bcpow($base, $len - $t);
              $out   = $out + strpos($index, substr($in, $t, 1)) * $bcpow;
          }

          if (is_numeric($pad_up)) {
              $pad_up--;
              if ($pad_up > 0) {
                  $out -= pow($base, $pad_up);
              }
          }
          $out = sprintf('%F', $out);
          $out = substr($out, 0, strpos($out, '.'));
      } else {
          // Digital number  -->>  alphabet letter code
          if (is_numeric($pad_up)) {
              $pad_up--;
              if ($pad_up > 0) {
                  $in += pow($base, $pad_up);
              }
          }

          $out = "";
          for ($t = floor(log($in, $base)); $t >= 0; $t--) {
              $bcp = bcpow($base, $t);
              $a   = floor($in / $bcp) % $base;
              $out = $out . substr($index, $a, 1);
              $in  = $in - ($a * $bcp);
          }
          $out = strrev($out); // reverse
      }

      return $out;
  }

}
