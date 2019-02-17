<?php
/**
 * An OpenTHC CRM License Model
 */

namespace App;

use Edoceo\Radix\DB\SQL;

class License extends \OpenTHC\License
{
	const TABLE = 'crm_license';
	protected $_table =' crm_license';

	function expandPhone()
	{
		if (!empty($this->_data['phone'])) {
			$p = $this->_data['phone'];
		} else {
			$m = json_decode($this->_data['meta'], true);
			$p = $m['phone'];
			//if (empty($data['License']['phone'])) {
			//$data['License']['phone'] = SQL::fetch_one('SELECT phone FROM company WHERE id = ?', array($data))
			// @todo Lookup from Company
			//}//
		}

		return array(
			'e164' => _phone_e164($p),
			'nice' => _phone_nice($p)
		);
	}

	/**
	 * Add the crm_license Link if necessary
	 * @param [type] $L [description]
	 */
	static function import($L0)
	{
		$sql = 'SELECT * FROM crm_license WHERE company_id_owner = :c AND license_id_prime = :l';
		$arg = array(
			':c' => $_SESSION['Company']['id'],
			':l' => $L0['id'],
		);
		$res = SQL::fetch_row($sql, $arg);
		if (empty($res)) {
			SQL::insert('crm_license', array(
				'company_id_owner' => $_SESSION['Company']['id'],
				'license_id_prime' => $L0['id'],
			));
		}
	}
}
