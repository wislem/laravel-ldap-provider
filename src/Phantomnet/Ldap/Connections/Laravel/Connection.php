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

use LDAP;
use Phantomnet\Ldap\Connections\ConnectionInterface;
use Phantomnet\Ldap\Connections\LdapConnectionException;
use Phantomnet\Ldap\Users\UserNotFoundException;
use Phantomnet\Ldap\Users\WrongPasswordException;

class Connection implements ConnectionInterface
{
    /**
     * Default value for timeout of an LDAP server connection
     */
    const DEFAULT_LDAP_TIMEOUT = 10;

	/**
	 * Default port for non-SSL connections
	 */
	const LDAP_PORT = '389';

	/**
	 * Default port for LDAPS connections
	 */
	const SSL_LDAP_PORT = '636';

    /**
     * LDAP protocol prefix
     */
    const LDAP_PREFIX = 'ldap://';

    /**
     * LDAPS protocol proefix
     */
    const LDAPS_PREFIX = 'ldaps://';

    /**
     * Known LDAP error codes
     */
    const LDAP_INVALID_CREDENTIALS = 49;

	/**
	 * Connection resource to the LDAP server
	 *
	 * @var mixed
	 */
	protected $ldapConnection;

	/**
	 * List of attributes to retrieve from LDAP
	 *
	 * @var array
	 */
	protected $attributes = array();

    /** 
     * List of metadata attributes to retrieve from the database
     *
     * @var array
     */
    protected $metadata = array('firstname', 'lastname', 'email');

	/**
	 * Prefix used by the databse columns. It will be added to the
	 * attribute from the list above
	 *
	 * @var string
	 */
	protected static $attribute_prefix = 'attr_';

	/**
	 * Attribute to be used for logins
	 *
	 * @var string
	 */
	protected $loginAttribute = 'login';

    /** 
     * Credentials to access the LDAP target
     *
     * @var array
     */
    protected $credentials = array();

	/**
	 * Hostname of the LDAP server being connected to
	 *
     * @var string
	 */
	protected $hostname;

    /**
     * Port of the LDAP server being connected to
     *
     * @var int
     */
    protected $port;

    /**
     * State flag for LDAPS
     *
     * @var bool
     */
    protected $secure;

    /**
     * Base distinguished name for the LDAP directory
     *
     * @var string
     */
    protected $base;

    /**
     * Filter used for processing attribute return values from the
     * LDAP directory
     *
     * @var string
     */
    protected $filter;

    /** 
     * Timeout period for attempting to connect to an LDAP server
     *
     * @var int
     */
    protected $timeout = self::DEFAULT_LDAP_TIMEOUT;

	/**
	 * Constructor method for the connection class
	 *
     * @var $target \Phantomnet\Ldap\Targets\TargetInterface
	 */
	public function __construct($target, $loginAttribute = null)
	{
        $attribute = static::$attribute_prefix . 'login';
        $this->loginAttribute = $loginAttribute ?: $target->$attribute;

        // Define the basic instance variable
		$this->hostname = $target->hostname;
        $this->port     = $target->port;
        $this->secure   = (bool)$target->secure;
        $this->base     = $target->base;
        $this->filter   = $target->filter;

        // Represents how long we should wiat for a failed connection attempt
        $this->timeout  = $target->timeout ?: static::DEFAULT_LDAP_TIMEOUT;

        // SKipped handling of credentials for now
        $this->credentials = array();

        // Run through the attributes we know of and
        // add them to the known attributes list. We get them from the target
        // model
        foreach ($this->metadata as $attribute)
        {
            // Add the prefix value to the attribute
            $field = static::$attribute_prefix . $attribute;

            // Save the field we have in the database if one exsists
            if ($value = $target->$field)
            {
                $this->attributes[$field] = $value;
            }             
        }
	}

	/**
     * Default Destructor
     * 
     * Closes the LDAP connection
     * 
     * @return void
     */
	function __destruct() 
	{ 
        $this->close(); 
    }

    /**
     * Attempt to authenticate the user with LDAP
     *
     * @var    $credentials array
     * @return array
     * @throws Phantomnet\Ldap\Connections\LdapConnectionException
     * @throws Phantomnet\Ldap\Users\AuthenticationException
     * @throws Phantomnet\Ldap\Users\WrongPasswordException
     * @throws Phantomnet\Ldap\Users\LoginRequiredException
     * @throws Phantomnet\Ldap\Users\PasswordRequiredException
     */
    public function authenticate($credentials)
    {
        // Validate the credential array we're given
        LDAP::getAuthProvider()
            ->validate($credentials);

        // Authentication components needed for the credentials
        $loginCredentialKey = LDAP::getAuthProvider()
            ->getLoginAttribute();
        $passwordCredentialKey = LDAP::getAuthProvider()
            ->getPasswordAttribute();

        // Create a new search filter and retrieve the results
        $filter = sprintf('%s=%s', $this->loginAttribute,
            $credentials[$loginCredentialKey]);
        $results = $this->search($filter, $this->attributes);
        // Attempt to bind the resulting matches with the password we retrieved
        // form the credential array
        $this->bind($results[0]['dn'], $credentials[$passwordCredentialKey]);

        // FInally return the results
        return $results;
    }

