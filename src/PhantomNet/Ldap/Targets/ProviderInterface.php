<?php namespace Phantomnet\Ldap\Targets;
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

interface ProviderInterface 
{
	/**
	 * Creates a new LDAP Target with the given credentials 
	 * and attributes
	 *
	 * @param array $credentials
	 * @param array $attributes
	 * @return \Phantomnet\Ldap\Targets\TargetInterface
	 */
	public function create($credentials, $attributes);

	/**
	 * Create a new instance of the model.
	 *
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function createModel();

	/**
	 * Sets a new model class name to be used at
	 * runtime.
	 *
	 * @param  string  $model
	 */
	public function setModel($model);
}