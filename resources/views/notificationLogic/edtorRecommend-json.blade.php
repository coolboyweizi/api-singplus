@inject('pushMessageConstant', 'SingPlus\Contracts\Notifications\Constants\PushMessage')

{
  "code": 0,
  "message": "",
  "data": {
    "messages": [
@foreach ($data->messages as $message)
      {
        "id": "{{ $message->id }}",
        "type": "{{ $message->type }}",
        "createdAt": "{!! $message->createdAt->format(config('datetime.format.default.datetime')) !!}",
        "createdTimestamp": {{ $message->createdAt->getTimestamp() }},
        "payload": {
  @if ($message->type == $pushMessageConstant::TYPE_MUSIC_SHEET)
          "musicSheetId": "{{ $message->payload->musicSheetId }}",
          "title": "@escapeJson($message->payload->title)",
          "cover": "@escapeJson($message->payload->cover)",
          "text": "@escapeJson($message->payload->text)"
  @elseif ($message->type == $pushMessageConstant::TYPE_WORK_SHEET)
          "workSheetId": "{{ $message->payload->workSheetId }}",
          "title": "@escapeJson($message->payload->title)",
          "cover": "@escapeJson($message->payload->cover)",
          "text": "@escapeJson($message->payload->text)"
  @elseif ($message->type == $pushMessageConstant::TYPE_NEW_MUSIC)
          "musicId": "{{ $message->payload->musicId }}",
          "text": "@escapeJson($message->payload->text)"
  @elseif ($pushMessageConstant::TYPE_NEW_WORK)
          "workId": "{{ $message->payload->workId }}",
          "text": "@escapeJson($message->payload->text)"
  @endif
        }
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
