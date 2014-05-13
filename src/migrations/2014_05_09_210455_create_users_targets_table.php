<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLdapUsersTargetsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users_targets', function($table){
			$table->engine = 'InnoDB';

			$table->integer('user_id')
				->unsigned();
			$table->integer('target_id')
				->unsigned();

			$table->primary(array('user_id', 'target_id'));
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExsists('users_targets');
	}
}
