{
  "code": 0,
  "message": "",
  "data": {
    "gifts": [
@foreach ($data->gifts as $gift)
      {
        "id": "{!! $gift->giftId !!}",
        "name": "{!! $gift->giftName !!}",
        "type": "{!! $gift->giftType !!}",
        "icon": {
              "small": "{!! $gift->giftIcon->small !!}",
              "big"  : "{!! $gift->giftIcon->big !!}"
        },
        "worth": {!! $gift->giftWorth !!},
        "popularity": {!! $gift->giftPopularity !!},
        "animation": {
            "giftUrl": "{!! $gift->giftAnimation->url !!}",
            "type": {!! $gift->giftAnimation->type !!},
            "duration": {!! $gift->giftAnimation->duration  !!}
        }
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
