{
  "code": 0,
  "message": "",
  "data": {
    "users": [
@foreach ($data->users as $user)
      {
        "socialiteUserId": "{!! $user->socialiteUserId !!}",
        "provider": "{{ $user->provider }}",
        "userId": "{!! $user->userId !!}",
        "nickname": "@escapeJson($user->nickname)",
        "avatar": "{!! $user->avatar !!}",
        "isFollowing": @if ($user->isFollowing) true @else false @endif,
        "followAt": @if ($user->followAt) "{!! $user->followAt->format(config('datetime.format.default.datetime')) !!}" @else null @endif,
        "followAtTimestamp": @if ($user->followAt) {{ $user->followAt->getTimestamp() }} @else null @endif,
        "isFollower": @if ($user->isFollower) true @else false @endif,
        "followedAt": @if ($user->followedAt) "{!! $user->followedAt->format(config('datetime.format.default.datetime')) !!}" @else null @endif,
        "followedAtTimestamp": @if ($user->followedAt) {{ $user->followedAt->getTimestamp() }} @else null @endif
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
