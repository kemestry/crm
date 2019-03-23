<?php
/**
 * Client View
 */

namespace App\Controller\Client;

use Edoceo\Radix\DB\SQL;

class View extends \App\Controller\Base
{
	function __invoke($REQ, $RES,$ARG)
	{
		$data = array(
			'Page' => array('title' => 'Client :: Detail'),
			'date_iso' => strftime('%Y-%m-%d'),
			'Origin_License' => $_SESSION['License'],
			'cost_per_hour' => 0,
			'cost_per_mile' => 0,
			'contact_list'  => $this->getContactList(),
		);

		$sql = 'SELECT * FROM license WHERE code = ?';
		$arg = array($ARG['guid']);
		$res = SQL::fetch_row($sql, $arg);
		if (empty($res['id'])) {
			_exit_text('Not Found', 404);
		}


		$data['License'] = $res;
		$data['Target_License'] = $res;

		switch ($_GET['v']) {
		case 'contact-list':
			return $this->_contact_list($RES, $data);
		case 'journal-list':
			return $this->_journal_list($RES, $data);
		}

		$L = new \App\License($data['License']);
		$p = $L->expandPhone();
		$data['License']['phone_e164'] = $p['e164'];
		$data['License']['phone_nice'] = $p['nice'];

		$c = new \OpenTHC\Company($data['License']['company_id']);
		$c['meta'] = json_decode($c['meta'], true);
		$c['weblink_meta'] = json_decode($c['weblink_meta'], true);
		$data['Company'] = $c;
		// $data['Company']['weblink_meta']['website']

		$data['Contact0'] = array(
			'name' => '-Not Set-',
			'link' => '/contact/create?' . http_build_query(array('company_id' => $data['Company']['id'], 'role' => 'Primary')),
		);
		$data['Contact1'] = array(
			'name' => '-Not Set-',
			'link' => '/contact/create?' . http_build_query(array('company_id' => $data['Company']['id'], 'role' => 'Buyer'))
		);
		$data['Manager'] = array(
			'name' => '- Not Set -',
			'link' => '/contact/create?' . http_build_query(array('company_id' => $_SESSION['Company']['id'])),
		);

		// Stat Client Transfers
		$sql = 'SELECT count(distinct(crm_transfer.id)) AS c';
		$sql.= ', sum(crm_transfer_item.full_price) AS r';
		$sql.= ', count(crm_transfer_item.id) AS l';
		$sql.= ', sum(crm_transfer_item.package_qty) AS u';
		$sql.= ' FROM crm_transfer';
		$sql.= ' LEFT JOIN crm_transfer_item ON crm_transfer.id = crm_transfer_item.transfer_id';
		$sql.= ' WHERE crm_transfer.company_id = :c AND target_license_id = :l';
		$sql.= ' AND crm_transfer.stat IN (301, 307)'; // 100, 200,
		$arg = array(
			':c' => $_SESSION['Company']['id'],
			':l' => $data['License']['id']
		);

		$res = SQL::fetch_row($sql, $arg);
		$data['transfer_stat'] = array(
			'revenue' => number_format($res['r'], 2),
			't_count' => $res['c'],
			'l_count' => $res['l'],
			'u_count' => number_format($res['u'], 2),
		);

		$data = $this->_stat_transfer_avgs($data);


		// Show all Client Transfers
		$sql = 'SELECT crm_transfer.guid, crm_transfer.flag, crm_transfer.created_at, crm_transfer.completed_at, crm_transfer.stat, crm_transfer.full_price, license.code AS target_license_code, license.name AS target_license_name';
		$sql.= ', count(crm_transfer_item.id) AS lot_count';
		$sql.= ' FROM crm_transfer';
		$sql.= ' JOIN license ON crm_transfer.target_license_id = license.id';
		$sql.= ' LEFT JOIN crm_transfer_item ON crm_transfer.id = crm_transfer_item.transfer_id';
		$sql.= ' WHERE crm_transfer.company_id = :c AND target_license_id = :l';
		//$sql.= ' AND crm_transfer.stat IN (100, 200, 301, 307)';
		$sql.= ' GROUP BY crm_transfer.guid, crm_transfer.flag, crm_transfer.created_at, crm_transfer.completed_at, crm_transfer.stat, crm_transfer.full_price, license.code, license.name';
		$sql.= ' ORDER BY created_at DESC';
		$arg = array(
			':c' => $_SESSION['Company']['id'],
			':l' => $data['License']['id']
		);

		$res = SQL::fetch_all($sql, $arg);
		foreach ($res as $rec) {
			$rec = \App\Transfer::inflate($rec);
			$data['transfer_list'][] = $rec;
		}

		$C = new \OpenTHC\Company($_SESSION['Company']);
		$data['cost_per_hour'] = floatval($C->opt('cost_per_hour'));
		$data['cost_per_mile'] = floatval($C->opt('cost_per_mile'));


		//return $this->_container->pwig->render($RES, 'page/client/view.php', $data);
		return $this->_container->view->render($RES, 'page/client/view.html', $data);

	}

