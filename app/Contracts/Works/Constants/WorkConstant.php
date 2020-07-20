<?php

namespace SingPlus\Contracts\Works\Constants;

class WorkConstant
{
  const CHORUS_TYPE_START = 1;      // 发起合唱
  const CHORUS_TYPE_JOIN = 10;      // 参与合唱

  static $chorusTypes = [
    self::CHORUS_TYPE_START,
    self::CHORUS_TYPE_JOIN,
  ];
}
