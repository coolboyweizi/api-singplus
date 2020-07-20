{
  "code": 0,
  "messages": "",
  "data": {
    "recommends": [
@foreach ($data->userWorks as $info)
      {
        "id": "{!! $info->id !!}",
        "userId": "{!! $info->user->userId !!}",
        "avatar": "@escapeJson($info->user->avatar)",
        "nickname": "@escapeJson($info->user->nickname)",
        "signature": "@escapeJson($info->user->signature)",
        "isFollowing": @if ($info->user->isFollowing) true @else false @endif,
        "isFollower": @if ($info->user->isFollower) true @else false @endif,
        "recCategroy": @if ($info->isAutoRecommend) 1 @else 2 @endif,
        "works": [
  @foreach ($info->works as $work)
          {
            "workId": "{!! $work->workId !!}",
            "cover": "@escapeJson($work->cover)",
            "musicName": "@escapeJson($work->musicName)",
            "listenNum": {!! $work->listenCount !!},
            "chorusType": @if ($work->chorusType) {!! $work->chorusType !!} @else null @endif
          } @if ( ! $loop->last) , @endif
  @endforeach
        ]
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  } 
}
