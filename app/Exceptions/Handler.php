<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use App\Libraries\SAPb1\SAPException;
use Throwable;
use App\Exceptions\CustomValidationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {

        $this->renderable(function (Exception $exception, $request) {
            if (!$request->wantsJson()) {
                return null; // Laravel handles as usual
            }

            throw CustomValidationException::withMessages(
                $exception->validator->getMessageBag()->getMessages()
            );
        });

        $this->reportable(function (Throwable $e) {
            //

        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        if ($request->wantsJson()) {
            return parent::prepareJsonResponse($request, $exception);
        }

        return parent::render($request, $exception);
    }
    /**
     * Convert a validation exception into a JSON response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Validation\ValidationException  $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function invalidJson($request, ValidationException $exception)
    {
        // You can return json response with your custom form
        return response()->json([
            'success' => false,
            'data' => [
                'code' => $exception->status,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors()
            ]
        ], $exception->status);
    }

}
