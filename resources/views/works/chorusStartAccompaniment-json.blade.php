{
  "code": 0,
  "message": "",
  "data": {
    "author": {
      "userId": "{!! $data->work->author->userId !!}",
      "avatar": "@escapeJson($data->work->author->avatar)"
    },
    "resource": "@escapeJson($data->work->resource)"
  }
}
