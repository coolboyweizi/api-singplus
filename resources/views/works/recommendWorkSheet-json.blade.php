{
  "code": 0,
  "message": "",
  "data": {
    "sheet": {
      "title": "@escapeJson($data->sheet->title)",
      "comments": "@escapeJson($data->sheet->recommendText)",
      "cover": "@escapeJson($data->sheet->cover)",
      "works": [
@foreach ($data->sheet->works as $work)
        {
          "workId": "{!! $work->workId !!}",
          "musicId": "{!! $work->music->musicId !!}",
          "name": @if ($work->workName) "@escapeJson($work->workName)" @else "@escapeJson($work->music->name)" @endif,
          "cover": "@escapeJson($work->cover)",
          "description": "@escapeJson($work->description)",
          "listenNum": {!! $work->listenCount !!},
          "commentNum": {!! $work->commentCount !!},
          "favouriteNum": {!! $work->favouriteCount !!},
          "chorusType": @if ($work->chorusType) {!! $work->chorusType !!} @else null @endif,
          "chorusCount": {!! $work->chorusCount !!},
          "resource": "{!! $work->resource !!}",
          "shareLink": "@escapeJson($work->shareLink)",
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
          "author": {
            "userId": "{!! $work->user->userId !!}",
            "nickname": "@escapeJson($work->user->nickname)",
            "avatar": "@escapeJson($work->user->avatar)",
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
            "isFollowing": @if ($work->user->friend->isFollowing) true @else false @endif,
            "isFollower": @if ($work->user->friend->isFollower) true @else false @endif
          },
          "publishDate": "{!! $work->createdAt->format(config('datetime.format.default.datetime')) !!}",
          "pubTimestamp": {{ $work->createdAt->getTimestamp() }}
        } @if ( ! $loop->last) , @endif
@endforeach
      ]
    }
  }
}
