{
  "code": 0,
  "message": "",
  "data": {
    "users": [
@foreach ($data->followers as $follower)
      {
        "id": "{!! $follower->id !!}",
        "userId": "{!! $follower->userId !!}",
        "nickname": "@escapeJson($follower->nickname)",
        "avatar": "{!! $follower->avatar !!}",
        "isFollower": @if ($follower->isFollower) true @else false @endif,
        "followedAt": @if ($follower->followedAt) "{!! $follower->followedAt->format(config('datetime.format.default.datetime')) !!}" @else null @endif,
        "followedAtTimestamp": @if ($follower->followedAt) {{ $follower->followedAt->getTimestamp() }} @else null @endif,
        "isFollowing": @if ($follower->isFollowing) true @else false @endif,
        "followAt": @if ($follower->followAt) "{!! $follower->followAt->format(config('datetime.format.default.datetime')) !!}" @else null @endif,
        "followAtTimestamp": @if ($follower->followAt) {{ $follower->followAt->getTimestamp() }} @else null @endif
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
