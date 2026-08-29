<?php
namespace App\Http\Middleware;
use App\Services\Security\MenuAuthorizationService; use Closure; use Illuminate\Http\Request; use Symfony\Component\HttpFoundation\Response;
class EnsureMenuPermission { public function __construct(private MenuAuthorizationService $authorization){} public function handle(Request $request, Closure $next, string $menuCode, string $permissionCode='view'): Response { abort_unless($request->user() && $this->authorization->allows($request->user(),$menuCode,$permissionCode),403); return $next($request); } }
