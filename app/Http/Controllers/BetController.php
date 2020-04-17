<?php

namespace App\Http\Controllers;

use App\Bet;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BetController extends Controller
{
	/**
	 * Get all bets.
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index()
	{
		$bets = Bet::all();
		return response()->json(['bets' => $bets]);
	}
	
	/**
	 * Get bet data.
	 *
	 * @param int $bet
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show(int $bet)
	{
		try {
			$bet = Bet::findOrFail($bet);
			$bet->remaining = $bet->getRemaining();
			
			return response()->json(['bet' => $bet]);
		} catch (\Exception $e) {
			return response()->json(['message' => "Impossible de retrouver cette mise.", 'exception' => $e->getMessage()], 500);
		}
	}
	
	/**
	 * Store user bet.
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \Throwable
	 */
	public function store(Request $request)
	{
		try {
			$this->validate($request, [
				'user' => 'required',
				'day' => 'required',
				'amount' => 'required',
			]);
			
			$data = $request->input();
			$user = User::findOrFail($data['user']);
			
			// Check if user exists
			if (is_null($user))
				return response()->json(['message' => "Impossible de retrouver le compte effectuant la mise. Vous m'avez piraté?"], 500);
			
			// Check if user active
			if (!$user->active)
				return response()->json(['message' => "Votre compte est désactivé, vous ne pouvez pas miser. Contactez un administrateur."], 500);
			
			// Check bet amount before save
			if ($data['amount'] < 300)
				return response()->json(['message' => "Le montant minimum d'une mise est de 300 points."], 500);
			
			
			$actual_bet = DB::table('bets')
				->where('user', $user->id)
				->whereDate('created_at', '>=', date('Y-m-d'))
				->first();
			
			if ($actual_bet != null) {
				$actual_bet = Bet::find($actual_bet->id);
				
				if ($actual_bet->getRemaining() > 0) {
					$user->update(['points' => $user->points + $actual_bet->amount]);
					
					// Check if user have points
					if ($user->points < $data['amount']) {
						$user->update(['points' => $user->points - $actual_bet->amount]);
						return response()->json(['message' => "Vous n'avez pas assez de points pour miser."], 500);
					}
					
					$actual_bet->update($data);
					$user->update(['points' => $user->points - $data['amount']]);
					
					return response()->json(['message' => "Votre mise a été modifiée avec succès.", 'bet' => $actual_bet]);
				} else {
					return response()->json(['message' => "Les mises sont verrouillées, vous ne pouvez plus les modifier."], 500);
				}
			} else {
				// Check if user have points
				if ($user->points < $data['amount'])
					return response()->json(['message' => "Vous n'avez pas assez de points pour miser."], 500);
				
				$bet = new Bet($data);
				$saved = $bet->saveOrFail();
				
				if ($saved)
					$user->update(['points' => $user->points - $data['amount']]);
				
				return response()->json(['message' => "Votre mise a été enregistrée avec succès. Croisons les doigts.", 'bet' => $bet], 201);
			}
		} catch (\Exception $e) {
			return response()->json(['message' => "Une erreur est survenue lors de l'enregistrement de votre mise. Veuillez réessayer.", 'excetion' => $e->getMessage()], 500);
		}
	}
	
	/**
	 * Update user bet.
	 *
	 * @param Request $request
	 * @param int $bet
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function update(Request $request, int $bet)
	{
		try {
			$bet = Bet::with(['owner'])->findOrFail($bet);
			$data = $request->input();
			
			if (isset($data['user']))
				return response()->json(['message' => "Il est impossible de mettre à jour l'utilisateur ayant fait une mise."], 500);
			
			if ($bet->locked)
				return response()->json(['message' => "Votre mise a déjà été validée. Il est impossible de la modifier."], 500);
			
			$bet->update($data);
			return response()->json(['message' => "Votre mise a été mise à jour avec succès.", 'bet' => $bet], 200);
		} catch (\Exception $e) {
			return response()->json(['message' => "Une erreur est survenue lors de la mise à jour de votre mise.", 'exception' => $e->getMessage()], 500);
		}
	}
	
	/**
	 * Delete user bet.
	 *
	 * @param int $bet
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function destroy(int $bet)
	{
		try {
			$bet = Bet::findOrFail($bet);
			$bet->delete();
			
			if ($bet->locked)
				return response()->json(['message' => "Votre mise a déjà été validée. Il est impossible de la supprimer."], 500);
			
			return response()->json(['message' => "Votre mise a été supprimée avec succès."]);
		} catch (\Exception $e) {
			return response()->json(['message' => "Une erreur est survenue lors de la suppression de votre mise.", 'exception' => $e->getMessage()], 500);
		}
	}
	
	/**
	 * Get specific user bets.
	 *
	 * @param int $user
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function bets(int $user)
	{
		try {
			$user = User::with(['bets'])->findOrFail($user);
			return response()->json(['bets' => $user->bets]);
		} catch (\Exception $e) {
			return response()->json(['message' => "Une erreur est survenue lors de l'obtention de vos mises.", 'exception' => $e->getMessage()], 500);
		}
	}
	
	/**
	 * Get days winning percentages.
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function percents()
	{
		try {
			$monday = DB::table('bets')->whereDate('created_at', '>=', date('Y-m-d'))->where('day', 1)->count();
			$tuesday = DB::table('bets')->whereDate('created_at', '>=', date('Y-m-d'))->where('day', 2)->count();
			$wednesday = DB::table('bets')->whereDate('created_at', '>=', date('Y-m-d'))->where('day', 3)->count();
			$thursday = DB::table('bets')->whereDate('created_at', '>=', date('Y-m-d'))->where('day', 4)->count();
			$friday = DB::table('bets')->whereDate('created_at', '>=', date('Y-m-d'))->where('day', 5)->count();
			$saturday = DB::table('bets')->whereDate('created_at', '>=', date('Y-m-d'))->where('day', 6)->count();
			$sunday = DB::table('bets')->whereDate('created_at', '>=', date('Y-m-d'))->where('day', 7)->count();
			$bets = DB::table('bets')->whereDate('created_at', '>=', date('Y-m-d'))->count();
			
			$arr = [
				'1' => round(100 - ($monday * 100 / $bets), 2),
				'2' => round(100 - ($tuesday * 100 / $bets), 2),
				'3' => round(100 - ($wednesday * 100 / $bets), 2),
				'4' => round(100 - ($thursday * 100 / $bets), 2),
				'5' => round(100 - ($friday * 100 / $bets), 2),
				'6' => round(100 - ($saturday * 100 / $bets), 2),
				'7' => round(100 - ($sunday * 100 / $bets), 2),
			];
			
			return response()->json(['percents' => $arr]);
		} catch (\Exception $e) {
			return response()->json(['message' => "Impossible d'obtenir les pourcentages de gains des jours."], 500);
		}
	}
}
