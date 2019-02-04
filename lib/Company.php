<?php
/**
 * An OpenTHC CRM Company Model
*/

namespace App;

use Edoceo\Radix\DB\SQL;

class Company extends \OpenTHC\Company
{
	const TABLE = 'crm_company';

	protected $_table = 'crm_company';

	function __construct($x)
	{
		// Try crm_table else, and then load from the \OpenTHC\Contact
	}

}
