{
  "code": 0,
  "message": "",
  "data": {
    "logged": @if ($data->logged) true @else false @endif,
@if ($data->user)
    "user": {
      "isNewUser": @if ($data->user->isNewUser) true @else false @endif
    },
@else
    "user": null,
@endif
    "update": {
      "isUrlForce": @if ($data->update->isInnerUpdateOn) true @else false @endif,
      "recommendUpdate": @if ($data->update->recommendUpdate) true @else false @endif,
      "forceUpdate": @if ($data->update->forceUpdate) true @else false @endif,
      "updateTips":
  @if ($data->update->updateTips)
        {
          "version": "@escapeJson($data->update->updateTips->version)",
          "url": "@escapeJson($data->update->updateTips->url)",
          "apkUrl": "@escapeJson($data->update->updateTips->apkUrl)",
          "tips": "@escapeJson($data->update->updateTips->tips)"
        }
  @else
        null
  @endif
    },
    "payPal": {
      "isOpen": @if ($data->payPal->isOpen) true @else false @endif,
      "url": "@escapeJson($data->payPal->url)"
    },
    "ads": [
@foreach ($data->ads as $ad)
      {
        "adId": "{!! $ad->adId !!}", 
        "title": "@escapeJson($ad->title)",
        "type": "@escapeJson($ad->type)",
        "needLogin": @if ($ad->needLogin) true @else false @endif,
        "image": "@escapeJson($ad->image)",
        "specImages": {
  @foreach ($ad->specImages as $spec => $image)
          "@escapeJson($spec)": "@escapeJson($image)" @if ( ! $loop->last) , @endif
  @endforeach
        },
        "link": @if ($ad->link) "@escapeJson($ad->link)" @else null @endif,
        "startTimestamp": {{ $ad->startTime->getTimestamp() }},
        "stopTimestamp": {{ $ad->stopTime->getTimestamp() }}
      } @if ( ! $loop->last) , @endif
@endforeach
    ],
    "supportLangs": [
@foreach ($data->supportLangs as $lang)
        "{{ $lang }}" @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
