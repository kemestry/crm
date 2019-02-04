<?php
/**
 * Search Interface
*/

namespace App\Controller;

use Edoceo\Radix\DB\SQL;
use DateInterval;
use DateTime;
use DateTimeZone;

class Home extends \OpenTHC\Controller\Base
{
	function __invoke($REQ, $RES,$ARG)
	{
		$data = array(
			'Page' => array('title' => 'Home'),
		);

		$C = new \OpenTHC\Company($_SESSION['Company']['id']);

		$chk = $C->getOption(OPT_SYNC_KEY);
		if (empty($chk)) {
			$data['message'] = 'This is your first go, this one can take a while...';
			//return $this->_container->view->render($RES, 'page/sync.html', $data);
		}

		$d0 = new DateTime();
		$d0->setTimezone(new DateTimeZone('America/Los_Angeles'));

		$d1 = clone $d0;
		$have_dow = $d1->format('N');

		$want_dow = 1; // Monday
		$want_dow = 7; // Sunday

		if ($have_dow != $want_dow) {
			if (7 == $want_dow) {
				$d1->sub(new DateInterval(sprintf('P%dD', $have_dow)));
			} else {
				$d1->sub(new DateInterval(sprintf('P%dD', $have_dow - $want_dow)));
			}
		}
		//echo $d1->format(DateTime::RFC3339);
		$d2 = clone $d1;
		for ($d=0;$d<7;$d++) {
			$data['dow_list'][] = $d2->format('D');
			$d2->add(new DateInterval('P1D'));
		}

		// 6 Week Calendar
		for ($w=0;$w<6;$w++) {
			$day_list = array();
			for ($d=0;$d<7;$d++) {
				$day_info = array(
					'date' => $d1->format(DateTime::RFC3339),
					'name' => $d1->format('m/d'),
					'pick' => ($d0 == $d1),
					'evt_list' => array(), // 'Foo', 'Bar', 'Baz'), //  $dom_event_list[]
				);
				$sql = 'SELECT * FROM calendar_event WHERE company_id = :c AND (alpha >= :d0 AND alpha <= :d1)';
				$arg = array(
					':c' => $_SESSION['Company']['id'],
					':d0' => $d1->format('Y-m-d 00:00:00'),
					':d1' => $d1->format('Y-m-d 23:59:59'),
				);
				$day_info['evt_list'] = SQL::fetch_all($sql, $arg);
				$day_list[] = $day_info;
				$d1->add(new DateInterval('P1D'));
			}
			$data['week_list'][] = array(
				'day_list' => $day_list
			);
		}


		return $this->_container->view->render($RES, 'page/home.html', $data);

	}
}
