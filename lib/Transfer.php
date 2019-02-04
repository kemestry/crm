<?php
/**
 * An OpenTHC CRM Transfer Model
*/

namespace App;

use Edoceo\Radix\DB\SQL;

class Transfer extends \OpenTHC\SQL\Record
{
	const TABLE = 'crm_transfer';

	const FLAG_SYNC = 0x00100000;

	const FLAG_SAMPLE = 0x00000080;

	protected $_table = 'crm_transfer';

	function __construct($x)
	{
		// Try crm_table else, and then load from the \OpenTHC\Contact
	}
}
