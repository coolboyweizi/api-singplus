{
  "code": 0,
  "message": "",
  "data": {
    "works": [
@foreach ($data->works as $work)
      {
        "workId": "{!! $work->workId !!}",
        "userId": "{!! $work->user->userId !!}",
        "avatar": "{!! $work->user->avatar !!}",
        "nickname": "@escapeJson($work->user->nickname)",
        "hierarchyIcon": "{!! $work->user->hierarchyIcon !!}",
        "hierarchyName": "{!! $work->user->hierarchyName !!}",
        "popularity": {!! $work->user->popularity !!},
        "hierarchyLogo": "{!! $work->user->hierarchyLogo !!}",
        "hierarchyAlias": "{!! $work->user->hierarchyAlias !!}",
        "description": "@escapeJson($work->description)",
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
        "giftAmount": {!! $work->giftAmount !!},
        "giftCoins": {!! $work->giftCoinAmount !!},
  @if ($work->originWorkUser)
        "originWorkUser": {
          "userId": "{!! $work->originWorkUser->userId !!}",
          "avatar": "@escapeJson($work->originWorkUser->avatar)",
          "nickname": "@escapeJson($work->originWorkUser->nickname)"
        },
  @else 
        "originWorkUser": null,
  @endif
        "isPrivate": @if ($work->isPrivate) true @else false @endif,
        "publishDate": "{!! $work->createdAt->format(config('datetime.format.default.datetime')) !!}"
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
