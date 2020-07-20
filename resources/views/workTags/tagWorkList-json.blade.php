{
  "code": 0,
  "message": "",
  "data": {
    "works": [
@foreach ($data->latests as $work)
      {
        "id": "{!! $work->workId !!}",
        "workId": "{!! $work->workId !!}",
        "userId": "{!! $work->user->userId !!}",
        "avatar": "{!! $work->user->avatar !!}",
        "nickname": "@escapeJson($work->user->nickname)",
        "description": "@escapeJson($work->description)",
        "resource": "{!! $work->resource !!}",
        "musicId": "{!! $work->music->musicId !!}",
        "musicName": @if ($work->workName)
            "@escapeJson($work->workName)",
        @else
            "@escapeJson($work->music->name)",
        @endif
        "cover": "{!! $work->cover !!}",
        "chorusType": @if ($work->chorusType) {!! $work->chorusType !!} @else null @endif,
        "chorusCount": {!! $work->chorusCount !!},
        "listenNum": {!! $work->listenCount !!},
        "favouriteNum": {!! $work->favouriteCount !!},
        "commentNum": {!! $work->commentCount !!},
        "transmitNum": {!! $work->transmitCount !!},
        "publishDate": "{!! $work->createdAt->format(config('datetime.format.default.datetime')) !!}",
        "pubTimestamp": {{ $work->createdAt->getTimestamp() }}
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
