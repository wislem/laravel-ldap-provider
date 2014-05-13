<?php namespace Phantomnet\Ldap\Users\Eloquent\Sentry;
/**
 * Copyright (C) 2014  Danny Weiner <info@phantomnet.net>
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
use Phantomnet\Ldap\Users\UserInterface;
use Cartalyst\Sentry\Users\Eloquent\User as SentryUser;
use Cartalyst\Sentry\Users\LoginRequiredException;
use Cartalyst\Sentry\Users\PasswordRequiredException;
use Cartalyst\Sentry\Users\UserExistsException;

class User extends SentryUser implements UserInterface
{
	/**
	 * Eloquent model represneting LDAP enabled targets
	 *
	 * @var string
	 */
	protected static $targetModel = 'Phantomnet\Ldap\Targets\Eloquent\Target';

	public function external()
	{
		return ($this->internal() == false);
	}

	public function internal()
	{
		return ($this->source_id == 1);
	}

	public function source()
	{
		return $this->belongsTo(static::$targetModel, 'id', 'source_id');
	}

	/**
	 * Validates the user and throws a number of
	 * Exceptions if validation fails.
	 *
	 * @return bool
	 * @throws Cartalyst\Sentry\Users\LoginRequiredException
	 * @throws Cartalyst\Sentry\Users\PasswordRequiredException
	 * @throws Cartalyst\Sentry\Users\UserExistsException
	 */
	public function validate()
	{
		if (!$login = $this->{parent::$loginAttribute})
		{
			throw new LoginRequiredException("A login is required for a user, none given.");
		}

		// This is the only change .. we need to ensure we only validate passwords
		// for the internal users as LDAP doesn't store them
		if ($this->internal() and !$password = $this->getPassword())
		{
			throw new PasswordRequiredException("A password is required for user [$login], none given.");
		}

		// Check if the user already exists
		$query = $this->newQuery();
		$persistedUser = $query->where($this->getLoginName(), '=', $login)->first();

		if ($persistedUser and $persistedUser->getId() != $this->getId())
		{
			throw new UserExistsException("A user already exists with login [$login], logins must be unique for users.");
		}

		return true;
	}
}