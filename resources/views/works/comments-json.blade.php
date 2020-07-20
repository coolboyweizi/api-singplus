@inject('workService', 'SingPlus\Contracts\Works\Services\WorkService')
{
  "code": 0,
  "message": "",
  "data": {
    "comments": [
@foreach ($data->comments as $comment)
      {
        "commentId": "{!! $comment->commentId !!}",
        "repliedCommentId": @if ($comment->repliedCommentId) "{!! $comment->repliedCommentId !!}" @else null @endif,
        "authorId": "{!! $comment->author->userId !!}",
        "avatar": "{!! $comment->author->avatar !!}",
        "nickname": "@escapeJson($comment->author->nickname)",
        "date": "{!! $comment->createdAt->format(config('datetime.format.default.datetime')) !!}",
        "pubTimestamp": {{ $comment->createdAt->getTimestamp() }},
        "content": "@escapeJson($comment->content)",
        "repliedUserId": "{!! $comment->repliedUser->userId !!}",
        "repliedUserNickname": "@escapeJson($comment->repliedUser->nickname)",
        @if ($data->clientVersion)
          "commentType": {{$workService->compatCommenType($data->clientVersion, $comment->commentType)}}
        @else
          "commentType": {!!$comment->commentType!!}
        @endif
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
