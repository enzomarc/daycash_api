<?php

namespace App\Http\Middleware;

use App\User;
use Closure;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class AuthMiddleware
{
	/**
	 * Handle an incoming request.
	 *
	 * @param $request
	 * @param Closure $next
	 * @return \Illuminate\Http\JsonResponse|mixed
	 * @throws \Exception
	 */
	public function handle($request, Closure $next)
	{
		if (!$request->hasHeader('Authorization'))
			return response()->json(['message' => 'Authorization Header not found.'], 401);
		
		$token = $request->header('Authorization');
		
		if ($token == null)
			return response()->json(['message' => 'No token provided.'], 401);
		
		try {
			$connection = DB::table('connections')->where('token', $token)->first();
			
			if ($connection != null) {
				$decrypted = Crypt::decryptString($token);
				$supposed_phone = $connection->phone;
				$supposed_phone_size = strlen($supposed_phone);
				$given_phone = substr($decrypted, 0, $supposed_phone_size);
				
				if ($given_phone == $supposed_phone)
					return $next($request);
				else
					return response()->json(['message' => 'Invalid token provided.'], 401);
			} else {
				return response()->json(['message' => 'Invalid token provided.'], 401);
			}
		} catch (\Exception $e) {
			return response()->json(['message' => $e->getMessage()], 401);
		}
	}
}
