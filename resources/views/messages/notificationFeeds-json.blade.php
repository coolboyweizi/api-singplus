{
  "code": 0,
  "message": "",
  "data": {
    "notifications": [
@foerach ($data->notifications as $feed)
      {
        "feedId": "{!! $feed->feedId !!}",
        "userId": "{!! $feed->userId !!}",
        "type": "{{ $feed->type }}",
        "operator": {
          "userId": "{{ $feed->operator->userId }}",
          "name": "@escapeJson($feed->operator->name)",
          "avatar": "@escapeJson($feed->operator->avatar)"
        },
        "detail": {
          "workId": "{!! $feed->workId !!}",
          "channel": @if ($feed->detail->channel) "{!! $feed->detail->channel !!}" @else null @endif,
  @if ($feed->detail->music)
          "music": {
            "musicId": "{!! $feed->detail->music->musicId !!}",
            "musicName": "@escapeJson($feed->detail->music->musicName)"
          }
  @else
          "music": null
  @endif
        }
        "isRead": @if ($feed->isRead) true @else false @endif ,
        "publishAt": "{!! $feed->publishAt->format(config('datetime.format.default.datetime')) !!}"
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
