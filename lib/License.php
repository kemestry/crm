<?php
/**
 * An OpenTHC CRM Company Model
*/

namespace App;

class License extends \OpenTHC\Company
{
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
}
