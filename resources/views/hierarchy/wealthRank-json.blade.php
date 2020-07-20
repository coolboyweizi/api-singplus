{
  "code": 0,
  "message": "",
  "data": {
        "ranks": [
        @foreach ($data->ranks as $rank)
            {
            "userId": "{!! $rank->userId !!}",
            "nickname": "{!! $rank->nickname !!}",
            "avatar": "{!! $rank->avatar !!}",
            "rankId": "{!! $rank->rankId !!}",
            "wealthHierarchyName": "{!! $rank->wealthHierarchy->name !!}",
            "wealthHierarchyIcon": "{!! $rank->wealthHierarchy->icon !!}",
            "wealthHierarchyLogo": "{!! $rank->wealthHierarchy->iconSmall !!}",
            "wealthHierarchyAlias": "{!! $rank->wealthHierarchy->alias !!}",
            "consumeCoins": {!! $rank->wealthHierarchy->consumeCoins !!},
            "gapCoins": {!! $rank->wealthHierarchy->gapCoins !!}
            } @if ( ! $loop->last) , @endif
        @endforeach
        ]
  }
}
