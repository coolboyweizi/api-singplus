<?php

namespace SingPlus\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use SingPlus\Http\Responses\ResponseFormatTrait;

class Handler extends ExceptionHandler
{
    use ResponseFormatTrait;

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
        \SingPlus\Exceptions\Users\UserNewException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
      try {
        if ($exception instanceof ValidationException) {
          $error = $exception->validator->errors()->first();
          return $this->renderError($error, ExceptionCode::ARGUMENTS_VALIDATION);
        }
        if ($exception instanceof AuthenticationException) {
          return $this->renderError($exception, ExceptionCode::USER_UNAUTHENTICATION);
        }
        if ($exception instanceof \ErrorException) {
          return $this->renderError($exception, ExceptionCode::SYNTAX_ERROR);
        }

        return $this->renderError($exception);
      } catch (Exception $exception) {
        return parent::render($request, $exception);
      }
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest(route('login'));
    }
}
