<?php

namespace App\Http\Controllers;

use App\Bet;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
	/**
	 * Login user with given credentials.
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \Illuminate\Validation\ValidationException
	 */
	public function login(Request $request)
	{
		$this->validate($request, [
			'phone' => 'required',
			'password' => 'required',
		]);
		
		try {
			$credentials = $request->input();
			$user = User::all()->where('phone', $credentials['phone'])->first();
			
			if (is_null($user))
				return response()->json(['message' => "Numéro de téléphone ou mot de passe incorrect."], 401);

			if (Hash::check($credentials['password'], $user->password)) {
				if ($user->active) {
					$token = Crypt::encryptString($user->phone . 'rand' . Str::random(8));
					DB::table('connections')->insert([
						'phone' => $user->phone,
						'token' => $token,
						'created_at' => date('Y-m-d H:i:s'),
						'updated_at' => date('Y-m-d H:i:s'),
					]);

					return response()->json(['message' => "Bienvenue sur DayCash. Tentez votre chance, c'est peut-être votre jour.", 'user' => $user, 'token' => $token]);
				}
				else
					return response()->json(['message' => "Votre compte est désactivé ou a été supprimé. Rendez-vous dans la section Aide pour plus d'informations."], 401);
			}
			else
				return response()->json(['message' => "Numéro de téléphone ou mot de passe incorrect."], 401);
		} catch (\Exception $e) {
			return response()->json(['message' => "Une erreur est survenue lors de la connexion.", 'exception' => $e->getMessage()], 500);
		}
	}

	/**
	 * Close the session with the given token.
	 *
	 * @param string $token
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function logout(string $token)
	{
		try {
			$connection = DB::table('connections')->where('token', $token)->first();
			DB::table('connections')->delete($connection->id);

			return response()->json(['message' => "Votre session a été fermée correctement."]);
		} catch (\Exception $e) {
			return response()->json(['message' => "Une erreur est survenue lors de votre déconnexion. Veuillez réessayer.", 'exception' => $e->getMessage()], 500);
		}
	}
	
	/**
	 * Check user connection state.
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \Illuminate\Validation\ValidationException
	 */
	public function check(Request $request)
	{
		$this->validate($request, [
			'acc_token' => 'required',
			'phone' => 'required',
		]);
		
		try {
			$data = $request->input();
			$connected = DB::table('connections')->where('token', $data['acc_token'])->where('phone', $data['phone'])->first();
			
			if (is_null($connected)) {
				$connected = DB::table('connections')->where('token', $data['acc_token'])->first();
				DB::table('connections')->delete($connected->id);
				return response()->json(['message' => "Votre session a été fermée. Veuillez vous reconnecter."], 401);
			}
			
			$clear_token = Crypt::decryptString($data['acc_token']);
			$phone = explode('rand', $clear_token)[0];
			
			if ($phone == $connected->phone) {
				$user = User::with(['bets'])->where('phone', $connected->phone)->first();
				$actual_bet = DB::table('bets')
					->where('user', $user->id)
					->whereDate('created_at', '>=', date('Y-m-d'))
					->first();
				
				if ($actual_bet != null) {
					$actual_bet = Bet::find($actual_bet->id);
					$actual_bet->remaining = $actual_bet->getRemaining();
				}
				
				$user->actual_bet = $actual_bet;
				
				return response()->json(['user' => $user, 'token' => $data['acc_token']]);
			}
			else {
				DB::table('connections')->delete($connected->id);
				return response()->json(['message' => "Votre session a été fermée. Veuillez vous reconnecter."], 401);
			}
		} catch (\Exception $e) {
			return response()->json(['message' => "Une erreur est survenue.", 'exception' => $e->getMessage()], 500);
		}
	}
}
