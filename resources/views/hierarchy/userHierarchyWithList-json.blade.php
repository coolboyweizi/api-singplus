{
  "code": 0,
  "message": "",
  "data": {
        "name": "{!! $data->hierarchy->name !!}",
        "icon": "{!! $data->hierarchy->icon !!}",
        "logo": "{!! $data->hierarchy->iconSmall !!}",
        "alias":"{!! $data->hierarchy->alias !!}",
        "popularity": {!! $data->hierarchy->popularity !!},
        "gapPopularity": {!! $data->hierarchy->gapPopularity !!},
        "hierarchy": [
        @foreach ($data->lists as $hierarchy)
            {
            "name": "{!! $hierarchy->name !!}",
            "icon": "{!! $hierarchy->icon !!}",
            "logo": "{!! $hierarchy->iconSmall !!}",
            "alias":"{!! $hierarchy->alias !!}",
            "popularity": {!! $hierarchy->popularity !!}
            } @if ( ! $loop->last) , @endif
        @endforeach
        ]
  }
}
