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
        "musicId": "{!! $comment->music->musicId !!}",
        "musicName": "@escapeJson($comment->music->name)",
        "workId": "{!! $comment->work->workId !!}",
  @if ($comment->repliedComment)
        "repliedCommentId": "{!! $comment->repliedComment->commentId !!}",
        "repliedCommentContent": "@escapeJson($comment->repliedComment->content)",
  @else
        "repliedCommentId": null,
        "repliedCommentContent": null,
  @endif
        "content": "@escapeJson($comment->content)",
        "date": "{!! $comment->createdAt->format(config('datetime.format.default.datetime')) !!}",
        "pubTimestamp": {{ $comment->createdAt->getTimestamp() }},
        "hasRead": false,
        "commentType": {!!$comment->commentType!!}
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
