<?php
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
return array(

	/*
	 * |---------------------------------------
	 * | Authentication System
	 * |---------------------------------------
	 *  Which authentication system should be used to provide session, 
	 *  user, group, etc control over assets
	 */
	'authentication' => 'Sentry',

	/*
	 * |---------------------------------------
	 * | Targets
	 * |---------------------------------------
	 */
	'targets' => array(
		/*
		 * Configuration option specifying which model we should use 
		 * by default. This will be loaded automatically at boot time.
		 */
		'model' => 'Phantomnet\Ldap\Targets\Eloquent\Target',
	),

	/*
	 * |---------------------------------------
	 * | Users
	 * |---------------------------------------
	 */
	'users' => array(
		/*
		 * Configuration option specifying which model we should use 
		 * by default. This will be loaded automatically at boot time.
		 */
		'model' => 'Phantomnet\Ldap\Users\Eloquent\User',

		/*
		 * Configuration option specifying which key we should use 
		 * by default to validate the username of the credentials
		 * array.
		 */
		'loginKey'      => 'username',

		/*
		 * Configuration option specifying which model we should use 
		 * by default to validate the username of the credentials
		 * array. 
		 */
		'passwordKey'   => 'password',
	),


);