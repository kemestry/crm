<?php
/**
 * Transfer Sync
*/

namespace App\Controller\Transfer;

use Edoceo\Radix\DB\SQL;
use DateInterval;
use DateTime;
use DateTimeZone;

class Sync extends \OpenTHC\Controller\Base
{
	function __invoke($REQ, $RES,$ARG)
	{
		\session_write_close();

		$RC = new \Redis();
		$RC->connect('127.0.0.1');

		// Load Transfer
		$sql = 'SELECT crm_transfer.*, license.code AS license_code, license.name AS license_name FROM crm_transfer';
		$sql.= ' JOIN license ON crm_transfer.target_license_id = license.id';
		$sql.= ' WHERE crm_transfer.company_id = :c AND crm_transfer.guid = :g';
		$arg = array(':c' => $_SESSION['Company']['id'], ':g' => $ARG['guid']);
		$data['transfer'] = SQL::fetch_row($sql, $arg);


		$rce = new \OpenTHC\RCE($_SESSION['pipe-token']);
		$res = $rce->get('/transfer/outgoing/' . $data['transfer']['guid']);
		if ('success' != $res['status']) {
			_exit_text('Failed to Load Transfer, Please Try Again', 500);
		}
		$T = $res['result'];

		if (!empty($T['void'])) {
			$data['transfer']['stat'] = 410;
		} else {
			switch ($T['status']) {
			case 'open':
				$data['transfer']['stat'] = 100;
				break;
			case 'ready-for-pickup':
				$data['transfer']['stat'] = 200;
				break;
			case 'in-transit':
				$data['transfer']['stat'] = 301;
				break;
			case 'received':
				$data['transfer']['stat'] = 307;
				break;
			}
		}

		// Load Transfer Items
		$rce = new \OpenTHC\RCE($_SESSION['pipe-token']);
		$res = $rce->get('/transfer/outgoing/' . $data['transfer']['guid']);
		if ('success' != $res['status']) {
			_exit_text('Failed to Load Items, Please Try Again', 500);
		}

		// Cleanup for re-add
		$sql = 'DELETE FROM crm_transfer_item WHERE transfer_id = :t';
		$arg = array($data['transfer']['id']);
		SQL::query($sql, $arg);

		$full_price = 0;
		foreach ($res['result']['inventory_transfer_items'] as $rec) {

			// Lookup Product
			$res = $rce->get('/lot/' . $rec['global_inventory_id']);
			$Lot = $res['result'];

			// Product+Cache
			$x = $RC->get('/cache/' . $rec['global_inventory_type_id']);
			if (!empty($x)) {
				$Product = json_decode($x, true);
			}
			if (empty($Product['global_id'])) {
				$res = $rce->get('/config/product/' . $rec['global_inventory_type_id']);
				$Product = $res['result'];
				$RC->set('/cache/' . $rec['global_inventory_type_id'], json_encode($Product), 3600);
			}

			$Strain = array();
			if (empty($rec['global_strain_id']) && empty($rec['strain_name'])) {
				$Strain = array(
					'name' => '- None -'
				);
			} else {
				$x = $RC->get('/cache/' . $rec['global_strain_id']);
				if (!empty($x)) {
					$Strain = \json_decode($x, true);
				}
				if (empty($Strain['global_id'])) {
					$res = $rce->get('/config/strain/' . $rec['global_strain_id']);
					$Strain = $res['result'];
					$RC->set('/cache/' . $rec['global_strain_id'], json_encode($Strain), 3600);
				}

				if (empty($Strain['name'])) {
					$Strain['name'] = $rec['strain_name'];
				}
			}

			$rec['product_type'] = _leafdata_product_type_nice($Product['type'], $Product['intermediate_type']);

			$add = array(
				//'company_id' => $_SESSION['Company']['id'],
				'transfer_id' => $data['transfer']['id'],
				'product' => $Product['name'],
				'strain' => $Strain['name'],
				'package_qty' => (200 == $data['transfer']['stat'] ? $rec['received_qty'] : $rec['qty']),
				'package_qom' => $Product['net_weight'],
				'package_uom' => $Product['uom'],
				'full_price' => $rec['price'],
				'meta' => array(
					'Item' => $rec,
					'Lot' => $Lot,
					'Product' => $Product,
					'Strain' => $Strain,
				)
			);
			var_dump($add);


			$full_price += floatval($rec['price']);

			$add['meta'] = json_encode($add['meta']);

			SQL::insert('crm_transfer_item', $add);

		}

		$sql = 'SELECT count(id) FROM crm_transfer_item WHERE transfer_id = :t';
		$arg = array(':t' => $data['transfer']['id']);
		$c0 = SQL::fetch_one($sql, $arg);

		$sql = "SELECT count(id) FROM crm_transfer_item WHERE transfer_id = :t AND meta->'Item'->>'is_sample' = '1'";
		$arg = [':t' => $data['transfer']['id']];
		$c1 = SQL::fetch_one($sql, $arg);

		if (($c0 > 0) && ($c0 == $c1)) {
			$data['transfer']['flag'] = $data['transfer']['flag'] | \App\Transfer::FLAG_SAMPLE;
		}

		SQL::query('UPDATE crm_transfer SET flag = flag | :f1,  full_price = :p, stat = :s WHERE id = :t', array(
			':t' => $data['transfer']['id'],
			':f1' => ($data['transfer']['flag'] | \App\Transfer::FLAG_SYNC),
			':s' => $data['transfer']['stat'],
			':p' => $full_price
		));

		//_exit_text($data['transfer']);

		if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			return $RES->withStatus(204);
		}

		return $RES->withRedirect('/transfer/' . $ARG['guid']);
	}
}
