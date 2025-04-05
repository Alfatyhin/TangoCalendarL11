<?php

namespace App\Http\Middleware;

use App\Http\Controllers\ApiController;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiRequestSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $data = $request->all();
        if (!ApiController::signedCheck($data)) {
            $date = new Carbon();


            $res = [
                'success' => false,
                'errorMessage' => 'error signed',
                'timeSighed' => $date->format('Y-m-d-H')
            ];

            print json_encode($res);
            die;
        }
        return $next($request);
    }
}
