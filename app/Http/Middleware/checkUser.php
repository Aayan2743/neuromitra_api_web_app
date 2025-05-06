<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;


class checkUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $checkrole=auth()->user()->role;

        if($checkrole!="User"){
            return response()->json(
                [
                    'status'=>false,
                    'message'=> 'You Dont have User Access',
                ],
               200);
        }

        return $next($request);

    }
}
