<?php

namespace SingPlus\Support\Database\Eloquent;

use Jenssegers\Mongodb\Eloquent\Model;
use SingPlus\Support\Database\Eloquent\UuidModelTrait;
use SingPlus\Support\Database\Eloquent\LocaleTrait;
use SingPlus\Support\Database\Eloquent\ModelInterface;

class MongodbModel extends Model implements ModelInterface
{
  use UuidModelTrait;
  use LocaleTrait;

  protected $connection = 'mongodb';

  /**
   * The attributes that should be mutated to dates.
   *
   * @var array 
   */
  protected $dates = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  /**
   * mutate date, which in variable $this->dates, value format to 'Y-m-d H:i:s'
   */
  public function setAttribute($key, $value)
  {
    parent::setAttribute($key, $value);


    if (in_array($key, $this->dates) && $value) {
      $mutatorValue = $this->attributes[$key];
      $this->attributes[$key] = $mutatorValue->toDateTime()->format('Y-m-d H:i:s');
    }

    return $this;
  }

  /**
   * Override Trait: SoftDeletes runSoftDelete method, Specified deleted_at date format
   * Child class must defined this method and invoke it. eg:
   *
   * @phpdoc
   * protected function runSoftDelete()
   * {
   *    parent::runSoftDelete();
   * }
   */
  protected function runSoftDelete()
  {
    $query = $this->newQueryWithoutScopes()->where($this->getKeyName(), $this->getKey());
    $this->{$this->getDeletedAtColumn()} = $time = \Carbon\Carbon::now();
    $query->update([
      $this->getDeletedAtColumn() => $time->format('Y-m-d H:i:s'),
      $this->getUpdatedAtColumn() => $time->format('Y-m-d H:i:s')
    ]);
  }
}
