<?php

namespace SingPlus\Domains\Works\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class WorkUploadTask extends MongodbModel
{
  const STATUS_NORMAL = 1;

  const NO_ACCOMPANIMENT_NO = 0;
  const NO_ACCOMPANIMENT_YES = 1;

  const IS_PRIVATE_NO = 0;
  const IS_PRIVATE_YES = 1;

  // constant for is_default_cover
  const DEFAULT_COVER_NO = 0;
  const DEFAULT_COVER_YES = 1;

  protected $collection = 'work_upload_tasks';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user_id',
    'music_id',
    'name',
    'duration',
    'cover',            // cover image uri
    'is_default_cover', // please to see self::DEFAULT_COVER_XXXX
    'slides',           // elements are user image uri
    'description',      // description for work, used for share default text
    'resource',         // if client upload work to s3 server, sing+ app server
    'no_accompaniment', // should pre-generate work storage key in s3
    'is_private',
    'chorus_type',      // @see \SingPlus\Contracts\Works\Constants\WorkConstant::CHORUS_TYPE_XXX
    'origin_work_id',   // exists only if chorus_type == CHORUS_TYPE_JOIN
    'status',
  ];

  public function noAccompaniment() : bool
  {
    return (isset($this->no_accompaniment) && $this->no_accompaniment == self::NO_ACCOMPANIMENT_YES) ? true : false;
  }

  public function isPrivate() : bool
  {
    return (isset($this->is_private) && $this->is_private == self::IS_PRIVATE_YES) ? true : false;
  }

  public function isDefaultCover() : bool
  {
    return (isset($this->is_default_cover) && $this->is_default_cover == self::DEFAULT_COVER_YES) ? true : false;
  }
}
