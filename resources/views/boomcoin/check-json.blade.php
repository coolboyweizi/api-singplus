{
  "code": 0,
  "message": "",
  "data": {
      @if (!$data->result->orderWell)
          "boomCoins": {!! $data->result->boomcoins !!},
          "totalCoins": {!! $data->result->totalCoins !!},
          "orderId": "{!! $data->result->orderId !!}",
          "gainGold": {!! $data->result->incrCoin !!},
      @endif
     "orderWell":@if($data->result->orderWell) true @else false @endif
  }
}
