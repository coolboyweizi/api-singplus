{
  "code": 0,
  "messages": "",
  "data": {
    "works": [
@foreach ($data->works as $work)
      {
        "id": "{!! $work->id !!}",
        "workId": "{!! $work->workId !!}",
        "author": {
          "userId": "{!! $work->author->userId !!}",
          "avatar": "@escapeJson($work->author->avatar)",
          "nickname": "@escapeJson($work->author->nickname)"
        },
        "publishedAt": "{!! $work->createdAt->format(config('datetime.format.default.datetime')) !!}",
        "publishedTimestamp": {{ $work->createdAt->getTimestamp() }}
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
