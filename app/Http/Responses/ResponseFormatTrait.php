<?php

namespace SingPlus\Http\Responses;


use Illuminate\Http\Response;
use SingPlus\Exceptions\ExceptionCode;
use SingPlus\Exceptions\AppException;

trait ResponseFormatTrait
{
  /**
   * Check accept content-type, then return related
   * response content in terms of content-type
   *
   * Notice: template path will be parsed by template name
   *         and response contentType, the rule as below:
   *         templatePath = templateName-respContentType.blade.php
   *         for example common.navigation with json format will be
   *         resource/views/common/navigation-json.blade.php
   *
   * @param string $template  template name
   * @param array $data       data need by template
   * @param string $message
   * @param int $code
   *
   * @return \Illuminate\Http\Response
   */
  protected function render(
    string $template,
    array $data = [],
    string $message = '',
    int $code = 0
  ) : Response {
    return $this->genResponse($template, [
      'code'      => $code,
      'data'      => (object) $data,
      'message'   => $message,
    ]);
  }

  /**
   * Response info message
   *
   * @param string $message
   *
   * @return \Illuminate\Http\Response
   */
  protected function renderInfo(string $message, ?array $data = null) : Response {
    return $this->genResponse('infos.info', [
      'code'      => 0,
      'data'      => $data ?: (object) [],
      'message'   => $message,
    ]);
  }

  /**
   * Responses error  message
   *
   * @param \Exception|string $data   exception or error message
   * @param int $code         error code
   *
   * @return \Illuminate\Http\Response
   */
  protected function renderError($data, int $code = ExceptionCode::GENERAL) : Response
  {
    $template = 'errors.custom';
    if ($data instanceof \Exception) {
      // only AppException's code be keeped
      if ($data instanceof AppException) {
        $code = $data->getCode();
      }

      $message = $data->getMessage();

      // in case debug enabled
      if (config('app.debug')) {
        $exception['trace'] = $data->getTrace();
        $exception['line'] = $data->getLine();
        $exception['file'] = $data->getFile();

        return $this->genResponse($template, [
          'code'      => $code,
          'data'      => (object) [],
          'message'   => $message,
          'exception' => empty($exception) ? [] : $exception,
        ]);
      }
    }

    return $this->genResponse($template, [
      'code'      => $code,
      'data'      => (object) [],
      'message'   => $data,
    ]);
  }

  /**
   * Get response format and contentType
   */
  protected function getResponseFormat() : array
  {
    static $respFormats = [
      'json'      => 'application/json',
      'xml'       => 'text/xml',
      'txt'       => 'text/plain',
      'html'      => 'text/html',
      'jsonp'     => 'text/plain',
    ];
    $request = request();
    // add jsonp mimetype supporting
    $request->setFormat('jsonp', 'application/jsonp');
    $respFormat = $request->format();
    if ( ! isset($respFormats[$respFormat])) {
      $respFormat = 'json';
    }
    $respContentType = $respFormats[$respFormat];

    return [$respFormat, $respContentType];
  }

  /**
   * Generate response
   *
   * @param string $template  template name
   * @param array $data
   *
   * @return \Illuminate\Http\Response
   */
  protected function genResponse($template, array $data)
  {
    list($respFormat, $respContentType) = $this->getResponseFormat();
    $template = rtrim($template, '.') . '-' . $respFormat;
    return response()
      ->view($template, [
        'code'      => $data['code'],
        'data'      => $data['data'],
        'message'   => $data['message'],
        'exception' => isset($data['exception']) ? $data['exception'] : [],
      ])
      ->header('Content-Type', $respContentType);
  }
}
