<?php
/**
 * Module for Vendor Routes
 */

namespace App\Module;

class Vendor extends \OpenTHC\Module\Base
{
	function __invoke($a)
	{
		$a->get('', 'App\Controller\Vendor\Home');

		$a->get('/create', 'App\Controller\Vendor\Create');
		$a->post('/create', 'App\Controller\Vendor\Create');

		$a->get('/map', 'App\Controller\Vendor\Map');

		$a->post('/journal/create', function($REQ, $RES) {

			// $sql = 'SELECT * FROM license WHERE code = ?';
			// $arg = array($ARG['guid']);
			// $res = SQL::fetch_row($sql, $arg);
			$L = new \OpenTHC\License($_POST['license_id']);

			// var_dump($_POST);

			$sql = 'INSERT INTO crm_journal (company_id_owner, contact_id_owner, company_id_about, contact_id_about, name, note) VALUES (?, ?, ?, ?, ?, ?)';
			$arg = array(
				$_SESSION['Company']['id'],
				$_SESSION['uid'],
				$L['company_id'],
				null,
				$_POST['type'],
				$_POST['note'],
			);
			\Edoceo\Radix\DB\SQL::query($sql, $arg);

			return $RES->withRedirect('/client/' . $L['code']);
		});

		$a->get('/{guid}', 'App\Controller\Vendor\View');
		$a->post('/{guid}', 'App\Controller\Vendor\Save');

	}
}
