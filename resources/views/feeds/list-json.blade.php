{
  "code": 0,
  "message": "",
  "data": {
    "feeds": [
@foreach ($data->feeds as $feed)
      {
        "feedId": "{!! $feed->feedId !!}",
        "userId": "{!! $feed->userId !!}",
        "type": "{{ $feed->type }}",
        "operator": {
          "userId": "{{ $feed->operator->userId }}",
          "name": "@escapeJson($feed->operator->name)",
          "avatar": "@escapeJson($feed->operator->avatar)"
        },
  @if ($feed->detail)
        "detail": {
          "workId": "{!! $feed->detail->workId !!}",
          "channel": @if ($feed->detail->channel) "{!! $feed->detail->channel !!}" @else null @endif,
      @if ($feed->detail->music)
              "music": {
                "musicId": "{!! $feed->detail->music->musicId !!}",
                "musicName": "@escapeJson($feed->detail->music->musicName)"
              }
      @else
              "music": null
      @endif
        },
  @else
        "detail" : null,
  @endif
        "isRead": @if ($feed->isRead) true @else false @endif ,
      "isFollowing" : @if ($feed->isFollowing) true @else false @endif,
      "publishAt": "{!! $feed->publishAt->format(config('datetime.format.default.datetime')) !!}",
      "pubTimestamp": {{ $feed->publishAt->getTimestamp() }}
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
