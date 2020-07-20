{
  "code": 0,
  "message": "",
  "data": {
    "feedCounts": {
      "workFavourite": {{ $data->feedCounts->workFavourite }},
      "workTransmit": {{ $data->feedCounts->workTransmit }},
      "workComment": {{ $data->feedCounts->workComment }},
      "follower": {{ $data->feedCounts->followed }},
      "workChorusJoin": {{ $data->feedCounts->workChorusJoin }},
      "gift": {{ $data->feedCounts->giftSendForWork }}
    },
    "bindTopics": [
@foreach ($data->bindTopics as $topic)
      "{{ $topic }}" @if ( ! $loop->last),@endif
@endforeach
    ]
  }
}
