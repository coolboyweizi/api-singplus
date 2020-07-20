@if (config('app.op_data_fake'))
  @include('works.selections-json-fake')
@else
{
  "code": 0,
  "message": "",
  "data": {
    "selections": [
@foreach ($data->selections as $work)
      {
        "selectionId": "{!! $work->selectionId !!}",
        "workId": "{!! $work->workId !!}",
        "userId": "{!! $work->user->userId !!}",
        "avatar": @if ($work->user->avatar) "{!! $work->user->avatar !!}" @else null @endif,
        "nickname": "@escapeJson($work->user->nickname)",
        "hierarchyIcon": "{!! $work->user->hierarchyIcon !!}",
        "hierarchyName": "{!! $work->user->hierarchyName !!}",
        "popularity": {!! $work->user->popularity !!},
        "hierarchyLogo": "{!! $work->user->hierarchyLogo !!}",
        "hierarchyAlias": "{!! $work->user->hierarchyAlias !!}",
        "author": {
          "userId": "{!! $work->user->userId !!}",
          "isFollowing": @if ($work->user->isFollowing) true @else false @endif,
          "isFollower": @if ($work->user->isFollower) true @else false @endif
        },
        "verified": {
          "verified": @if ($work->user->verified->verified) true @else false @endif,
          "names": [
    @foreach ($work->user->verified->names as $vNames)
            "@escapeJson($vNames)" @if ( ! $loop->last) , @endif
    @endforeach
          ]
        },
        "musicId": "{!! $work->music->musicId !!}",
        "musicName": @if ($work->workName)
                      "@escapeJson($work->workName)", 
                     @else
                      "@escapeJson($work->music->name)",
                     @endif
        "cover": @if ($work->cover) "{!! $work->cover !!}" @else null @endif,
        "listenNum": {!! $work->listenCount !!},
        "commentNum": {!! $work->commentCount !!},
        "favouriteNum": {!! $work->favouriteCount !!},
        "transmitNum": {!! $work->transmitCount !!},
        "description": "@escapeJson($work->description)",
        "resource": "{!! $work->resource !!}",
        "shareLink": "@escapeJson($work->shareLink)",
        "giftAmount": {!! $work->giftAmount !!},
        "giftCoins": {!! $work->giftCoinAmount !!},
        "chorusType": @if ($work->chorusType) {!! $work->chorusType !!} @else null @endif,
        "chorusCount": {!! $work->chorusCount !!},
  @if ($work->originWorkUser)
        "originWorkUser": {
          "userId": "{!! $work->originWorkUser->userId !!}",
          "avatar": "@escapeJson($work->originWorkUser->avatar)",
          "nickname": "@escapeJson($work->originWorkUser->nickname)"
        },
  @else 
        "originWorkUser": null,
  @endif
        "date": "{!! $work->createdAt->format(config('datetime.format.default.datetime')) !!}",
        "pubTimestamp": {{ $work->createdAt->getTimestamp() }}
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
@endif
