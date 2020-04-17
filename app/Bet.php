<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bet extends Model
{
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'user', 'day', 'amount', 'locked', 'won',
	];
	
	/**
	 * Get the user who bets.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function owner()
	{
		return $this->belongsTo(User::class, 'user');
	}
	
	/**
	 * Get remaining time before bet locking.
	 *
	 * @return int
	 * @throws \Exception
	 */
	public function getRemaining()
	{
		$diff = date_diff(new \DateTime($this->created_at), new \DateTime());
		
		if ($diff->h >= 1)
			return 0;
		else
			return 60 - $diff->i;
	}
}
