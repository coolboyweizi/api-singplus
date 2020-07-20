{
  "code": 0,
  "messages": "",
  "data": {
    "ranks": [
@foreach ($data->ranks as $rank)
      {
        "id": "{!! $rank->id !!}",
        "workId": "{!! $rank->workId !!}",
        "avatar": "@escapeJson($rank->author->avatar)",
        "nickname": "@escapeJson($rank->author->nickname)",
        "hierarchyIcon": "{!! $rank->author->hierarchyIcon !!}",
        "hierarchyName": "{!! $rank->author->hierarchyName !!}",
        "popularity": {!! $rank->author->popularity !!},
        "hierarchyLogo": "{!! $rank->author->hierarchyLogo !!}",
        "hierarchyAlias": "{!! $rank->author->hierarchyAlias !!}",
        "musicName": "@escapeJson($rank->music->name)"
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
