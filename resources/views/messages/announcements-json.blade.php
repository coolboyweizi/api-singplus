{
  "code": 0,
  "message": "",
  "data": {
    "announcements": [
@foreach ($data->announcements as $announcement)
      {
        "announcementId": "{!! $announcement->announcementId !!}",
        "title": "@escapeJson($announcement->title)",
        "cover": "{!! $announcement->cover !!}",
        "summary": "@escapeJson($announcement->summary)",
        "type": "{!! $announcement->type !!}",
        "attributes": {!! json_encode($announcement->attributes) !!},
        "date": "{!! $announcement->createdAt->format(config('datetime.format.default.datetime')) !!}",
        "pubTimestamp": {{ $announcement->createdAt->getTimestamp() }}
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
