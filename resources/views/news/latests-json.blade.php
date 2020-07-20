{
  "code": 0,
  "message": "",
  "data": {
    "latests": [
@foreach ($data->news as $new)
      {
        "newsId": "{!! $new->newsId !!}",
        "type": "{!! $new->type !!}",
        "desc": "{!! $new->desc !!}",
        "author": {
          "userId": "{!! $new->author->userId !!}",
          "avatar": "{!! $new->author->avatar !!}",
          "nickname": "@escapeJson($new->author->nickname)",
          "verified": {
            "verified": @if ($new->author->verified->verified) true @else false @endif,
            "names": [
        @foreach ($new->author->verified->names as $vNames)
              "@escapeJson($vNames)" @if ( ! $loop->last) , @endif
        @endforeach
            ]
          }
        },
        "detail":{
            "work" : {
                  "workId": "{!! $new->work->workId !!}",
                  "userId": "{!! $new->work->user->userId !!}",
                  "avatar": "{!! $new->work->user->avatar !!}",
                  "nickname": "@escapeJson($new->work->user->nickname)",
                  "verified": {
                    "verified": @if ($new->work->user->verified->verified) true @else false @endif,
                    "names": [
                @foreach ($new->work->user->verified->names as $vNames)
                      "@escapeJson($vNames)" @if ( ! $loop->last) , @endif
                @endforeach
                    ]
                  },
                  "hierarchyIcon": "{!! $new->work->user->hierarchyIcon !!}",
                  "hierarchyName": "{!! $new->work->user->hierarchyName !!}",
                  "hierarchyLogo": "{!! $new->work->user->hierarchyLogo !!}",
                  "hierarchyAlias": "{!! $new->work->user->hierarchyAlias !!}",
                  "popularity": {!! $new->work->user->popularity !!},
                  "author": {
                      "userId": "{!! $new->work->user->userId !!}"
                  },
                  "description": "@escapeJson($new->work->description)",
                  "resource": "{!! $new->work->resource !!}",
                  "shareLink": "@escapeJson($new->work->shareLink)",
                  "musicId": "{!! $new->work->music->musicId !!}",
                  "musicName": @if ($new->work->workName)
                      "@escapeJson($new->work->workName)",
                  @else
                      "@escapeJson($new->work->music->name)",
                  @endif
                  "cover": "{!! $new->work->cover !!}",
                  "chorusType": @if ($new->work->chorusType) {!! $new->work->chorusType !!} @else null @endif,
                  "chorusCount": {!! $new->work->chorusCount !!},
                  "listenNum": {!! (int) $new->work->listenCount !!},
                  "favouriteNum": {!! (int) $new->work->favouriteCount !!},
                  "commentNum": {!! (int) $new->work->commentCount !!},
                  "transmitNum": {!! (int) $new->work->transmitCount !!},
                  @if ($new->work->originWorkUser)
                      "originWorkUser": {
                      "userId": "{!! $new->work->originWorkUser->userId !!}",
                      "avatar": "@escapeJson($new->work->originWorkUser->avatar)",
                      "nickname": "@escapeJson($new->work->originWorkUser->nickname)"
                      },
                  @else
                      "originWorkUser": null,
                  @endif
                  "publishDate": "{!! $new->work->createdAt->format(config('datetime.format.default.datetime')) !!}",
                  "pubTimestamp": {{ $new->work->createdAt->getTimestamp() }},
                  "status": {{$new->work->status}},
                  "giftAmount": {!! $new->work->giftAmount !!},
                  "giftCoins": {!! $new->work->giftCoinAmount !!}
            },
            @if ($new->relationships)
            "friends": {
            "isFollowing": @if ($new->relationships->isFollowing) true @else false @endif,
            "isFollower": @if ($new->relationships->isFollower) true @else false @endif
            }
            @else
            "friends": null
            @endif
        },
        "publishDate": "{!! $new->createdAt->format(config('datetime.format.default.datetime')) !!}",
        "pubTimestamp": {{ $new->createdAt->getTimestamp() }}
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
