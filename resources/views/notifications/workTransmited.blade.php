@if ($waitNum > 1)
{{ $receptorNickname }}, {{ $waitNum }} or more people shared your song, check out who the are
@else
{{ $receptorNickname }}, {{ $nickname }} shared your song {{ $musicName }}
@endif
