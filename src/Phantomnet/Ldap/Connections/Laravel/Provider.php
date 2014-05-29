<?php namespace Phantomnet\Ldap\Connections\Laravel;
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

use \Phantomnet\Ldap\Connections\ProviderInterface;

class Provider implements ProviderInterface
{
	/**
	 * The Laravel Connection object
	 *
	 * @var string
	 */
	protected $connection = 'Phantomnet\Ldap\Connections\Laravel\Connection';

	/**
	 * Cache of availabe connections that are currently active
	 *
	 * @var array
	 */
	protected $connections = array();

	/**
	 * Default destructor for the Connection provider
	 */
	function __destruct()
	{
		foreach ($this->connections as $index => $connection)
		{
			// Run the clos emethod incase it already hasn't been run
			$connection->close();

			// Remove from the cache
			unset($this->connections[$index]);
		}
	}

	/**
	 * Create a new connection and automatically initiate the connection
	 *
	 * @return \Phantomnet\Ldap\Connections\Laravel\Connection
	 */
	public function create($target)
	{
		if (isset($this->connections[$target->base]))
		{
			return $this->connections[$target->base];
		}
		
		$connection = $this->createConnection($target);

		$this->connections[$target->base] = $connection;

		$connection->connect();

		return $connection;
	}

	/**
	 * Create a new instance of the model.
	 *
	 * @return Phantomnet\Ldap\Targets\TargetInterface
	 */
	public function createConnection($target)
	{
		$class = '\\'.ltrim($this->connection, '\\');

		return new $class($target);
	}

	/**
	 * Sets a new connection class name to be used at
	 * runtime.
	 *
	 * @param  string  $connection
	 */
	public function setConnection($connection)
	{
		$this->connection = $connection;
	}
}