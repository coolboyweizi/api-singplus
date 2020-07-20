@if (config('app.op_data_fake'))
  @include('artists.listByCategory-json-fake')
@else
{
  "code": 0,
  "message": "",
  "data": {
    "artists": [
@foreach ($data->artists as $artist)
      {
        "artistId": "{!! $artist->artistId !!}",
        "avatar": "{!! $artist->avatar !!}",
        "name": "@escapeJson($artist->name)",
        "abbreviation": "{!! strtoupper($artist->abbreviation) !!}"
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
@endif
