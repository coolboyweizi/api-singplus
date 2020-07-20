@if (config('app.op_data_fake'))
  @include('musics.list-json-fake')
@else
{
  "code": 0,
  "message": "",
  "data": {
    "musics": [
@foreach ($data->musics as $music)
      {
        "musicId": "{!! $music->musicId !!}",
        "name": "@escapeJson($music->name)",
        "size": "{!! round($music->size->total * 1.0 / 1000 / 1000, 2) !!}M",
        "artists": "@escapeJson(collect($music->artists)->implode('name', ' '))",
    @if (@isset($music->highlight))
        "highlight": {
        @foreach ($music->highlight as $k => $v)
            "@escapeJson($k)": "@escapeJson($v[0])",
        @endforeach
            "_placehold": true
        }
    @else
        "highlight": null
    @endif
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
@endif
