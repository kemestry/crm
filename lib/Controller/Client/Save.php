<?php
/**
 * Client Save
 */

namespace App\Controller\Client;

use Edoceo\Radix\DB\SQL;

class Save extends \OpenTHC\Controller\Base
{
	function __invoke($REQ, $RES,$ARG)
	{

		$sql = 'SELECT * FROM license WHERE code = ?';
		$arg = array($ARG['guid']);
		$res = SQL::fetch_row($sql, $arg);
		if (empty($res['id'])) {
			_exit_text('Not Found', 404);
		}

		$L = $res;

		switch ($_POST['a']) {
		case 'update-route':
			$sql = 'SELECT * FROM crm_license WHERE company_id_owner = :c0 AND license_id_prime = :l0';
			$arg = array(
				':c0' => $_SESSION['Company']['id'],
				':l0' => $L['id'],
			);
			$rec = SQL::fetch_row($sql, $arg);
			if (empty($rec)) {
				// Create
			} else {
				$rec['meta'] = json_decode($rec['meta']);
				$rec['meta']['dist_s'] = $_POST['s'];
				$rec['meta']['dist_m'] = $_POST['m'];
				$rec['meta']['cost_per_hour'] = $_POST['ct'];
				$rec['meta']['cost_per_mile'] = $_POST['cm'];
				$rec['meta'] = json_encode($rec['meta']);
				SQL::query('UPDATE crm_license SET meta = :m WHERE id = :id', array(
					':id' => $rec['id'],
					':m' => json_encode($rec['meta'])
				));
			}

			_exit_json(array(
				'status' => 'success',
			));

			break;
		}
	}
}
