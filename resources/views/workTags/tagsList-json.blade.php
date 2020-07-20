{
  "code": 0,
  "messages": "",
  "data": {
    "tags": [
@foreach ($data->tags as $tag)
      {
        "title": "{!! $tag->title !!}"
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
