{
  "code": 0,
  "message": "",
  "data": {
@if ( ! $data->sheet)
    "sheet": null
@else
    "sheet": {
      "title": "@escapeJson($data->sheet->title)",
      "cover": [
  @foreach ($data->sheet->cover as $cover)
        "{!! $cover !!}" @if ( ! $loop->last) , @endif
  @endforeach
      ],
      "requestNum": {!! $data->sheet->requestNum !!},
      "musics": [
  @foreach ($data->sheet->musics as $music)
        {
          "musicId": "{!! $music->musicId !!}",
          "name": "@escapeJson($music->name)",
          "artists": "@escapeJson(implode($music->artists, ' '))",
          "size": "{!! round($music->size->total * 1.0 / 1024 / 1024, 2) !!}M"
        } @if ( ! $loop->last) , @endif
  @endforeach
      ]
    }
@endif
  }
}
