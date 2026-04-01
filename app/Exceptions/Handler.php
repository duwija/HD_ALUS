<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

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
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        
        // Tangkap error koneksi DB tenant jika tenant nonaktif
        if ($exception instanceof \Illuminate\Database\QueryException) {
            // Cek apakah request domain adalah tenant nonaktif
            $host = $request->getHost();
            $tenant = null;
            try {
                $tenant = \App\Tenant::on('isp_master')->where('domain', $host)->first();
            } catch (\Exception $e) {}
            if ($tenant && isset($tenant->is_active) && ($tenant->is_active === 0 || $tenant->is_active === '0' || $tenant->is_active === false)) {
                return response()->view('errors.tenant_inactive', [], 403);
            }
        }
        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function unauthenticated($request, \Illuminate\Auth\AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Check which guard caused the exception
        $guard = $exception->guards()[0] ?? null;

        switch ($guard) {
            case 'admin':
                $login = 'admin.login';
                break;
            case 'customer':
                $login = 'customer.login';
                break;
            case 'sales':
                $login = 'sales.login';
                break;
            default:
                $login = 'login';
                break;
        }

        return redirect()->guest(route($login));
    }}