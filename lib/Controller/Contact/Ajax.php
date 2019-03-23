<?php
/**
 * Contact AJAX
 */

namespace App\Controller\Contact;

use Edoceo\Radix\DB\SQL;

class Ajax extends \OpenTHC\Controller\Base
{
	function __invoke($REQ, $RES,$ARG)
	{
		switch ($_GET['a']) {
		case 'client':
			// Contacts in "this" company
			$sql = <<<SQL
SELECT contact.*
FROM contact
JOIN crm_contact ON contact.id = crm_contact.contact_id_prime
WHERE crm_contact.company_id_owner = :c0
AND (contact.name ILIKE :q0 OR contact.email LIKE :e0 OR contact.phone LIKE :p0)
ORDER BY contact.name
SQL;
			$arg = array(
				':c0' => $_GET['company'],
				':q0' => sprintf('%%%s%%', preg_replace('/\S+/', null, $_GET['term'])),
				':e0' => sprintf('%%%s%%', strtolower(preg_replace('/\S+/', null, $_GET['term']))),
				':p0' => sprintf('%%%s%%', preg_replace('/[^\d]+/', null, $_GET['term'])),
			);
			$res = SQL::fetch_all($sql, $arg);

			$output_list = array();
			foreach ($res as $rec) {
				$output_list[] = array(
					'id' => $rec['id'],
					'label' => $rec['name'],
					'value' => $rec['name'],
					'company' => array(
						'id' => $rec['company_id'],
					),
				);
			}

			return $RES->withJSON($output_list);

			break;
		case 'global':
			// Everyone!!
			$sql = <<<SQL
SELECT contact.*, company.name AS company_name
FROM contact
LEFT JOIN company ON contact.company_id = company.id
WHERE contact.name ILIKE :q0 OR contact.email LIKE :e0 OR contact.phone LIKE :p0
ORDER BY contact.name
LIMIT 3
SQL;
			$arg = array(
				':q0' => sprintf('%%%s%%', $_GET['term']),
				':e0' => sprintf('%%%s%%', strtolower(preg_replace('/\s+/', null, $_GET['term']))),
				':p0' => null, // sprintf('%%%s%%', preg_replace('/[^\d]+/', null, $_GET['term'])),
			);
			$res = SQL::fetch_all($sql, $arg);


			// var_dump($sql);
			// print_r($arg);
			// print_r($res);

			$output_list = array();
			foreach ($res as $rec) {

				if (!empty($rec['company_name'])) {
					$rec['name'] = sprintf('%s @ %s', $rec['name'], $rec['company_name']);
				}

				$output_list[] = array(
					'id' => $rec['id'],
					'label' => $rec['name'],
					'value' => $rec['name'],
					'email' => $rec['email'],
					'phone' => $rec['phone'],
					'company' => array(
						'id' => $rec['company_id'],
					),
				);
			}

			return $RES->withJSON($output_list);

			break;

		case 'origin':

			// Contacts in "this" company
			$sql = <<<SQL
SELECT contact.*
FROM contact
JOIN crm_contact ON contact.id = crm_contact.contact_id_prime
WHERE crm_contact.company_id_owner = :c0
AND (contact.name ILIKE :q0 OR contact.email LIKE :e0 OR contact.phone LIKE :p0)
ORDER BY contact.name
SQL;
			$arg = array(
				':c0' => $_SESSION['gid'],
				':q0' => sprintf('%%%s%%', preg_replace('/\S+/', null, $_GET['term'])),
				':e0' => sprintf('%%%s%%', strtolower(preg_replace('/\S+/', null, $_GET['term']))),
				':p0' => sprintf('%%%s%%', preg_replace('/[^\d]+/', null, $_GET['term'])),
			);
			$res = SQL::fetch_all($sql, $arg);

			$output_list = array();
			foreach ($res as $rec) {
				$output_list[] = array(
					'id' => $rec['id'],
					'label' => $rec['name'],
					'value' => $rec['name'],
					'company' => array(
						'id' => $rec['company_id'],
					),
				);
			}

			return $RES->withJSON($output_list);

		}
	}
}
