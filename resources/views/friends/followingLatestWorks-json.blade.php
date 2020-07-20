{
  "code": 0,
  "message": "",
  "data": {
    "latests": [
@foreach ($data->latests as $work)
      {
        "id": "{!! $work->id !!}",
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
        "listenNum": {!! (int) $work->listenCount !!},
        "favouriteNum": {!! (int) $work->favouriteCount !!},
        "commentNum": {!! (int) $work->commentCount !!},
        "transmitNum": {!! (int) $work->transmitCount !!},
        "shareLink": "@escapeJson($work->shareLink)",
  @if ($work->originWorkUser)
        "originWorkUser": {
          "userId": "{!! $work->originWorkUser->userId !!}",
          "avatar": "@escapeJson($work->originWorkUser->avatar)",
          "nickname": "@escapeJson($work->originWorkUser->nickname)"
        },
  @else 
        "originWorkUser": null,
  @endif
        "publishDate": "{!! $work->createdAt->format(config('datetime.format.default.datetime')) !!}",
        "pubTimestamp": {{ $work->createdAt->getTimestamp() }}
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
