<?php

namespace App\Http\Controllers;

use App\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
	/**
	 * Get users accounts.
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index()
	{
		$users = User::all();
		return response()->json(['users' => $users]);
	}

	/**
	 * Get user account.
	 *
	 * @param int $user
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show(int $user)
	{
		try {
			$user = User::findOrFail($user);
			return response()->json(['user' => $user]);
		} catch (\Exception $e) {
			return response()->json(['message' => "Impossible de retrouver l'utilisateur.", 'exception' => $e->getMessage()], 500);
		}
	}

	/**
	 * Store a newly created user.
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \Illuminate\Validation\ValidationException
	 * @throws \Throwable
	 */
	public function store(Request $request)
	{
		$this->validate($request, [
			'name' => 'required',
			'phone' => 'required',
		]);

		try {
			$data = $request->input();
			$exists = User::all()->where('phone', $data['phone'])->first() != null;

			if (!$exists) {
				$password = Str::random(8);
				$data['password'] = Hash::make($password);

				$user = new User($data);
				$user->saveOrFail();
				
				$client = new Client();
				
				// Send password to user via SMS
				$message = "Bienvenue sur DayCash, votre compte a été créé avec succès. Votre mot de passe est: " . $password;
				$phone = Str::startsWith($data['phone'], ['237', '+237']) ? $data['phone'] : '237' . $data['phone'];
				$client->get("http://obitsms.com/api/bulksms?username=" . env('SMS_USER') . "&password=" . env('SMS_PASSWORD') . "&sender=" . env('SMS_SENDER') . "&destination=" . $phone . "&message=" . $message);

				return response()->json(['message' => "Votre compte a été ouvert avec succès. Vous recevrez le mot de passe par message sous peu.", 'user' => $user], 201);
			} else {
				return response()->json((['message' => "Vous possédez déjà un compte, veuillez vous connecter."]), 500);
			}
		} catch (\Exception $e) {
			return response()->json(['message' => "Une erreur est survenue lors de la création du compte.", 'exception' => $e->getMessage()], 500);
		}
	}

	/**
	 * Update user account.
	 *
	 * @param Request $request
	 * @param int $user
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function update(Request $request, int $user)
	{
		try {
			$user = User::findOrFail($user);
			$data = $request->input();

			if (isset($data['password']))
				// Send password to user via SMS?
				$data['password'] = Hash::make($data['password']);

			$user->update($data);

			return response()->json(['message' => "Votre compte a été mis à jour avec succès.", 'user' => $user]);
		} catch (\Exception $e) {
			return response()->json(['message' => "Une erreur est survenue lors de la mise à jour de votre compte.", 'exception' => $e->getMessage()], 500);
		}
	}

	/**
	 * Delete user account.
	 *
	 * @param int $user
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function destroy(int $user)
	{
		try  {
			$user = User::findOrFail($user);
			$user->update(['active' => 0]);
			// Delete or deactivate user account. Also delete related.

			return response()->json(['message' => "Votre compte a été supprimé avec succès."]);
		} catch (\Exception $e) {
			return response()->json(['message' => "Une erreur est survenue lors de la suppression de votre compte.", 'exception' => $e->getMessage()], 500);
		}
	}

	/**
	 * Determine if user with given phone exists.
	 *
	 * @param string $phone
	 * @return bool
	 */
	public function exists(string $phone)
	{
		$user = User::all()->where('phone', $phone)->first();
		return $user != null;
	}
}
