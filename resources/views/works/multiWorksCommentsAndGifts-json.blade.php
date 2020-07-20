{
  "code": 0,
  "message": "",
  "data": {
    "data": [
@foreach ($data->data as $info) 
    {
      "isFavourite": @if ($info->isFavourite) true @else false @endif,
      "workId": "{!! $info->workId !!}",
      "gifts": [
  @foreach ($info->gifts as $gift)
        {
          "userId": "{!! $gift->userId !!}",
          "avatar": "@escapeJson($gift->avatar)"
        } @if ( ! $loop->last) , @endif
  @endforeach
      ],
      "comments": [
  @foreach ($info->comments as $comment)
        {
          "commentId": "{!! $comment->commentId !!}",
          "repliedCommentId": "{!! $comment->repliedCommentId !!}",
          "author": {
            "userId": "{!! $comment->author->userId !!}",
            "nickname": "@escapeJson($comment->author->nickname)"
          },
          "repliedUser": {
            "userId": "{!! $comment->repliedUser->userId !!}",
            "nickname": "@escapeJson($comment->repliedUser->nickname)"
          },
          "content": "@escapeJson($comment->content)"
        } @if ( ! $loop->last) , @endif
  @endforeach
      ]
    } @if ( ! $loop->last) , @endif
@endforeach
    ]
  } 
}
