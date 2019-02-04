<?php
/**
	Search Interface
*/

namespace App\Controller\Contact;

use Edoceo\Radix\DB\SQL;

class Create extends \OpenTHC\Controller\Base
{
	function __invoke($REQ, $RES,$ARG)
	{
		$data = array(
			'Company' => array(),
			'Contact' => array(),
		);

		if (!empty($_GET['company_id'])) {
			$x = SQL::fetch_row('SELECT * FROM company WHERE id = ?', array($_GET['company_id']));
			$data['Company'] = $x;
		}

		switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			return $this->_container->view->render($RES, 'page/contact/create.html', $data);
		case 'POST':

			$_POST['email'] = trim(strtolower($_POST['email']));

			$PrimeContact = array();
			$sql = 'SELECT * FROM contact WHERE company_id = :c AND email = :e';
			$arg = array(':c' => $_POST['company_id'], ':e' => $_POST['email']);
			$chk = SQL::fetch_row($sql, $arg);
			if (!empty($chk)) {
				$PrimeContact = $chk;
			} else {
				// Insert Record into the DIRECTORY
				$PrimeContact['id'] = SQL::insert('contact', array(
					'company_id' => $_POST['company_id'],
					'name' => $_POST['name'],
					'email' => $_POST['email'],
					'phone' => $_POST['phone'],
				));
			}

			$sql = 'SELECT * FROM crm_contact WHERE company_id = :c AND email = :e';
			$arg = array(':c' => $_POST['company_id'], ':e' => $_POST['email']);
			$chk = SQL::fetch_row($sql, $arg);
			if (empty($chk)) {
				// Insert Record into the DIRECTORY
				SQL::insert('crm_contact', array(
					'company_id_owner' => $_SESSION['gid'],
					'company_id' => $_POST['company_id'],
					'contact_id_prime' => $PrimeContact['id'],
					'name' => $_POST['name'],
					'email' => $_POST['email'],
					'phone' => $_POST['phone'],
				));
			}

			return $RES->withRedirect($_SERVER['HTTP_REFERER']);
		}
	}
}
