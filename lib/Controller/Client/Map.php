<?php
/**
 * Client Home
 */

namespace App\Controller\Client;

use Edoceo\Radix\DB\SQL;

class Map extends \OpenTHC\Controller\Base
{
	function __invoke($REQ, $RES,$ARG)
	{
		if ('list' == $_GET['a']) {
			$sql = <<<EOS
SELECT DISTINCT license.name AS license_name
, license.geo_lat
, license.geo_lon
, license.code AS license_code
, license.guid AS license_guid
, license.type AS license_type
, license.address_full
, crm_license.meta AS license_meta
, company.name AS company_name
FROM license
JOIN crm_license ON license.id = crm_license.license_id_prime
JOIN crm_transfer ON crm_transfer.target_license_id = license.id
JOIN company ON license.company_id = company.id
WHERE crm_license.company_id_owner = :c0 AND crm_transfer.company_id = :c0

EOS;

			$arg = array(
				':c0' => $_SESSION['gid'],
			);

			$ret = array();
			$res = SQL::fetch_all($sql, $arg);
			foreach ($res as $rec) {

				$rec['license_meta'] = json_decode($rec['license_meta'], true);
				// $rec['license_meta'] = json_decode($rec['license_meta'], true);
				//
				// if ('R421577' == $rec['license_code']) {
				// 	var_dump($rec);
				// 	exit;
				// }
				$d = $rec['license_meta']['dist_m']; // Meters
				$d = ceil($d * 0.0006213712); // In Miles

				$tS = $rec['license_meta']['dist_s'];
				$tH = floor($tS / 60 / 60);
				$tS = $tS - ($tH * 60 * 60);
				$tM = floor($tS / 60);

				$ret[] = array(
					'name' => $rec['license_name'],
					'type' => $rec['license_type'],
					'license_code' => $rec['license_code'],
					'license_guid' => $rec['license_guid'],
					'license_type' => $rec['license_type'],
					'travel_info' => sprintf('%d Miles, %dh %02dm', $d, $tH, $tM),
					'geo_lat' => floatval($rec['geo_lat']),
					'geo_lon' => floatval($rec['geo_lon']),
					'marker' => array(
						'color' => \App\UI_License::color($rec['license_type']),
						'mark' => \App\UI_License::mark($rec['license_type']),
					)
				);
			}

			_exit_json($ret);
		}

		$data = array(
			'Page' => array('title' => 'Client :: Map'),
			'client_list' => array(),
			'license_type_list' => array(),
		);

		$data['license_type_list'][] = array('type' => 'Retail');
		$data['license_type_list'][] = array('type' => 'Processor');
		$data['license_type_list'][] = array('type' => 'Laboratory');
		$data['license_type_list'][] = array('type' => 'Carrier');

		$data['Origin_License'] = $_SESSION['License'];

		return $this->_container->view->render($RES, 'page/client/map.html', $data);

	}
}
