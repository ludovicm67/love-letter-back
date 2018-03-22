<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
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
        if ($this->isHttpException($exception)) {
            $statusCode = $exception->getStatusCode();
            if ($statusCode == 401 || $statusCode == 404 || $statusCode == 405) {
              $statusMsg = $exception->getMessage();
              if (empty($statusMsg)) {
                switch ($statusCode) {
                  case 401:
                    $statusMsg = "unauthorized access (token may be invalidated)";
                    break;
                  case 404:
                    $statusMsg = "page not found";
                    break;
                  case 405:
                    $statusMsg = "method not allowed";
                    break;
                  default:
                    // do nothing
                }
              }
              return response()->json([
                  'success' => false,
                  'message' => $statusMsg
              ], $statusCode);
            }
        }
        return parent::render($request, $exception);
    }
}