	function _stat_transfer_avgs($data)
	{
		// Stat Client Transfers
		$sql = 'SELECT count(distinct(crm_transfer.id)) AS c';
		$sql.= ', sum(crm_transfer_item.full_price) AS r';
		$sql.= ', count(crm_transfer_item.id) AS l';
		$sql.= ', sum(crm_transfer_item.package_qty) AS u';
		$sql.= ', min(created_at) AS min_created_at';
		$sql.= ', max(created_at) AS max_created_at';
		$sql.= ' FROM crm_transfer';
		$sql.= ' LEFT JOIN crm_transfer_item ON crm_transfer.id = crm_transfer_item.transfer_id';
		$sql.= ' WHERE crm_transfer.company_id = :c AND target_license_id = :l';
		$sql.= ' AND crm_transfer.stat IN (100, 200, 301, 307)';
		$arg = array(
			':c' => $_SESSION['Company']['id'],
			':l' => $data['License']['id']
		);

		$res = SQL::fetch_row($sql, $arg);

		$d0 = new \DateTime($res['min_created_at']);
		$d1 = new \DateTime($res['max_created_at']);

		$dX = $d0->diff($d1);
		//var_dump($dX);

		//var_dump($res);
		//$data['transfer_stat']['a_']
		// $data['transfer_stat']['age'] =
		$data['transfer_stat']['avg_days'] = '-';
		$data['transfer_stat']['avg_lot_count'] = '-';
		$data['transfer_stat']['avg_unit_count'] = '-';
		$data['transfer_stat']['avg_revenue'] = '-.--';

		if ($res['c'] > 0) {
			$data['transfer_stat']['avg_days'] = floor($dX->days / $res['c']);
			$data['transfer_stat']['avg_lot_count']  = floor($res['l'] / $res['c']);
			$data['transfer_stat']['avg_unit_count'] = floor($res['u'] / $res['c']);
			$data['transfer_stat']['avg_revenue']    = number_format($res['r'] / $res['c'], 2);
		}

		return $data;
	}

	function _contact_list($RES, $data)
	{
		$sql = 'SELECT * FROM crm_contact WHERE company_id_owner = :c0 AND company_id = :c1';
		$arg = array(
			':c0' => $_SESSION['Company']['id'],
			':c1' => $data['License']['company_id'],
		);
		$res = SQL::fetch_all($sql, $arg);
		//var_dump($res);

		$data['contact_list'] = $res;

		return $this->_container->view->render($RES, 'block/contact-list.html', $data);
	}

	function _journal_list($RES, $data)
	{
		$sql = 'SELECT * FROM crm_journal WHERE company_id_owner = :c0 AND company_id_about = :c1';
		// JOIN
		$arg = array(
			':c0' => $_SESSION['Company']['id'],
			':c1' => $data['License']['company_id'],
		);
		$res = SQL::fetch_all($sql, $arg);

		$data['journal_list'] = array();
		foreach ($res as $rec) {
			$rec['date_nice'] = _date('m/d', $rec['execute_at']);
			$data['journal_list'][] = $rec;
		}

		// $res;

		return $this->_container->view->render($RES, 'block/journal-list.html', $data);
	}
}
