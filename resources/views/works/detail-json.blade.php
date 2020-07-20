{
  "code": 0,
  "message": "",
  "data": {
    "work": {
      "workId": "{!! $data->work->workId !!}",
      "userId": "{!! $data->work->user->userId !!}",
      "avatar": "{!! $data->work->user->avatar !!}",
      "nickname": "@escapeJson($data->work->user->nickname)",
      "verified": {
        "verified": @if ($data->work->user->verified->verified) true @else false @endif,
        "names": [
@foreach ($data->work->user->verified->names as $vNames)
        "@escapeJson($vNames)" @if ( ! $loop->last) , @endif
@endforeach
        ]
      },
      "hierarchyIcon": "{!! $data->work->user->hierarchyIcon !!}",
      "hierarchyName": "{!! $data->work->user->hierarchyName !!}",
      "hierarchyLogo": "{!! $data->work->user->hierarchyLogo !!}",
      "hierarchyAlias": "{!! $data->work->user->hierarchyAlias !!}",
      "popularity": {!! $data->work->user->popularity !!},
      "isFollow": "{!! $data->work->user->isFollow !!}",
      "followerCount": {!! $data->work->user->followerCount !!},
      @if (isset($data->work->friend))
      "author": {
        "userId": "{!! $data->work->user->userId !!}",
        "isFollowing": @if ($data->work->friend->isFollowing) true @else false @endif,
        "isFollower": @if ($data->work->friend->isFollower) true @else false @endif
      },
      @endif
      "musicId": "{!! $data->work->music->musicId !!}",
      "musicName": @if ($data->work->workName)
                    "@escapeJson($data->work->workName)",
                   @else
                    "@escapeJson($data->work->music->name)",
                   @endif
      "artists": "{!! isset($data->work->music->artists) ? implode(" ", $data->work->music->artists) : "" !!}",
      "lyric": "{!! $data->work->music->lyric !!}",
      "resource": "{!! $data->work->resource !!}",
      "description": "@escapeJson($data->work->description)",
      "cover": "{!! $data->work->cover !!}",
      "slides": [
@foreach ($data->work->slides as $image)
        "{!! $image !!}" @if ( ! $loop->last) , @endif
@endforeach
      ],
      "listenNum": {!! $data->work->listenCount !!},
      "favouriteNum": {!! $data->work->favouriteCount !!},
      "isFavourite": {!! $data->work->isFavourite ? "true" : "false" !!},
      "commentNum": {!! $data->work->commentCount !!},
      "transmitNum": {!! $data->work->transmitCount !!},
      "publishDate": "{!! $data->work->createdAt->format(config('datetime.format.default.datetime')) !!}",
      "pubTimestamp": {{ $data->work->createdAt->getTimestamp() }},
      "duration": {!! $data->work->duration !!},
      "noAccompaniment": @if ($data->work->noAccompaniment) true @else false @endif,
      "favourites": [
@foreach ($data->work->favourites as $favourite)
        {
          "userId": "{!! $favourite->userId !!}",
          "avatar": "@escapeJson($favourite->avatar)"
        } @if ( ! $loop->last) , @endif
@endforeach
      ],
      "chorusType": @if ($data->work->chorusType) {!! $data->work->chorusType !!} @else null @endif,
@if (isset($data->work->chorusStartInfo) && $data->work->chorusStartInfo)
      "chorusStartInfo": {
        "chorusCount": {!! $data->work->chorusStartInfo->chorusCount !!}
      },
@endif
@if (isset($data->work->chorusJoinInfo) && $data->work->chorusJoinInfo)
      "chorusJoinInfo": {
        "workId": "{!! $data->work->chorusJoinInfo->originWorkId !!}",
        "workDescription": "@escapeJson($data->work->chorusJoinInfo->description)",
        "author": {
          "userId": "{!! $data->work->chorusJoinInfo->author->userId !!}",
          "nickname": "@escapeJson($data->work->chorusJoinInfo->author->nickname)",
          "avatar": "@escapeJson($data->work->chorusJoinInfo->author->avatar)",
          "signature": "@escapeJson($data->work->chorusJoinInfo->author->signature)",
          "hierarchyIcon": "{!! $data->work->chorusJoinInfo->author->hierarchyIcon !!}",
          "hierarchyName": "{!! $data->work->chorusJoinInfo->author->hierarchyName !!}",
          "hierarchyLogo": "{!! $data->work->chorusJoinInfo->author->hierarchyLogo !!}",
          "hierarchyAlias": "{!! $data->work->chorusJoinInfo->author->hierarchyAlias !!}",
          "popularity": {!! $data->work->chorusJoinInfo->author->popularity !!},
          "isFollowing": @if ($data->work->chorusJoinInfo->friend->isFollowing) true @else false @endif
        }
      },
@endif
      "shareLink": "{!! $data->work->shareLink !!}",
      "giftAmount": {!! $data->work->giftAmount !!},
      "giftCoins": {!! $data->work->giftCoinAmount !!},
      "isPrivate": @if ($data->work->isPrivate) true @else false @endif
    }
  }
}
