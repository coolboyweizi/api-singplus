{
  "code": 0,
  "messages": "",
  "data": {
      "title": "{!! $data->tag->title !!}",
      @if($data->tag->cover)
          "cover": "{!! $data->tag->cover !!}",
      @endif
      @if($data->tag->desc)
          "desc": "{!! $data->tag->desc !!}",
      @endif
      "joinCount": {!! $data->tag->joinCount !!}

  }
}
