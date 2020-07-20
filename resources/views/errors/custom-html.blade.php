<h1>Errors !!</h1>
<p><span>code:</span> {{ $code }}</p>
<p><span>message:</span> {{ $message }}</p>

@if ( ! empty($exception))
<h2>Trace</h2>
<p><span>file: </span> {{ $exception['file'] }}</p>
<p><span>line: </span> {{ $exception['line'] }}</p>
<p><span>trace: </span> {!! gettype($exception['trace']) !!}</p>
<ul>
  @foreach ($exception['trace'] as $item)
  <li>
    <p>{!! json_encode($item) !!}</p>
  </li>
  @endforeach
</ul>
@endif
