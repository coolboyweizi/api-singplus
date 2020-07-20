@if ($isReplyToWork)
{{ $receptorNickname }}, {{ $nickname }} commented on your song {{ $musicName }}
@else
{{ $receptorNickname }}, {{ $nickname }} replied your comment
@endif
