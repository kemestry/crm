<?php
/**
 * Transfer Home
 */

namespace App\Controller\Transfer;

use Edoceo\Radix\DB\SQL;

class Home extends \OpenTHC\Controller\Base
{
	function __invoke($REQ, $RES,$ARG)
	{
		$data = array(
			'Page' => array('title' => 'Transfers Overview'),
			'sync' => false,
			'transfer_list' => array(),
		);

		$sql = 'SELECT count(id) AS c, sum(full_price) AS r FROM crm_transfer WHERE company_id = :c AND (stat >= 100 AND stat < 400)';
		$arg = array(':c' => $_SESSION['Company']['id']);
		$res = SQL::fetch_row($sql, $arg);
		$data['transfer_stat'] = array(
			't_count' => $res['c'],
			'r_sum' => $res['r'],
			'r_avg' => ($res['c'] ? $res['r'] / $res['c'] : 0),
		);

		$sql = 'SELECT crm_transfer.*, license.code AS target_license_code, license.name AS target_license_name FROM crm_transfer';
		$sql.= ' JOIN license ON crm_transfer.target_license_id = license.id';
		$sql.= ' WHERE crm_transfer.company_id = :c';
		$sql.= ' AND (crm_transfer.stat >= 100 AND crm_transfer.stat < 400)';
		//$sql.= ' AND crm_transfer.completed_at IS NOT NULL';
		$sql.= ' ORDER BY created_at DESC';
		$sql.= ' LIMIT 100';
		$arg = array(':c' => $_SESSION['Company']['id']);
		$res = SQL::fetch_all($sql, $arg);
		foreach ($res as $rec) {

			$rec = \App\Transfer::inflate($rec);
			$data['transfer_list'][] = $rec;

			if (empty($rec['flag_sync'])) {
				$data['sync'] = true;
			}

		}

		return $this->_container->view->render($RES, 'page/transfer/home.html', $data);
	}
}
