{
  "code": 0,
  "message": "",
  "data": {
    "works": [
@foreach ($data->works as $work)
      {
        "id": "{!! $work->id !!}",
        "workId": "{!! $work->workId !!}",
        "cover": "@escapeJson($work->cover)",
        "musicId": "{!! $work->musicId !!}",
        "musicName": "@escapeJson($work->musicName)",
        "chorusCount": {!! $work->chorusCount !!}
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
