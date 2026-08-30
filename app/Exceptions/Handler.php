<?php

namespace App\Exceptions;

use DomainException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (DomainException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()
                ->withInput()
                ->withErrors([
                    'erp' => $e->getMessage(),
                ]);
        });
    }

    public function render($request, Throwable $e)
    {
        if ($e instanceof TokenMismatchException) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Session expired. Refresh the page and sign in again.',
                ], 419);
            }

            return redirect()
                ->route('login')
                ->with(
                    'warning',
                    'Sesi Anda telah berakhir. Silakan login kembali.'
                );
        }

        return parent::render($request, $e);
    }
}