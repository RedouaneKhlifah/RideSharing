<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        // Handle ModelNotFoundException directly
        if ($exception instanceof ModelNotFoundException) {
            $model = class_basename($exception->getModel());
            $modelId = $exception->getIds()[0] ?? 'unknown';

            return response()->json([
                'message' => "The requested {$model} with ID {$modelId} could not be found.",
                'errors' => [
                    $model => ["The specified {$model} does not exist in our records."]
                ]
            ], 404);
        }

        // Keep the NotFoundHttpException handler as a fallback
        if ($exception instanceof NotFoundHttpException) {
            $previousException = $exception->getPrevious();
    
            if ($previousException instanceof ModelNotFoundException) {
                $model = class_basename($previousException->getModel());
                $modelId = $previousException->getIds()[0] ?? 'unknown';
    
                return response()->json([
                    'message' => "The requested {$model} with ID {$modelId} could not be found.",
                    'errors' => [
                        'client_id' => ["The specified {$model} does not exist in our records."]
                    ]
                ], 404);
            }
        }
    
        return parent::render($request, $exception);
    }
}