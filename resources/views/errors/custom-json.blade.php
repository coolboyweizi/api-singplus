{
  "code": {!! $code !!},
  "message": "@escapeJson($message)",
  "data": @if ($data) {!! json_encode($data) !!} @else {} @endif
@if ( ! empty($exception))
  ,"exception": {
    "file": "@escapeJson($exception['file'])",
    "line": "@escapeJson($exception['line'])",
    "trace": {!! json_encode($exception['trace']) ?: [] !!}
  }
@endif
}
