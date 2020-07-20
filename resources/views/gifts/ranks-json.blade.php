{
  "code": 0,
  "message": "",
  "data": {
    "workId": "{!! $data->work->workId !!}",
    "workGiftAmount":{!! $data->work->giftAmount !!},
    "workCoinAmount":{!! $data->work->giftCoinAmount !!},
    "workGiftPopularity": {!! $data->work->giftPopularity !!},
    "workPopularity": {!! $data->work->giftPopularity !!},
    "users": [
@foreach ($data->ranks as $rank)
      {
        "userId": "{!! $rank->userId !!}",
        "avatar": "{!! $rank->avatar !!}",
        "nickname": "@escapeJson($rank->nickname)",
        "goldNumber": {!! $rank->coins !!},
        "consumeCoins": {!! $rank->consumeCoins !!},
        "wealthHierarchyName": "{!! $rank->wealthHierarchyName !!}",
        "wealthHierarchyIcon": "{!! $rank->wealthHierarchyIcon !!}",
        "wealthHierarchyLogo": "{!! $rank->wealthHierarchyLogo !!}",
        "wealthHierarchyAlias": "{!! $rank->wealthHierarchyAlias !!}",
        "gifts": [
         @foreach($rank->gifts as $gift)
             {
                 "name": "{!! $gift->giftName !!}",
                 "id" : "{!! $gift->giftId !!}",
                 "icon": {
                      "small": "{!! $gift->giftIcon->small !!}",
                      "big": "{!! $gift->giftIcon->big !!}"
                 },
                 "number": {!! $gift->giftNum !!},
                 "coins": {!! $gift->giftCoin !!}

         }@if (! $loop->last), @endif
         @endforeach
        ]
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
