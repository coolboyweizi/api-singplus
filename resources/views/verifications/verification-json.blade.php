{
  "code": 0,
  "data": {
    "interval": {!! $data->verification->interval !!}
    @if (config('sms.config.pretending'))
    ,"code": "{!! $data->verification->code !!}"
    @endif
  }
}
