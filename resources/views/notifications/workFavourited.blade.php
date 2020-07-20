@if ($waitNum > 1)
{{ $receptorNickname }}, you have {{ $waitNum }} likes or more after that, check out now
@else
{{ $receptorNickname }}, {{ $nickname }} liked your song {{ $musicName }}
@endif
