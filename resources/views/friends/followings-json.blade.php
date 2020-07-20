{
  "code": 0,
  "message": "",
  "data": {
    "users": [
@foreach ($data->followings as $follow)
      {
        "userId": "{!! $follow->userId !!}",
        "nickname": "@escapeJson($follow->nickname)",
        "avatar": "{!! $follow->avatar !!}",
        "isFollowing": @if ($follow->isFollowing) true @else false @endif,
        "followAt": @if ($follow->followAt) "{!! $follow->followAt->format(config('datetime.format.default.datetime')) !!}" @else null @endif,
        "followAtTimestamp": @if ($follow->followAt) {{ $follow->followAt->getTimestamp() }} @else null @endif,

        "isFollower": @if ($follow->isFollower) true @else false @endif,

        "followedAt": @if ($follow->followedAt) "{!! $follow->followedAt->format(config('datetime.format.default.datetime')) !!}" @else null @endif,
        "followedAtTimestamp": @if ($follow->followedAt) {{ $follow->followedAt->getTimestamp() }} @else null @endif
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
