@inject('workService', 'SingPlus\Contracts\Works\Services\WorkService')
{
  "code": 0,
  "message": "",
  "data": {
    "comments": [
@foreach ($data->comments as $comment)
      {
        "feedId": "{!! $comment->feedId !!}",
        "feedType": "{!! $comment->feedType !!}",
        "commentId": "{!! $comment->commentId !!}",
        "repliedCommentId": @if ($comment->repliedCommentId) "{!! $comment->repliedCommentId !!}" @else null @endif,
        "authorId": "{!! $comment->author->userId !!}",
        "avatar": "{!! $comment->author->avatar !!}",
        "nickname": "@escapeJson($comment->author->nickname)",
        "musicId": "{!! $comment->music->musicId !!}",
        "musicName": @if ($comment->work->workName)
                      "@escapeJson($comment->work->workName)",
                     @else
                      "@escapeJson($comment->music->name)",
                     @endif
        "workId": "{!! $comment->work->workId !!}",
  @if ($comment->giftInfo)
        "gift": {
            "name": "{!! $comment->giftInfo->giftName !!}",
            "count": {!! $comment->giftInfo->giftAmount !!}

        },
  @endif
  @if ($comment->repliedComment)
        "repliedCommentId": "{!! $comment->repliedComment->commentId !!}",
        "repliedCommentContent": "@escapeJson($comment->repliedComment->content)",
  @else
        "repliedCommentId": null,
        "repliedCommentContent": null,
  @endif
        "content": "@escapeJson($comment->content)",
        @if ($data->clientVersion)
            "commentType": {{$workService->compatCommenType($data->clientVersion, $comment->commentType)}},
        @else
            "commentType": {!!$comment->commentType!!},
        @endif
        "date": "{!! $comment->createdAt->format(config('datetime.format.default.datetime')) !!}",
        "pubTimestamp": {{ $comment->createdAt->getTimestamp() }},
        "isNormal": @if ($comment->isNormal) true @else false @endif,
        "hasRead": @if ($comment->isRead) true @else false @endif
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
