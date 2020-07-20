{
  "code": 0,
  "message": "",
  "data": {
        "name": "{!! $data->hierarchy->name !!}",
        "icon": "{!! $data->hierarchy->icon !!}",
        "logo": "{!! $data->hierarchy->iconSmall !!}",
        "alias": "{!! $data->hierarchy->alias !!}",
        "consumeCoins": {!! $data->hierarchy->consumeCoins !!},
        "gapCoins": {!! $data->hierarchy->gapCoins !!},
        "hierarchy": [
        @foreach ($data->lists as $hierarchy)
            {
            "name": "{!! $hierarchy->name !!}",
            "icon": "{!! $hierarchy->icon !!}",
            "logo": "{!! $hierarchy->iconSmall !!}",
            "alias": "{!! $hierarchy->alias !!}",
            "consumeCoins": {!! $hierarchy->consumeCoins !!}
            } @if ( ! $loop->last) , @endif
        @endforeach
        ]
  }
}