    /**
     * Bind the LDAP COnnection to the given parameters
     *
     * @return bool
     * @throws \Phantomnet\Ldap\Connections\LdapConnectionException
     */    
    public function bind($dn = null, $password = null)
    {
        if (! $bind = @ldap_bind($this->ldapConnection, $dn, $password))
        {
            $errno  = @ldap_errno($this->ldapConnection);
            $errstr = @ldap_err2str($errno);

            switch ($errno)
            {
                case self::LDAP_INVALID_CREDENTIALS:
                    throw new WrongPasswordException('Unable to validate user credentials');
                break;
            }

            throw new LdapConnectionException(sprintf('Failed bind on Target [%s] :: %s',
                $errno, $errstr));
        }

        return $bind;
    }

    /**
    * Closes the LDAP connection
    * 
    * @return void
    */
    public function close() 
    {
        if ($this->ldapConnection) 
        {
            \ldap_close($this->ldapConnection);
        }
    }

    /**
    * Connects and Binds to the Domain Controller
    * 
    * @return bool
    */
    public function connect() 
    {
    	// Might be worth throwing an exception here
    	if ($this->ldapConnection)
    	{
    		return true;
    	}

        $hostname = $this->toString();

        // Initiate a connection to the server
        $this->ldapConnection = @ldap_connect($hostname, $this->port);

        if (!$this->ldapConnection)
        {
            $error = error_get_last();

            throw new LdapConnectionException('Failed to connect to LDAP Target ['.$error.']');
        }
    }

    /**
     * Return the list of attributes used in the LDAP filter
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Returns the attribute prefix
     *
     * @return string
     */
    public function getAttributePrefix()
    {
        return static::$attribute_prefix;
    }

	/**
	 * Return the target model we used to connect with
	 *
	 * @return \Phantomnet\Ldap\Targets\TargetInterface
	 */
	public function getConnectionTarget()
	{
		return $this->target;
	}

	/**
     * Get the active LDAP Connection
     * 
     * @return resource
     */
	public function getLdapConnection()
    {
    	if ($this->ldapConnection) 
    	{
            return $this->ldapConnection;   
        }

        return false;
    }

	/**
     * Detect LDAP support in php
     * 
     * @return bool
     */    
    protected function ldapSupported()
    {
        if (!function_exists('ldap_connect')) 
        {
            return false;
        }

        return true;
    }

	/** 
     * Test basic connectivity to controller 
     * 
     * @return bool
     */
    public function ping()
    {
    	if (!$this->ldapConnection)
    	{
    		throw new LdapConnectionException('ping() - Connection has not been initialized yet');
    	}

        $socket = @fsockopen($this->hostname, $this->port, 
        	$errno, $errstr, 10); 

        if ($socket)
        {
        	@fclose($socket);
        }

        if ($errno > 0) 
        {
            return false;
        }

        return true;
    }

    /**
     * Search for users within the directory with the given 
     * attribute.
     *
     * @param $filter string
     * @param $attributes array
     * @return bool|mixed
     */
    public function search($filter, $attributes = array())
    {
        $result = ldap_search($this->ldapConnection, $this->base, $filter, 
            array_values($attributes));

        if (!$result)
        {
            // echo '<pre>';

            // var_dump($this->ping());

            // var_dump($this);

            $errno  = @ldap_errno($this->ldapConnection);
            $errstr = @ldap_err2str($errno);

            return false;

            // var_dump($errno, $errstr);

            // die('</pre>');
        }
        

        $results = ldap_count_entries($this->ldapConnection, $result);

        $entries = ldap_get_entries($this->ldapConnection, $result);

        return $entries;
    }

    /**
     * Set the attribute prefix to be used in creating the LDAP
     * attributes on the fly target server.
     *
     * @param $prefix string
     * @return void
     */
    public static function setAttributePrefix($prefix)
    {
    	static::$attribute_prefix = $prefix;
    }

    /**
     * Set the attribute to be used for logging in to the LDAP
     * target server.
     *
     * @param $prefix string
     * @return void
     */
    public static function setLoginAttribute($attribute)
    {
    	static::$loginAttribute = $attribute;
    }

    /**
     * Convert the hostname into a protocol readable string
     *
     * @return string
     */
    public function toString()
    {
        if ($this->secure)
        {
            return static::LDAPS_PREFIX . $this->hostname;
        }

        return static::LDAP_PREFIX . $this->hostname;
    }
}
