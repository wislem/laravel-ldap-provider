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

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLdapTargetsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ldap_targets', function($table){
			$table->engine = 'InnoDB';

			$table->increments('id');

			$table->timestamps();
			$table->softDeletes();

			$table->string('name', 32);
			$table->string('hostname', 64);
			$table->smallInteger('port')
				->unsigned();
			$table->boolean('secure')
				->default('0');

			// Login credentials for LDAP
			$table->string('account', 32);
			$table->string('password', 64);
			$table->string('base', 155);
			$table->string('filter', 255);
			$table->tinyInteger('timeout')
				->unsigned();
			// Used for user-creation-on-the-fly detection
			$table->boolean('creation')
				->default('0')
				->index();

			// LDAP Attributes
			$table->string('attr_login', 32);
			$table->string('attr_email', 64);
			$table->string('attr_firstname', 32);
			$table->string('attr_lastname', 32);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('ldap_targets');
	}

}
