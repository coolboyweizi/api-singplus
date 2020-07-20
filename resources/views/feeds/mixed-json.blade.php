@inject('feedConstant', 'SingPlus\Contracts\Feeds\Constants\Feed')
{
  "code": 0,
  "message": "",
  "data": {
    "feeds": [
@foreach ($data->feeds as $feed)
      {
        "feedId": "{!! $feed->feedId !!}",
        "feedType": "{!! $feed->feedType !!}",
        "authorId": "{!! $feed->author->userId !!}",
        "avatar": "{!! $feed->author->avatar !!}",
        "nickname": "@escapeJson($feed->author->nickname)",
        "workId": "{!! $feed->work->workId !!}",
        "date": "{!! $feed->createdAt->format(config('datetime.format.default.datetime')) !!}",
        "pubTimestamp": {{ $feed->createdAt->getTimestamp() }},

  @if (in_array($feed->feedType, [$feedConstant::TYPE_WORK_COMMENT, $feedConstant::TYPE_WORK_COMMENT_DELETE]))
        "commentId": "{!! $feed->commentId !!}",
        "repliedCommentId": @if ($feed->repliedCommentId) "{!! $feed->repliedCommentId !!}" @else null @endif,
        "musicId": "{!! $feed->music->musicId !!}",
        "musicName": @if ($feed->work->workName)
                      "@escapeJson($feed->work->workName)",
                     @else
                      "@escapeJson($feed->music->name)",
                     @endif
    @if ($feed->repliedComment)
        "repliedCommentId": "{!! $feed->repliedComment->commentId !!}",
        "repliedCommentContent": "@escapeJson($feed->repliedComment->content)",
    @else
        "repliedCommentId": null,
        "repliedCommentContent": null,
    @endif
        "content": "@escapeJson($feed->content)",
        "isNormal": @if ($feed->isNormal) true @else false @endif,
  @else
        "workName": "@escapeJson($feed->work->workName)",
        "workChorusJoinInfo": {
          "workId": "{!! $feed->workChorusJoinInfo->workId !!}",
          "workName": "@escapeJson($feed->workChorusJoinInfo->workName)",
          "workDescription": "@escapeJson($feed->workChorusJoinInfo->workDescription)"
        },
  @endif
        "hasRead": @if ($feed->isRead) true @else false @endif
      } @if ( ! $loop->last) , @endif
@endforeach
    ]
  }
}
