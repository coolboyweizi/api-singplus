{
  "code": 0,
  "message": "",
  "data": {
    "boomCoins": {!! $data->balance !!},
    "products": [
@foreach ($data->products as $product)
      {
        "productId": "{!! $product->productId !!}",
        "worth": {!! $product->coins !!},
        "price_amount_micros" : {!! $product->boomcoins !!}
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
