@inject('mobileHelper', 'SingPlus\Support\Helpers\Mobile')
{
  "code": {!! $code !!},
  "data": {
@if ($data->profile)
  @if ($data->isSelf)
    "isPasswordSet": {!! $data->user->isPasswordSet ? "true" : "false" !!},
  @endif
    "userId": "{!! $data->profile->getUserId() !!}",
    "avatar": @if ($data->profile->avatar) "{!! $data->profile->avatar !!}" @else null @endif ,
    "nickname": "@escapeJson($data->profile->getNickname())",
    "verified": {
      "verified": @if ($data->profile->verified->verified) true @else false @endif,
      "names": [
@foreach ($data->profile->verified->names as $vNames)
        "@escapeJson($vNames)" @if ( ! $loop->last) , @endif
@endforeach
      ]
    },
    "hierarchyIcon": "{!! $data->profile->herarchyIcon !!}",
    "hierarchyName": "{!! $data->profile->herarchyName !!}",
    "hierarchyLogo": "{!! $data->profile->herarchyLogo !!}",
    "hierarchyAlias": "{!! $data->profile->herarchyAlias !!}",
    "popularity": {!! $data->profile->popularity !!},
    "consumeCoins": {!! $data->profile->consumeCoins !!},
    "wealthHierarchyIcon": "{!! $data->profile->wealthHerarchyIcon !!}",
    "wealthHierarchyName": "{!! $data->profile->wealthHerarchyName !!}",
    "wealthHierarchyLogo": "{!! $data->profile->wealthHerarchyLogo !!}",
    "wealthHierarchyAlias": "{!! $data->profile->wealthHerarchyAlias !!}",
  @if ($data->profile->friend)
    "friend": {
      "isFollowing": @if ($data->profile->friend->isFollowing) true @else false @endif,
      "isFollower": @if ($data->profile->friend->isFollower) true @else false @endif
    },
  @else
    "friend": null,
  @endif
    "sex": @if ($data->profile->getGender()) "{!! $data->profile->getGender() !!}" @else null @endif,
    "sign": "@escapeJson($data->profile->getSignature())",
    "signature": "@escapeJson($data->profile->getSignature())",
    "birthDate": @if ($data->profile->getBirthDate()) "{!! $data->profile->getBirthDate() !!}" @else null @endif,
    "mobile": "{!! (string) $mobileHelper::genLocalMobile($data->user->getMobile(), $data->user->getCountryCode()) !!}",
    "followers": {!! $data->profile->countFollowers() !!},
    "following": {!! $data->profile->countFollowings() !!},
    "views": {!! $data->profile->workListenNum !!},
    "gold": {!! $data->profile->coinBalance !!},
    "workCount": {!! $data->profile->work_count !!},
    "workChorusStart": {!! $data->profile->work_chorus_start_count !!},
     @if ($data->profile->modifiedLocation)
         "city": "{!! $data->profile->modifiedLocation->city !!}",
         "countryCode": "{!! $data->profile->modifiedLocation->countryCode !!}",
         "countryName": "{!! $data->profile->modifiedLocation->countryName !!}",
         "countryAbbr": "{!! $data->profile->modifiedLocation->abbr !!}",
     @endif
    "preferenceConf": {
        "notifyFollowed": {!! $data->profile->prefConf->followed ? "true" : "false" !!},
        "notifyFavourite": {!! $data->profile->prefConf->favourite ? "true" : "false" !!},
        "notifyComment": {!! $data->profile->prefConf->comment ? "true" : "false" !!},
        "notifyGift": {!! $data->profile->prefConf->gift ? "true" : "false" !!},
        "notifyImMsg": {!! $data->profile->prefConf->imMsg ? "true" : "false" !!},
        "privacyUnfollowedMsg": {!! $data->profile->prefConf->unfollowedMsg ? "true" : "false" !!}
    }
@else
    "avatar": null,
    "nickname": "",
    "sex": null,
    "sign": "",
    "signature": "",
    "mobile": "",
    "followers": 0,
    "following": 0,
    "views": 0,
    "gold":0,
    "workCount":0,
    "workChorusStart":0
@endif
  }
}
