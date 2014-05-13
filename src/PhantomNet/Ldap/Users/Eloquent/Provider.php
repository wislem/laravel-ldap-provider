<?php namespace Phantomnet\Ldap\Users\Eloquent;
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

use Phantomnet\Ldap\Users\ProviderInterface;

class Provider implements ProviderInterface
{
	/**
	 * The Eloquent Target model
	 *
	 * @var string 
	 */
	protected $model = 'Phantomnet\Ldap\Users\Eloquent\User';

	public function __construct($model = null)
	{

		if (isset($model))
		{
			$this->model = $model;
		}
		
	}

	/**
	 * Creates a new LDAP User with the given credentials 
	 *
	 * @param array $credentials
	 * @return \Phantomnet\Ldap\Users\UserInterface
	 */
	public function create($credentials)
	{
		$user = $this->createModel();

		$user->fill($credentials);
		
		$user->save();

		return $user;
	}

	/**
	 * Create a new instance of the model.
	 *
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function createModel()
	{
		$class = '\\'.ltrim($this->model, '\\');

		return new $class;
	}

	/**
	 * Returns an empty user object.
	 *
	 * @return \Phantomnet\Ldap\Users\UserInterface
	 */
	public function getEmptyUser()
	{
		return $this->createModel();
	}

	/**
	 * Sets a new model class name to be used at
	 * runtime.
	 *
	 * @param  string  $model
	 */
	public function setModel($model)
	{
		$this->model = $model;
	}
}