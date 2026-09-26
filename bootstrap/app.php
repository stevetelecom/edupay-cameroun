<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__.'/../routes/web.php',
        api:      __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health:   '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

    $middleware->validateCsrfTokens(except: [
        'webhook/aangaraapay',
    ]);

        // Middleware alias
        $middleware->alias([
            'super.admin'       => \App\Http\Middleware\SuperAdminMiddleware::class,
            'check.abonnement'  => \App\Http\Middleware\CheckAbonnement::class,
            'role'              => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'        => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);

        // Middleware groupes
        $middleware->web(append: [
            \App\Http\Middleware\CheckMaintenanceMode::class,
        ]);

        // Audit P : SetLocale n'etait attache qu'au groupe `web`, donc l'API
        // repondait toujours en francais et `resources/lang/en` etait
        // injoignable depuis le mobile. Il est desormais global (prepend :
        // avant l'authentification et avant le rendu des exceptions) :
        //   - l'API est traduite (401/403/404/422/429) ;
        //   - les routes inconnues passent par lui aussi, donc leur 404 est
        //     lui aussi traduit (sinon le groupe `api` n'est jamais execute) ;
        //   - le groupe `web` n'a plus besoin de le declarer.
        $middleware->prepend([
            \App\Http\Middleware\SetLocale::class,
        ]);

        // SetLocale est global, donc execute AVANT le dechiffrement des
        // cookies : le cookie `locale` y serait illisible. Cette preference
        // de langue n'a rien de confidentiel, on la laisse donc en clair.
        $middleware->encryptCookies(except: [
            'locale',
        ]);

        // Securite (M-02 audit) : headers appliques a TOUTE reponse (web + api),
        // pas seulement l'espace admin comme auparavant.
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

    })
    ->withExceptions(function (Exceptions $exceptions) {

        // Réponses JSON pour l'API (frontend mobile)
        $exceptions->shouldRenderJsonWhen(function (\Illuminate\Http\Request $request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // Audit P : messages traduits via resources/lang/{fr,en}/api.php,
        // la langue etant determinee par SetLocale (?lang=, cookie,
        // Accept-Language).
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // Le message metier porte par l'exception ("Veuillez choisir
                // les frais a regler.", ...) est plus utile que le message
                // generique : on reprend donc la premiere erreur, et le
                // message generique traduit n'est utilise que si l'exception
                // n'a pas de validateur.
                $message = $e->validator?->errors()->first() ?: __('api.validation');

                return response()->json([
                    'message' => $message,
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => __('api.unauthenticated')], 401);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => __('api.not_found')], 404);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => __('api.forbidden')], 403);
            }
        });

        // 429 : trop de requetes (routes throttle:) — message tradui.
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => __('api.throttle')], 429);
            }
        });

    })
    ->create();