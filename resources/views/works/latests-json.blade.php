{
  "code": 0,
  "message": "",
  "data": {
    "latests": [
@foreach ($data->works as $work)
      {
        "workId": "{!! $work->workId !!}",
        "userId": "{!! $work->user->userId !!}",
        "avatar": "{!! $work->user->avatar !!}",
        "nickname": "@escapeJson($work->user->nickname)",
        "verified": {
          "verified": @if ($work->user->verified->verified) true @else false @endif,
          "names": [
    @foreach ($work->user->verified->names as $vNames)
            "@escapeJson($vNames)" @if ( ! $loop->last) , @endif
    @endforeach
          ]
        },
        "hierarchyIcon": "{!! $work->user->hierarchyIcon !!}",
        "hierarchyName": "{!! $work->user->hierarchyName !!}",
        "popularity": {!! $work->user->popularity !!},
        "hierarchyLogo": "{!! $work->user->hierarchyLogo !!}",
        "hierarchyAlias": "{!! $work->user->hierarchyAlias !!}",
        "author": {
          "userId": "{!! $work->user->userId !!}",
          "isFollowing": @if ($work->friend->isFollowing) true @else false @endif,
          "isFollower": @if ($work->friend->isFollower) true @else false @endif
        },
        "description": "@escapeJson($work->description)",
        "resource": "{!! $work->resource !!}",
        "shareLink": "@escapeJson($work->shareLink)",
        "giftAmount": {!! $work->giftAmount !!},
        "giftCoins": {!! $work->giftCoinAmount !!},
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
