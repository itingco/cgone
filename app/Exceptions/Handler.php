<?php
namespace App\Exceptions;

use DomainException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = ['current_password','password','password_confirmation'];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {});
        $this->renderable(function (DomainException $e, Request $request) {
            if ($request->expectsJson()) return response()->json(['message'=>$e->getMessage()],422);
            return back()->withInput()->withErrors(['erp'=>$e->getMessage()]);
        });
    }
}
