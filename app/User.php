<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
	use Authenticatable, Authorizable;
	
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'name', 'phone', 'password', 'points', 'active', 'email', 'city',
	];
	
	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = [
		'password',
	];
	
	/**
	 * The casted attributes.
	 *
	 * @var array
	 */
	protected $casts = [
		'active' => 'boolean'
	];
	
	/**
	 * Get the bets of the user.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function bets()
	{
		return $this->hasMany(Bet::class, 'user');
	}
}
