{
"code": {!! $code !!},
"data": {
"users":[
@foreach ($data->profiles as $profile)
    {
    "userId":  "{!! $profile->userId !!}",
    "nickname": "@escapeJson($profile->nickname)",
    "sex": @if ($profile->gender) "{!! $profile->gender !!}" @else null @endif ,
    "avatar": @if ($profile->avatar) "{!! $profile->avatar !!}" @else null @endif,
    "sign": "@escapeJson($profile->signature)",
    "birthDate": @if ($profile->birthDate) "{!! $profile->birthDate !!}" @else null @endif,
    @if ($profile->friend)
        "friend": {
        "isFollowing": @if ($profile->friend->isFollowing) true @else false @endif,
        "isFollower": @if ($profile->friend->isFollower) true @else false @endif
        }
    @else
        "friend": null,
    @endif
    }
    @if ( ! $loop->last)
        ,
    @endif
@endforeach
]
}
}
