<?php namespace Phantomnet\Ldap;
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
use Phantomnet\Ldap\Bridge\ProviderInterface as BridgedProviderInterface;
use Phantomnet\Ldap\Connections\Laravel\Provider as ConnectionProvider;
use Phantomnet\Ldap\Connections\ProviderInterface as ConnectionProviderInterface;
use Phantomnet\Ldap\Connections\LdapConnectionException;
use Phantomnet\Ldap\Users\Eloquent\Provider as UserProvider;
use Phantomnet\Ldap\Users\ProviderInterface as UserProviderInterface;
use Phantomnet\Ldap\Users\AuthenticationException;
use Phantomnet\Ldap\Users\UserNotFoundException;
use Phantomnet\Ldap\Targets\Eloquent\Provider as TargetProvider;
use Phantomnet\Ldap\Targets\ProviderInterface as TargetProviderInterface;

class Ldap
{
	/**
	 * The Bridge Provider used for retrieving
	 * objects which implment the Authentication interface
	 *
	 * @var \Phantomnet\Ldap\Bridge\ProviderInterface
	 */
	protected $authProvider;

	/**
	 * The Connection Provider used for retrieving
	 * objects which implment the Connection interface
	 *
	 * @var \Phantomnet\Ldap\Connections\ProviderInterface
	 */
	protected $connectionProvider;

	/**
	 * The User Provider used for retrieving
	 * objects which implment the User interface
	 *
	 * @var \Phantomnet\Ldap\Users\ProviderInterface
	 */
	protected $userProvider;

	/**
	 * The Target Provider used for retrieving
	 * objects which implment the Target interface
	 *
	 * @var \Phantomnet\Ldap\Targets\ProviderInterface
	 */
	protected $targetProvider;

	public function __construct(
		BridgedProviderInterface    $authProvider,
		UserProviderInterface       $userProvider   = null,
		TargetProviderInterface     $targetProvider = null,
		ConnectionProviderInterface $connProvider   = null) 
	{
		$this->authProvider       = $authProvider;
		$this->userProvider       = $userProvider   ?: new UserProvider;
		$this->targetProvider     = $targetProvider ?: new TargetProvider;
		$this->connectionProvider = $connProvider   ?: new ConnectionProvider;
	}

	/**
	 * Attempt to authenticate the user with LDAP
	 *
	 * @var    $target      Phantomnet\Ldap\Targets\TargetInterface
	 * @var    $credentials array
	 * @return Phantomnet\Ldap\Users\UserInterface
	 * @throws Phantomnet\Ldap\Users\AuthenticationException
	 */
	public function authenticate($target, $credentials)
	{
		// Validate the credential array we're given
		$this->authProvider->validate($credentials);

		/* Assuming we validate our credentials we need to establish a 
		 * connection to the LDAP target, and bind the values to check
		 * for a valid login */
		$connection = LDAP::getConnectionProvider()
			->create($target);

		// Should return a laravel collection (Eventually)
		$results = $connection->authenticate($credentials);
		
		// Results represent the entry object we used as data FROM the LDAP server
		return $results[0];
	}

	/**
	 * @return Phantomnet\Ldap\Bridge\ProviderInterface
	 */
	public function getAuthProvider()
	{
		return $this->authProvider;
	}

	/**
	 * @return \Phantomnet\Ldap\Users\ProviderInterface
	 */
	public function getUserProvider()
	{
		return $this->userProvider;
	}

	/**
	 * @return \Phantomnet\Ldap\Targets\ProviderInterface
	 */
	public function getTargetProvider()
	{
		return $this->targetProvider;
	}

	/**
	 * @return Phantomnet\Ldap\Connections\ProviderInterface
	 */
	public function getConnectionProvider()
	{
		return $this->connectionProvider;
	}

	/**
	 *
	 */
	public function onTheFlyCreation($credentials, $remember)
	{
		/* Validate the credential key */
		// $loginCredentialKey = $this->authProvider->getLoginAttribute();
		$loginCredentialKey = 'email';
		$this->authProvider->validate($credentials, $loginCredentialKey);

		// We need to fetch all targets which allow user creation on the fly
		// Sadly there is no option better than a linear search
		$targets = $this->targetProvider->getEmptyTarget()
			->where('creation', '=', '1')->get();

		// If no targets support on-the-fly creation we can error out
		// now before we process further
		if (! $targets->first())
		{
			throw new UserNotFoundException('No user found');
		}

		// I say it again .. sigh .. linear search
		foreach ($targets as $target)
		{
			// We have to check EVERY target that allows for users creation
			// like this. We can use the first user that exists with a positive 
			// bind
			try
			{
				// This will throw an exception in the event that something happens
				// if it progresses beyond then we have a valid user and will create
				// it as we go
				$results = LDAP::authenticate($target, $credentials);

				// Generate a list of knonw attributes that the model
				// must have to be successfully created
				$user = array(
			        $loginCredentialKey => $credentials[$loginCredentialKey],
			        'source_id'         => $target->id,
			        'activated'         => 1
			    );

				// This object should be pulled form the cache and reusable
			    $connection = LDAP::getConnectionProvider()
					->create($target);

			    // now we need to generate a list of attributes we retrieve
			    // from LDAP as specified by the LDAP Target configuration
			    $attributes = array();

			    $links = $target->getMetadata();

			    // This function is significantly dirtier than I wnated for the first run
			    // we have to convert the attributes we know of to the databse keys
			    // we will be using
			    // Afterwords we can build an attribute array
			    foreach ($connection->getAttributes() as $field => $value)
			    {
			    	// create the field name
			    	$field = substr($field, strlen($connection->getAttributePrefix()));
			    	// We need to verify that the field exists
			    	$attribute = isset($results[strtolower($value)]) ? $results[strtolower($value)] : false;
			    	// Check that the attribute was set and contains a relevent count
			    	if ($attribute and isset($attribute['count']) and $attribute['count'] > 0)
			    	{
						if (isset($links[$field]))
						{
							$field = $links[$field];
						}

						$attributes[$field] = $attribute[0];
			    	}
			    }

				// Generate a new user and activate it
				$user = LDAP::getUserProvider()
					->create(array_merge($user, $attributes));

				// Login and build the session
			    LDAP::getAuthProvider()
				    ->login($user, $remember);

				// We return the user to keep the API standardized
			    return $user;
			}
			catch (UserNotFoundException $e)
			{
				// Do nothing .. just move on
			}
		}

		throw new UserNotFoundException('No user found');
	}

	/**
	 *
	 */
	public function testConnection($target)
	{
		return LDAP::getConnectionProvider()
			->create($target)->ping();
	}
}