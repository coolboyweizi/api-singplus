{
  "code": 0,
  "message": "",
  "data": {
    "favourites": [
@foreach ($data->favourites as $favourite)
      {
        "id": "{!! $favourite->id !!}",
        "favouriteId": "{!! $favourite->favouriteId !!}",
        "userId": "{!! $favourite->userId !!}",
        "avatar": "@escapeJson($favourite->avatar)",
        "nickname": "@escapeJson($favourite->nickname)",
        "signature": "@escapeJson($favourite->signature)"
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
