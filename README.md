LDAP Authentication Provider
============================

The goal of this project is to connect with existing Authentication systems and provide connectivity
and access via LDAP without implementing it's own session control system. Built natively into this
package is support for cartalyst's [Sentry](http://cartalyst.com/manual/sentry) package to provide
authentication services.

If you would like to connect your authentication system just send a pull request and your code will
be revied for entry! :-)

Supported Authentication Systems
--------------------------------

The supported authentication systems are:

* [Cartalyst Sentry 2](http://cartalyst.com/manual/sentry)

Configuration
-------------

Open your composer.json file and add the following to the require array:

```
"phantomnet/laravel-ldap-provider": "dev-master"
```

After modifying the composer.json file make sure to validate it:

```
composer validate
```

Install the dependencies and packages with the following:

```
composer install
```

Or

```
composer update
```

**Laravel 4**

The following configuration options go through the **app/config/app.php** file

In the **providers** array add the following:

```
/* LDAP Provider */
'Phantomnet\Ldap\LdapServiceProvider',
```

Within the aliases array add the following:

```
/* LDAP Auth Provider */
'LDAP'   => 'Phantomnet\Ldap\Facades\Laravel\Ldap',
```

**Migrations**

```
php artisan migrate --package=phantomnet/ldap
```

**Configuration**

```
php artisan config:publish phantomnet/ldap
```

This will publish **app/config/packages/phantomnet/ldap/config.php** where it 
can be modified.

Usage
-----

The goal was to reuse as much existing code as possible without having to create 
separate authentication controls like session management, so integrating natively 
into existing auth solution is provided via a bridge.

The following example is the logic behind authenticating with LDAP and should be 
placed in a controller that handles logins. Please note this is **NOT** a complete
example and should only be used as a model! 

Using Laravel 4 syntaxing.


```
$credentials = array(
    /* this is the login attribute. See the 
     * configuration file for properly changing it */
	'email'    => Input::get('username'), 
	'password' => Input::get('password')
);

$remember = (bool)Input::has('remember-me');

try
{
	/* Attempt to authenticate the user with the given credentials */
	LDAP::getAuthProvider()->authenticate($credentials, $remember);

	/* Assuming login worked, we can route to the admin dashboard */
	return Redirect::intended('admin');
}
catch (Phantomnet\Ldap\users\WrongPasswordException $e)
{
	// If we get to this point, just return an error
    return Redirect::route('login') // named route "login"
        ->withInput()
        ->with('error', 'Incorrect username or password.');
}
catch (Phantomnet\Ldap\Users\UserNotFoundException $e)
{
	/* This will cover a wrong password as well */
	try
	{
		/* Assuming we have any targets with on-the-fly creation 
		 * allowed we can try to login a new user here and then 
		 * redirect them */
		LDAP::onTheFlyCreation($credentials, $remember);

		/* If we made it here .. we found a valid user and 
		 * attempted to authenticate them */
		return Redirect::intended('admin');
	}
	catch (Phantomnet\Ldap\Users\UserNotFoundException $e) 
	{
		// We do nothing in here
	}

	// If we get to this point, just return an error
    return Redirect::route('login') // named route "login"
        ->withInput()
        ->with('error', 'Incorrect username or password.');
}
```
