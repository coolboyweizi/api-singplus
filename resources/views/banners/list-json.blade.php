@if (config('app.op_data_fake'))
  @include('banners.list-json-fake')
@else
{
  "code": 0,
  "message": "",
  "data": {
    "banners": [
@foreach ($data->banners as $banner)
      {
        "id": "{!! $banner->id !!}",
        "image": "{!! $banner->image !!}",
        "type": "{!! $banner->type !!}",
        "attributes": {!! json_encode($banner->attributes) !!}
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
@endif
