<?php namespace Phantomnet\Ldap\Components\Laravel;
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

use Phantomnet\Ldap\Components\ResultInterface;

class Result implements ResultInterface
{
	/**
	 * List of attributes that this result has available
	 */
	protected $attributes = array();

	/** 
	 * LDAP Connection that generated this result set
	 */
	protected $connection;

	/** 
	 * Creates a new LDAP data result
	 *
	 * @var $attributes array
	 * @return void
	 */
	public function __construct(array $attributes = array())
	{

	}
}