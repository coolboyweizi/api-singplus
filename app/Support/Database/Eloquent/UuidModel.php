<?php

namespace SingPlus\Support\Database\Eloquent;

use Illuminate\Database\Eloquent\Model;
use SingPlus\Support\Database\Eloquent\ModelInterface;

class UuidModel extends Model implements ModelInterface
{
    use UuidModelTrait;
    /** 
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

}
