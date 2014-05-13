<?php

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
