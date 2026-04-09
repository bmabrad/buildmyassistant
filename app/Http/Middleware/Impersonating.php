<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Impersonating
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->session()->has('impersonating_from')) {
            return $response;
        }

        if (! $response instanceof \Illuminate\Http\Response) {
            return $response;
        }

        $content = $response->getContent();

        if (! str_contains($content, '</body>')) {
            return $response;
        }

        $user = $request->user();
        $name = e($user?->name ?? 'Unknown');
        $email = e($user?->email ?? '');

        $banner = <<<HTML
        <div id="impersonation-banner" style="position:fixed;top:0;left:0;right:0;z-index:9999;background:#f59e0b;color:#1E2A38;padding:10px 20px;display:flex;align-items:center;justify-content:center;gap:16px;font-family:'Inter',sans-serif;font-size:14px;font-weight:500;box-shadow:0 2px 8px rgba(0,0,0,0.15);">
            <span>You are viewing as {$name} ({$email})</span>
            <form method="POST" action="/admin/stop-impersonating" style="margin:0;">
                <input type="hidden" name="_token" value="{$request->session()->token()}">
                <button type="submit" style="background:#1E2A38;color:white;border:none;padding:6px 16px;border-radius:6px;font-family:'Inter',sans-serif;font-size:13px;font-weight:500;cursor:pointer;">Stop impersonating</button>
            </form>
        </div>
        <style>#impersonation-banner ~ * { margin-top: 0; } body { padding-top: 48px; }</style>
        HTML;

        $content = str_replace('</body>', $banner . '</body>', $content);
        $response->setContent($content);

        return $response;
    }
}
