<?php namespace Phantomnet\Ldap\Targets\Eloquent;
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

use Phantomnet\Ldap\Targets\ProviderInterface;
use Phantomnet\Ldap\Targets\TargetNotFoundException;

class Provider implements ProviderInterface
{

	/**
	 * The Eloquent Target model
	 *
	 * @var string 
	 */
	protected $model = 'Phantomnet\Ldap\Targets\Eloquent\Target';

	public function __construct($model = null)
	{

		if (isset($model))
		{
			$this->model = $model;
		}
		
	}

	/**
	 * Creates a new LDAP Target with the given credentials 
	 * and attributes
	 *
	 * @param  array $credentials
	 * @param  array $attributes
	 * @return Phantomnet\Ldap\Targets\TargetInterface
	 */
	public function create($credentials, $attributes = array())
	{
		$target = $this->createModel();

		$target->fill(array_merge($credentials, $attributes));

		$target->save();

		return $target;
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
	 * Find an authentication target by ID
	 *
	 * @param  mixed $id
	 * @return Phantomnet\Ldap\Targets\TargetNotFoundException
	 */
	public function findById($id)
	{
		$model = $this->createModel();

		if (!$target = $model->newQuery()->find($id))
		{
			throw new TargetNotFoundException("An authentication target could not be found with ID [$id]");
		}

		return $target;
	}

	/**
	 * Return an empty Target object
	 *
	 * @return Phantomnet\Ldap\Targets\TargetInterface
	 */
	public function getEmptyTarget()
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