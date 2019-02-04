<?php
/**
 * Sync Interface
*/

namespace App\Controller;

use Edoceo\Radix\DB\SQL;

class Sync extends \OpenTHC\Controller\Base
{
	function __invoke($REQ, $RES, $ARG)
	{
		$data = array(
			'Page' => [ 'title' => 'Sync' ],
			'transfer_list' => array(),
		);

		if ('debug' == $_GET['v']) {
			header('content-type: text/plain');
			$this->exec($REQ, $RES, $ARG);
			exit(0);
		}

		$C = new \OpenTHC\Company($_SESSION['Company']['id']);

		$chk = $C->getOption(OPT_SYNC_KEY);
		if (empty($chk)) {
			// Never Sync!  Make them Press a Button or Wait?
			$data['message'] = 'Never Sync! Please Press the Button';
			//return $this->_container->view->render($RES, 'page/sync.html', $data);
		}

		$age = $_SERVER['REQUEST_TIME'] - $chk;
		if ($age > 3600) {
			$data['message'] = 'More than one hour since the last Sync';
			//return $this->_container->view->render($RES, 'page/sync.html', $data);
		}

		return $this->_container->view->render($RES, 'page/sync.html', $data);
	}

	/**
	 * Actually do the Sync
	 * @param [type] $REQ [description]
	 * @param [type] $RES [description]
	 * @param [type] $ARG [description]
	 * @return [type] [description]
	 */
	function exec($REQ, $RES, $ARG)
	{
		\session_write_close();

		$C = new \OpenTHC\Company($_SESSION['Company']['id']);

		$rce = new \OpenTHC\RCE($_SESSION['pipe-token']);
		$res = $rce->get('/transfer/outgoing?source=true');

		if ('success' != $res['status']) {
			_exit_text($res, 500);
		}

		foreach ($res['result'] as $rec) {

			//echo "transfer:{$rec['guid']} = {$rec['status']}\n";

			$sql = 'SELECT id FROM license WHERE guid = :g';
			$arg = array(':g' => $rec['_source']['global_to_mme_id']);
			$L = SQL::fetch_row($sql, $arg);
			if (empty($L['id'])) {
				die("No License: '{$rec['_source']['global_to_mme_id']}'");
			}

			$sql = 'SELECT id, hash FROM crm_transfer WHERE company_id = :c AND guid = :g';
			$arg = array(':c' => $C['id'], ':g' => $rec['guid']);
			$chk = SQL::fetch_row($sql, $arg);

			if (empty($chk)) {

				$add = array(
					'company_id' => $C['id'],
					'guid' => $rec['guid'],
					'hash' => $rec['hash'],
					'stat' => 100,
					'target_license_id' => $L['id'],
					'created_at' => $rec['_source']['created_at'],
					//'confirm_at' =>
					'completed_at' => $rec['_source']['transferred_at'],
					'meta' => json_encode($rec['_source']),
				);

				if (empty($add['completed_at'])) {
					unset($add['completed_at']);
				}

				SQL::insert('crm_transfer', $add);

			} elseif ($chk['hash'] != $rec['hash']) {

				$sql = 'SELECT id FROM license WHERE guid = :g';
				$arg = array(':g' => $rec['_source']['global_to_mme_id']);
				$L = SQL::fetch_row($sql, $arg);
				if (empty($L['id'])) {
					die("No License: '{$rec['_source']['global_to_mme_id']}'");
				}

				$sql = 'UPDATE crm_transfer SET target_license_id = :l, flag = :f, hash = :h, stat = :s, meta = :m WHERE id = :id';
				$arg = array(
					':id' => $chk['id'],
					':l' => $L['id'],
					':f' => ($rec['flag'] & ~ \App\Transfer::FLAG_SYNC),
					':h' => $rec['hash'],
					':s' => 100,
					':m' => json_encode($rec['_source']),
				);
				SQL::query($sql, $arg);

			}

		}

		$C->setOption(OPT_SYNC_KEY, time());

		return $RES->withStatus(204);

	}

	function transferItems()
	{
		// if (0 == count($data['transfer_item_list'])) {
		// 	$rce = new \OpenTHC\RCE($_SESSION['pipe-token']);
		// 	$res = $rce->get('/transfer/outgoing/' . $data['transfer']['guid']);
		// 	var_dump($res);
		// 	foreach ($res as $rec) {
		// 		SQL::insert(array())
		// 	}
		// }

	}
}
