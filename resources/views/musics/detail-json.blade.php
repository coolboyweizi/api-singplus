{
  "code": 0,
  "messages": "",
  "data": {
    "music": {
      "name": "@escapeJson($data->music->music->name)",
      "cover": "@escapeJson($data->music->music->cover)",
      "size": "{!! round($data->music->music->size * 1.0 / 1000 / 1000, 2) !!}M",
      "sizeBytes": {!! $data->music->music->size !!},
      "etag": @if ($data->music->music->etag) "@escapeJson($data->music->music->etag)" @else null @endif,
      "artists": "@escapeJson($data->music->music->artistsName)"
    }
@if ( ! $data->basic)
    ,
    "chorusRecommends": [
  @foreach ($data->music->chorusRecommends as $work)
      {
        "workId": "{!! $work->workId !!}",
        "chorusCount": {!! $work->chorusCount !!},
        "author": {
          "userId": "{!! $work->author->userId !!}",
          "avatar": "@escapeJson($work->author->avatar)",
          "nickname": "@escapeJson($work->author->nickname)"
        }
      } @if ( ! $loop->last) , @endif
  @endforeach
    ],
    "soloRankinglists": [
  @foreach ($data->music->soloRankinglists as $work)
      {
        "rank": {!! $loop->index + 1 !!},
        "workId": "{!! $work->workId !!}",
        "listenCount": {!! $work->listenCount !!},
        "author": {
          "userId": "{!! $work->author->userId !!}",
          "avatar": "@escapeJson($work->author->avatar)",
          "nickname": "@escapeJson($work->author->nickname)"
        }
      } @if ( ! $loop->last) , @endif
  @endforeach
    ]
@endif
  }
}
