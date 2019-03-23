<?php
/**
 * An OpenTHC CRM Contact Model
*/

namespace App;

use Edoceo\Radix\DB\SQL;

class Contact extends \OpenTHC\Contact
{
	const TABLE = 'crm_contact';

	protected $_table = 'crm_contact';

	function __construct($x)
	{
		// Try crm_table else, and then load from the \OpenTHC\Contact
	}

	// function findBy($q, $f)
	// {
	// 	if (is_array($q)) {
	// 		if (!empty($q['company_id'])) {
	//
	// 		}
	// 	}
	// }
}
