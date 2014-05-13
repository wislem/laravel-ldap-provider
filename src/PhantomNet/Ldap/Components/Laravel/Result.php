<?php namespace Phantomnet\Ldap\Components\Laravel;
/**
 *
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