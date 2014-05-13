<?php namespace Phantomnet\Ldap\Targets\Eloquent;
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

use Illuminate\Database\Eloquent\Model;
use Phantomnet\Ldap\Targets\TargetInterface;

class Target extends Model implements TargetInterface
{
	/**
	 * The Eloquent user model.
	 *
	 * @var string
	 */
	protected static $userModel = 'Phantomnet\Ldap\Users\Eloquent\User';

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'ldap_targets';

	/**
	 *	Elements fillable by the create method
	 *
	 * @var array
	 */
	protected $fillable = array('name', 'hostname', 'port', 'secure', 'account', 
		'password', 'base', 'filter', 'timeout', 'creation', 'attr_login', 
		'attr_firstname', 'attr_lastname', 'attr_email');

	/**
	 * Elements that should not be shown serialized
	 *
	 * @var array
	 */
	protected $hidden = array('username', 'password');

	/**
	 * Timestamps should be maintained
	 *
	 * @var bool
	 */
	public $timestamps = true;

	/**
	 * Returns the metadata link information as an array
	 * Used to map attributes via the LDAP target to the respective attribute
	 * on the user model.
	 *
	 * @return array
	 */
	public function getMetadata()
	{
		if (isset($this->attributes['metadata']))
		{
			return @json_decode($this->metadata, true);
		}

		return array();
	}

	/** 
	 * Define the one-to-many relationship between the target and
	 * the users it manages
	 */
	public function users()
	{
		return $this->hasMany(static::$userModel, 'source_id', 'id');
	}
}