<?php namespace Phantomnet\Ldap\Bridge\Sentry\Laravel;
/**
 * Copyright (C) 2014  Danny Weiner <info@phantomnet.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

use Config;
use LDAP;
use Phantomnet\Ldap\Bridge\ProviderInterface;
use Phantomnet\Ldap\Users\UserInterface;
use Phantomnet\Ldap\Users\LoginRequiredException;
use Phantomnet\Ldap\Users\PasswordRequiredException;
use Phantomnet\Ldap\Users\UserNotFoundException;
use Phantomnet\Ldap\Users\WrongPasswordException;
/* Sentry Exceptions */
use Sentry;
use Cartalyst\Sentry\Users\UserNotFoundException as SentryUserNotFoundException;
use Cartalyst\Sentry\Users\WrongPasswordException as SentryWrongPasswordException;
use Cartalyst\Sentry\Users\UserNotActivatedException;

class Provider implements ProviderInterface
{
	/**
	 * Login attribute to use for usernames
	 *
	 * @var string
	 */
	protected $loginKey;

	/**
	 * Password attribute to use for passwords
	 *
	 * @var string
	 */
	protected $passwordKey;

	/**
	 * Default constructor
	 *
	 * @var $attribute String slug to use as the login username key
	 *                 within a credentials array
	 */
	public function __construct($loginKey = null, $passwordKey = null)
	{
		$this->loginKey = $loginKey 
			? $loginKey : Config::get('ldap::users.loginKey');

		$this->passwordKey = $passwordKey 
			? $passwordKey : Config::get('ldap::users.passwordKey');
	}

	/**
	 * Authenticate a set of credentials using the built in  functions included
	 * within the Sentry 2 authentication package with enchanced LDAP features.
	 *
	 * @var $credentials array
	 * @var $remember bool
	 * @throws Phantomnet\Ldap\Users\UserNotFoundException
	 * @throws Phantomnet\Ldap\Users\WrongPasswordException
	 * @throws Phantomnet\Ldap\Users\LoginRequiredException
	 * @throws Phantomnet\Ldap\Users\PasswordRequiredException
	 */
	public function authenticate($credentials, $remember)
	{
		// Validate the credential array that is passed to us for authentication
		$this->validate($credentials);

		$loginCredentialKey = $this->getLoginAttribute();

		// Now comes the mess. We need to prioritize the LDAP authentication 
		// if it's available and then try the Sentry internal login.
		try
		{
			// If throttling is enabled, we'll firstly check the throttle.
			// This will tell us if the user is banned before we even attempt
			// to authenticate them
			if ($throttlingEnabled = Sentry::getThrottleProvider()->isEnabled())
			{
				$throttle = Sentry::getThrottleProvider()
					->findByUserLogin($credentials[$loginCredentialKey], Sentry::getIpAddress());

				if ($throttle)
				{
					$throttle->check();
				}
			}

			$user = Sentry::getUserProvider()
				->findByLogin($credentials[$loginCredentialKey]);

			// This is way to hacky.
			if ($user->external())
			{
				$target = LDAP::getTargetProvider()
					->findById($user->source_id);

				// This is hackier.
				LDAP::authenticate($target, $credentials);
			}
			else 
			{
				// If all else fails we can try to authenticate internally
				$user = Sentry::getUserProvider()
					->findByCredentials($credentials);		
			}						
		}
		catch (SentryUserNotFoundException $e)
		{
			if ($throttlingEnabled and isset($throttle))
			{
				$throttle->addLoginAttempt();
			}

			if ($e instanceof SentryWrongPasswordException)
			{
				throw new WrongPasswordException($e->getMessage());
			}

			throw new UserNotFoundException($e->getMessage());
		}

		if ($throttlingEnabled and isset($throttle))
		{
			$throttle->clearLoginAttempts();
		}

		$user->clearResetPassword();

		$this->login($user, $remember);

		return Sentry::getUser();
	}

	/**
	 * Retrieve the login attribute
	 *
	 * @return string
	 */
	public function getLoginAttribute()
	{
		return $this->loginKey;
	}

	/**
	 * Retrieve the password attribute
	 *
	 * @return string
	 */
	public function getPasswordAttribute()
	{
		return $this->passwordKey;
	}

	/**
	 * Alias to the Sentry::login command
	 * Create sthe session using the Sentry package as a backend including the 
	 * sessions and other utilities.
	 *
	 * @var $user Phantomnet\Ldap\Users\UserInterface
	 * @var $remember bool
	 */
	public function login(UserInterface $user, $remember)
	{
		return Sentry::login($user, $remember);
	}

	/**
	 * Set a new login attribute
	 *
	 * @var string
	 */
	public function setLoginAttribute($attribute)
	{
		$this->loginKey = $attribute;
	}

	/**
	 * Validate a credentials array ha sthe required fields set.
	 * Always use validation or ensure the array is set programatically. This does
	 * not validate values, merely their existance.
	 *
	 * @var $credentials array
	 * @var $loginCredentialKey string
	 * @var $passwordCredentialKey string
	 * @throws Phantomnet\Ldap\Users\LoginRequiredException
	 * @throws Phantomnet\Ldap\Users\PasswordRequiredException
	 */
	public function validate($credentials, $loginCredentialKey = null, $passwordCredentialKey = null)
	{
		if (!$loginCredentialKey)
		{
			$loginCredentialKey = $this->getLoginAttribute();
		}

		if (!$passwordCredentialKey)
		{
			$passwordCredentialKey = $this->getPasswordAttribute();
		}

		if (empty($credentials[$loginCredentialKey]))
		{
			throw new LoginRequiredException("The [$loginCredentialKey] attribute is required.");
		}

		if (empty($credentials['password']))
		{
			throw new PasswordRequiredException('The password attribute is required.');
		}

		return true;
	}
}