{
  "code": 0,
  "messages": "",
  "data": {
    "recommends": [
@foreach ($data->users as $user)
      {
        "id": "{!! $user->id !!}",
        "userId": "{!! $user->userId !!}",
        "avatar": "@escapeJson($user->avatar)",
        "nickname": "@escapeJson($user->nickname)",
        "isFollowing": @if ($user->isFollowing) true @else false @endif,
        "isFollower": @if ($user->isFollower) true @else false @endif,
        "recCategroy": @if ($user->isAutoRecommend) 1 @else 2 @endif
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
