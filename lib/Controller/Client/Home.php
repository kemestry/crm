<?php
/**
 * Client Home
 */

namespace App\Controller\Client;

use Edoceo\Radix\DB\SQL;

class Home extends \OpenTHC\Controller\Base
{
	function __invoke($REQ, $RES,$ARG)
	{
		$data = array(
			'Page' => array('title' => 'Client :: Overview'),
			'client_list' => array(),
			'type_active' => $_GET['type'] ?: 'a',
			'type_link_a' => '?' . \http_build_query(array_merge($_GET, ['type' => 'a'])),
			'type_link_g' => '?' . \http_build_query(array_merge($_GET, ['type' => 'g'])),
			'type_link_p' => '?' . \http_build_query(array_merge($_GET, ['type' => 'p'])),
			'type_link_r' => '?' . \http_build_query(array_merge($_GET, ['type' => 'r'])),
			'view_active' => $_GET['view'] ?: 'm',
			'view_link_m' => '?' . \http_build_query(array_merge($_GET, ['view' => 'm'])),
			'view_link_a' => '?' . \http_build_query(array_merge($_GET, ['view' => 'a'])),
			'view_link_p' => '?' . \http_build_query(array_merge($_GET, ['view' => 'p'])),
			'view_link_d' => '?' . \http_build_query(array_merge($_GET, ['view' => 'd'])),
		);

		$sql = 'SELECT license.id, license.name, license.code';
		$sql.= ', count(crm_transfer.id) AS c';
		$sql.= ', sum(crm_transfer.full_price) AS r';
		$sql.= ', max(crm_transfer.completed_at) AS d';
		$sql.= ' FROM crm_transfer';
		$sql.= ' JOIN license ON crm_transfer.target_license_id = license.id';
		//$sql.= ' LEFT JOIN crm_client ON crm_transfer.target_license_id = crm_client.license_id_about';
		$sql.= ' WHERE crm_transfer.company_id = :c '; // AND crm_client.company_id_owner = :c';
		//$sql.= ' AND crm_transfer.full_price > 0';
		switch ($_GET['view']) {
		case 'a':
			$sql.= " AND crm_transfer.completed_at >= now() - '3 months'::interval AND crm_transfer.stat NOT IN (410)";
			break;
		case 'd': // Drifting
			$sql = 'SELECT license.id, license.name, license.code';
			$sql.= ', count(crm_transfer.id) AS c';
			$sql.= ', sum(crm_transfer.full_price) AS r';
			$sql.= ', max(crm_transfer.completed_at) AS d';
			$sql.= ' FROM crm_transfer';
			$sql.= ' JOIN license ON crm_transfer.target_license_id = license.id';
			//$sql.= ' LEFT JOIN crm_client ON crm_transfer.target_license_id = crm_client.license_id_about';
			$sql.= ' WHERE crm_transfer.company_id = :c '; // AND crm_client.company_id_owner = :c';
			$sql.= " AND crm_transfer.completed_at <= now() - '3 months'::interval";
			break;
		case 'p': // Prospects
			// Filter for Only
			$tab = sprintf('license_prospect_%08x', crc32(\json_encode($_SESSION)));
			$sql = "CREATE TEMPORARY TABLE $tab (license_id bigint)";
			SQL::query($sql);

			$sql = "INSERT INTO $tab (SELECT DISTINCT target_license_id FROM crm_transfer WHERE company_id = :c AND full_price = 0)";
			SQL::query($sql, array(':c' => $_SESSION['Company']['id']));

			$sql = "DELETE FROM $tab WHERE license_id IN (SELECT target_license_id FROM crm_transfer WHERE company_id = :c AND full_price > 0)";
			SQL::query($sql, array(':c' => $_SESSION['Company']['id']));

			$res = SQL::fetch_all("SELECT * FROM $tab");
			//var_dump($res);
			//exit;

			$sql = 'SELECT license.id, license.name, license.code';
			$sql.= ', count(crm_transfer.id) AS c';
			$sql.= ', sum(crm_transfer.full_price) AS r';
			$sql.= ', max(crm_transfer.completed_at) AS d';
			$sql.= ' FROM crm_transfer';
			$sql.= ' JOIN license ON crm_transfer.target_license_id = license.id';
			//$sql.= ' LEFT JOIN crm_client ON crm_transfer.target_license_id = crm_client.license_id_about';
			$sql.= ' WHERE crm_transfer.company_id = :c '; // AND crm_client.company_id_owner = :c';
			$sql.= " AND target_license_id IN (SELECT license_id FROM $tab)";

			break;

		}

		$sql.= ' GROUP BY license.id, license.name, license.code';
		$sql.= ' ORDER BY d DESC, r, c';
		//$sql.= ' HAVING r > 0';
		$arg = array(':c' => $_SESSION['Company']['id']);
		$res = SQL::fetch_all($sql, $arg);

		foreach ($res as $rec) {
			$rec['d'] = _date('m/d/y', $rec['d']);
			$rec['r'] = number_format($rec['r'], 2);
			$data['client_list'][] = $rec;
		}

		return $this->_container->view->render($RES, 'page/client/home.html', $data);
	}
}
