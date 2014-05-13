<?php namespace Phantomnet\Ldap;
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

use InvalidArgumentException;
use Illuminate\Support\ServiceProvider;
use Phantomnet\Ldap\Ldap as LDAP;
use Phantomnet\Ldap\Connections\Laravel\Provider as ConnectionProvider;
use Phantomnet\Ldap\Users\Eloquent\Provider as UserProvider;
use Phantomnet\Ldap\Targets\Eloquent\Provider as TargetProvider;
/* Bridge Providers */
use Phantomnet\Ldap\Bridge\Sentry\Laravel\Provider as SentryAuthenticationProvider;

class LdapServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('phantomnet/ldap');
	}

	/**
	 * Bootstrap the Athentication provider
	 *
	 * @return void
	 */
	protected function registerAuthProvider()
	{
		$this->app['ldap.auth'] = $this->app->share(function($app){

			$auth = $app['config']->get('ldap::authentication');

			switch ($auth)
			{
				case 'Sentry':
					return new SentryAuthenticationProvider;
				break;
			}

			throw new InvalidArgumentException('Invalid authenticator bridge');
		});
	}

	/**
	 * Register the target provider.
	 *
	 * @return void
	 */
	protected function registerConnectionProvider()
	{
		$this->app['ldap.connection'] = $this->app->share(function($app){
			return new ConnectionProvider();
		});
	}

	/**
	 * Register the user provider.
	 *
	 * @return void
	 */
	protected function registerUserProvider()
	{
		$this->app['ldap.user'] = $this->app->share(function($app){

			$model = $app['config']->get('ldap::users.model');

			return new UserProvider($model);
		});
	}

	/**
	 * Register the target provider.
	 *
	 * @return void
	 */
	protected function registerTargetProvider()
	{
		$this->app['ldap.target'] = $this->app->share(function($app){

			$model = $app['config']->get('ldap::targets.model');

			return new TargetProvider($model);
		});
	}

	/**
	 * Register the new LDAP handler.
	 *
	 * @return void
	 */
	protected function registerLdapProvider()
	{
		$this->app['ldap'] = $this->app->share(function($app){
			return new LDAP($app['ldap.auth'], $app['ldap.user'], 
				$app['ldap.target']);
		});
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerAuthProvider();
		$this->registerConnectionProvider();
		$this->registerUserProvider();
		$this->registerTargetProvider();
		$this->registerLdapProvider();
	}
}
