{
  "code": 0,
  "message": "",
  "data": {
    "languages": [
@foreach ($data->languages as $language)
      {
        "languageId": "{!! $language->id !!}",
        "cover": "{!! $language->cover !!}",
        "name": "{!! $language->name !!}",
        "totalNum": {!! $language->totalNum !!}
      } @if ( ! $loop->last) , @endif
@endforeach
    ],
    "styles": [
@foreach ($data->styles as $style)
      {
        "styleId": @if ($style->id) "{!! $style->id !!}" @else null @endif,
        "cover": "{!! $style->cover !!}",
        "name": "{!! $style->name !!}",
        "totalNum": {!! $style->totalNum !!}
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
