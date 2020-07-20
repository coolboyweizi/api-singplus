@inject('workService', 'SingPlus\Contracts\Works\Services\WorkService')
{
  "code": 0,
  "message": "",
  "data": {
    "gifts": [
@foreach ($data->feeds as $feed)
      {
        "feedId": "{!! $feed->feedId !!}",
        "feedType": "{!! $feed->feedType !!}",
        "authorId": "{!! $feed->author->userId !!}",
        "avatar": "{!! $feed->author->avatar !!}",
        "nickname": "@escapeJson($feed->author->nickname)",
        "workId": "{!! $feed->work->workId !!}",
        "musicName": @if ($feed->work->workName)
                          "@escapeJson($feed->work->workName)",
                      @else
                          @if($feed->music->musicName)
                              "@escapeJson($feed->music->musicName)",
                          @else
                              "",
                          @endif
                      @endif
        "chorusType": {!! $feed->work->chorusType !!},
        "gift": {
                "name": "{!! $feed->gift->giftName !!}",
                "number": {!! $feed->gift->giftAmount !!},
                "icon": {
                    "small":"{!! $feed->gift->icon->small !!}",
                    "big":"{!! $feed->gift->icon->big !!}"
                },
                "id": "{!! $feed->gift->giftId !!}"
        },
        "date": "{!! $feed->createdAt->format(config('datetime.format.default.datetime')) !!}",
        "pubTimestamp": {{ $feed->createdAt->getTimestamp() }},
        "hasRead": @if ($feed->isRead) true @else false @endif
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
