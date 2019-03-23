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
	const FLAG_MUTE   = 0x04000000;
	const FLAG_DEAD   = 0x08000000;

	protected $_table = 'crm_transfer';

	function __construct($x)
	{
		// Try crm_table else, and then load from the \OpenTHC\Contact
	}

	static function inflate($rec)
	{
		$flag_html = array();
		switch ($rec['stat']) {
		case 410:
			$flag_html[] = '<i class="fas fa-ban"></i>';
			break;
		}

		if ($rec['flag'] & \App\Transfer::FLAG_SAMPLE) {
			$flag_html[] = '<i class="fas fa-flask"></i>';
		}

		$rec['guid_nice'] = preg_match('/\.IT(\w{3,10})$/', $rec['guid'], $m) ? $m[1] : $rec['guid'];
		$rec['date_nice'] = _date('m/d', $rec['completed_at']);
		$rec['meta'] = json_decode($rec['meta'], true);
		$rec['flag_html'] = implode(' ', $flag_html);
		$rec['flag_sync'] = ($rec['flag'] & \App\Transfer::FLAG_SYNC);
		$rec['full_price'] = \number_format($rec['full_price'], 2);

		return $rec;
	}
}
