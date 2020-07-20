{
  "code": {!! $code !!},
  "data": {
    "gallery": [
@foreach ($data->gallery as $image)
      {
        "imageId": "{!! $image->imageId !!}",
        "url": "{!! $image->url !!}",
        "isAvatar": @if ($image->isAvatar) true @else false @endif
      }
  @if ( ! $loop->last)
      ,
  @endif
@endforeach
    ]
  }
}
