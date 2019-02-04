<?php
/**
 * Transfer View
*/

namespace App\Controller\Transfer;

use Edoceo\Radix\DB\SQL;
use DateInterval;
use DateTime;
use DateTimeZone;

class View extends \OpenTHC\Controller\Base
{
	function __invoke($REQ, $RES,$ARG)
	{
		switch ($_POST['a']) {
		case 'sync':
			$C = new Sync($this->_container);
			return $C->__invoke($REQ, $RES, $ARG);
		}


		$data = array(
			'Page' => array('title' => 'Transfer :: Detail'),
			'transfer' => array(),
			'transfer_item_list' => array(),
		);

		$sql = 'SELECT * FROM crm_transfer WHERE company_id = :c AND guid = :g';
		$arg = array(':c' => $_SESSION['Company']['id'], ':g' => $ARG['guid']);
		$res = SQL::fetch_row($sql, $arg);
		$res['meta'] = json_decode($res['meta'], true);
		$data['transfer'] = $res;
		$data['transfer']['full_price'] = number_format($data['transfer']['full_price'], 2);
		$data['transfer']['lot_count'] = 0;
		$data['transfer']['package_count'] = 0;
		$data['transfer']['created_at'] = _date('m/d', $data['transfer']['created_at']);
		$data['transfer']['completed_at'] = date('m/d', $data['transfer']['completed_at']);

		$data['client_license'] = SQL::fetch_row('SELECT * FROM license WHERE id = ?', array($data['transfer']['target_license_id']));

		$L = new \App\License($data['client_license']);
		$p = $L->expandPhone();
		$data['client_license']['phone_e164'] = $p['e164'];
		$data['client_license']['phone_nice'] = $p['nice'];

		// Account Primary Contact in Target Company
		$sql = 'SELECT * FROM contact JOIN crm_license ON contact.id = crm_license.contact_id_account WHERE contact.company_id = :c AND license.id = :l';
		$arg = array(
			':c' => $_SESSION['Company']['id'],
			':l' => $data['client_license']['id'],
		);
		//$x = SQL::fetch_row($sql, $arg);


		// Account Manager Contact In My Company
		$data['manage_contact'] = array(
			'id' => 0,
			'name' => '- Not Assigned -',
		);
		// Contacts in my company
		$sql = 'SELECT * FROM contact JOIN crm_license ON contact.id = crm_license.contact_id_account WHERE contact.company_id = :c';
		$arg = array(
			':c' => $_SESSION['Company']['id'],
			//':l' => $data['client_license']['id'],
		);
		$x = SQL::fetch_row($sql, $arg);
		if (!empty($x)) {
			$data['manage_contact'] = $x;
		}


		$sql = 'SELECT * FROM crm_transfer_item WHERE transfer_id = :t ORDER BY package_qty, product, strain';
		$arg = array(':t' => $data['transfer']['id']);
		$res = SQL::fetch_all($sql, $arg);
		foreach ($res as $rec) {

			$rec['meta'] = json_decode($rec['meta'], true);
			if (preg_match('/^(.+) WA[\w\. ]$/', $rec['meta']['Item']['description'], $m)) {
				$rec['meta']['Item']['description'] = $m[1];
			}

			$rec['package_qty'] = intval($rec['package_qty']);

			$rec['full_price'] = floatval($rec['meta']['Item']['price']);

			if ($rec['package_qty']) {
				$rec['unit_price'] = $rec['full_price'] / $rec['package_qty'];
			}

			$rec['unit_price'] = number_format($rec['unit_price'], 2);
			$rec['full_price'] = number_format($rec['full_price'], 2);

			$data['transfer_item_list'][] = $rec;

			$data['transfer']['lot_count']     += 1;
			$data['transfer']['package_count'] += $rec['package_qty'];

		}

		//_exit_text($data);

		return $this->_container->view->render($RES, 'page/transfer/view.html', $data);

	}
}
