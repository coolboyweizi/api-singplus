<?php

namespace SingPlus\Support\Database\Eloquent;

use Ramsey\Uuid\Uuid;

/**
 * use this trait must set $incrementing
 * Example: public $incrementing = false;
 * the current model can not be set to do the $incrementing.
 */
trait UuidModelTrait
{

  /** 
   * The "booting" method of the model.
   *
   * @return void
   */
  protected static function boot()
  {   
    parent::boot();

    /** 
     * Attach to the 'creating' Model Event to provide a UUID
     * for the `id` field (provided by $model->getKeyName())
     */
    static::creating(function ($model) {
      $model->{$model->getKeyName()} = $model->generateNewId();
    }); 
  }   

  /** 
   * Get a new version 4 (random) UUID.
   *
   * @return string
   */
  public function generateNewId()
  {   
    return Uuid::uuid4()->getHex();
  }   

  public function getId() : string
  {
    return $this->{$this->getKeyName()};
  }
}
