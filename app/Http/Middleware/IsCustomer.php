<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Modul menggunakan role == 2 (enum numerik lama).
        // Pada proyek ini kolom users.role bertipe enum string: admin|superadmin|customer,
        // maka pengecekan disesuaikan menjadi 'customer'.
        if (auth()->check() && auth()->user()->role == 'customer') {
            return $next($request);
        }

        return redirect('/auth/redirect')->with('msgError', 'Anda harus login sebagai customer');
    }
}
