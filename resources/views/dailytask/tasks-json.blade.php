{
  "code": 0,
  "message": "",
  "data": {
    "totalCoins": {!! $data->totalCoins !!},
    "tasks": [
@foreach ($data->tasks as $task)
      {
        "taskId": "{!! $task->taskId !!}",
        "type": "{!! $task->type !!}",
        "status" : {!! $task->status !!},
        "value": {!! $task->value !!} ,
        "days": {!! $task->days !!} ,
        "title": "{!! $task->title !!}",
        "desc" : "{!! $task->desc !!}",
        "recentDays" : [
            @foreach($task->recentDays as $recent)
            {
                "day": {!! $recent['day'] !!},
                "value": {!! $recent['value'] !!}
            }@if (! $loop->last), @endif
            @endforeach
         ]
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
