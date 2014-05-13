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

class CreateLdapUsersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('users', function($table){
			// We create the new coulmn to be used
			$table->integer('source_id')
				->unsigned()
				->default('1')
				->index();

			// Add the foreign key reference we need
			$table->foreign('source_id')
		      ->references('id')->on('ldap_targets')
		      ->onUpdate('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		if (Schema::hasColumn('users', 'source_id'))
		{
			// Reversing changes that would be made for the source_id
			// column we use
			Schema::table('users', function($table){
				// Drop the foreign key reference first
				$table->dropForeign('users_source_id_foreign');
				// Drop the index first if we have it
				$table->dropIndex('users_source_id_index');
				// Then drop the column
				$table->dropColumn('source_id');
			});
		}
		else
		{
			Schema::dropIfExists('users');
		}
	}
}
