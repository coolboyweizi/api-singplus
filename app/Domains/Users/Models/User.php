<?php

namespace SingPlus\Domains\Users\Models;

use Illuminate\Notifications\Notifiable;
use Jenssegers\Mongodb\Auth\User as Authenticatable;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;
use SingPlus\Contracts\Users\Models\User as UserContract;
use SingPlus\Support\Database\Eloquent\UuidModelTrait;

class User extends Authenticatable implements UserContract
{
    use Notifiable;
    use UuidModelTrait;
    use SoftDeletes;

    const DEFAULT_COUNTRY_CODE = '254';       // kenya

    protected $connection = 'mongodb';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'mobile',
        'password',
        'country_code',
        'push_alias',
        'boomsing_push_alias',
        'source',         // value please to see \SingPlus\Contracts\User\Constants\User::SOURCE_XX
        'boomcoin_country_code'    // string country_code from boomcoin api
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

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
     * This method must be added with SoftDeletes Trait
     */
    protected function runSoftDelete()
    {
      parent::runSoftDelete();
    }

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

    //====================================
    //    implements contract method
    //====================================
    public function getMobile() : ?string
    {
      return $this->mobile;
    }

    public function getCountryCode() : ?string
    {
      return $this->country_code;
    }
}
