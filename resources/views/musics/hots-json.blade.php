{
  "code": 0,
  "message": "",
  "data": {
    "musics": [
@foreach ($data->musics as $music)
      {
        "id": "{!! $music->id !!}",
        "musicId": "{!! $music->musicId !!}",
        "cover": "{!! $music->cover !!}",
        "name": "@escapeJson($music->name)",
        "artists": "@escapeJson(implode($music->artists, ' '))",
        "size": "{!! round($music->size->total * 1.0 / 1024 / 1024, 2) !!}M",
        "requestNum": {!! $music->requestNum !!}
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
