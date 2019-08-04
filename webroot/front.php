<?php
/**
 * Front Controller for crm.openthc.com
 */

use Edoceo\Radix;
use Edoceo\Radix\Session;
use Edoceo\Radix\DB\SQL;

require_once(dirname(dirname(__FILE__)) . '/boot.php');
require_once(APP_ROOT . '/Pwig.php');

$cfg = array('debug' => true);
$app = new \OpenTHC\App($cfg);
$con = $app->getContainer();

$con['DB'] = function($c0) {
	$cfg = \OpenTHC\Config::get('database_main');
	var_dump($cfg);
	exit;
};

// Extend Twig with PHP Include (eval'd!!!)
// $con['view']->getEnvironment()->addFunction(new \Twig\TwigFunction('include_php', function($f, $d) {
// 	var_dump($f);
// 	var_dump($d);
// 	exit(0);
// }, ['is_safe' => ['html']]));

// Pwig
// $con['pwig'] = function($c0) {
// 	$p = Edoceo\Pwig::getInstance();
// 	$p->addPath(sprintf('%s/pwig', APP_ROOT));
// 	return $p;
// };


// Home
$app->get('/home', 'App\Controller\Home')
->add('App\Middleware\Menu')
->add('App\Middleware\Auth')
->add('App\Middleware\Session');


// $app->get('/pwig', function($REQ, $RES) {
// 	return $this->pwig->render($RES, 'client/view.php', $data);
// });

// Sync
$app->get('/sync', 'App\Controller\Sync')
->add('App\Middleware\Menu')
->add('App\Middleware\Session');

$app->post('/sync', 'App\Controller\Sync:exec')
->add('App\Middleware\Session');

$app->get('/chart/{spec}', function($REQ, $RES, $ARG) {
	$spec = $ARG['spec'];
	$spec = preg_replace('/[^\w\-]+/', null, $spec);
	$file = sprintf('%s/chart/%s.php', APP_ROOT, $spec);
	if (is_file($file)) {
		return include($file);
	}
	var_dump($ARG);
	return $RES->withStatus(404);
})
->add('App\Middleware\Session');

// Create new Code with UI
$app->get('/prospect', function($REQ, $RES, $ARG) {

	$data = array(
		'Page' => array('title' => 'Prospecting'),
		'Center' => array(
			'latitude' => 47.5,
			'longitude' => -122.5,
		),
	);

	$data['license_type_list'] = array();
	$res = SQL::fetch_all('SELECT DISTINCT type FROM license ORDER BY type');
	foreach ($res as $rec) {
		$t = $rec['type'];
		$data['license_type_list'][$t] = $t;
	}

	return $this->view->render($RES, 'page/prospect.html', $data);

})
->add('App\Middleware\Menu')
->add('App\Middleware\Auth')
->add('App\Middleware\Session');

// Create new Code with UI
$app->group('/client', 'App\Module\Client')
	->add('App\Middleware\Menu')
	->add('App\Middleware\Auth')
	->add('App\Middleware\Session');


// Create new Code with UI
$app->group('/vendor', 'App\Module\Vendor')
	->add('App\Middleware\Menu')
	->add('App\Middleware\Auth')
	->add('App\Middleware\Session');


$app->get('/contact/ajax', 'App\Controller\Contact\Ajax')->add('App\Middleware\Session');
$app->get('/contact/create', 'App\Controller\Contact\Create')->add('App\Middleware\Menu')->add('App\Middleware\Session');
$app->post('/contact/create', 'App\Controller\Contact\Create')->add('App\Middleware\Menu')->add('App\Middleware\Session');
// $app->post('/contact/create', function($REQ, $RES, $ARG) {
// 	$hashids = new Hashids\Hashids(HASHID_SALT, 0, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
// 	$hc = $hashids->encode($pk);
// 	return $RES->withRedirect('/feedback/' . $hc);
// });


// Transfer / Manifest / Order
$app->group('/transfer', 'App\Module\Transfer')
	->add('App\Middleware\Menu')
	->add('App\Middleware\Auth')
	->add('App\Middleware\Session');


// Public View
$app->get('/feedback/{code}', function($REQ, $RES, $ARG) {

	$hashids = new Hashids\Hashids(HASHID_SALT, 0, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
	$pk = $hashids->decode($ARG['code']);
	$pk = $pk[0];

	//$pk = \Base32::decode($ARG['code']);

	$sql = 'SELECT * FROM fire_code WHERE id = ?';
	$arg = array($pk);
	$rec = SQL::fetch_row($sql, $arg);
	if (empty($rec['id'])) {
		//die('No Product');
		//Radix::redirect('/feedback/' . $q);
	}

	$data = array(
		'Product' => $rec,
	);
	return $this->view->render($RES, 'page/feedback.html', $data);

});


// Search Lander
$app->get('/search', 'App\Controller\Search')
->add('App\Middleware\Menu')
->add('App\Middleware\Session');


// Settings
$app->map(['GET', 'POST'], '/settings', 'App\Controller\Settings')
->add('App\Middleware\Menu')
->add('App\Middleware\Session');


// Report
$app->get('/report', function($REQ, $RES, $ARG) {
	$data = array(
		'Page' => array('title' => 'Reports'),
	);
	return $this->view->render($RES, 'page/report/index.html', $data);
})
->add('App\Middleware\Menu')
->add('App\Middleware\Session');

$app->get('/report/[{path:.*}]', function($REQ, $RES, $ARG) {
	$data = array(
		'Page' => array('title' => 'Reports'),
		'path' => $ARG['path'],
	);
	return $this->view->render($RES, 'page/report/empty.html', $data);
})
->add('App\Middleware\Menu')
->add('App\Middleware\Session');


$app->get('/intent', function($REQ, $RES, $ARG) {
	switch ($_SESSION['intent']) {
	default:
		return $RES->withRedirect('/home');
	}
});


// APIs
// API
$app->group('/api', function() {

	$this->get('', 'App\Controller\API');

	// Full Search
	$this->get('/search', 'App\Controller\API\Search');

	// Company Search
	//$this->get('/company', 'App\Controller\API\Company\Search');

	// Single
	//$this->get('/company/{guid}', 'App\Controller\API\Company\Single');

	// Create
	//$this->post('/company', 'App\Controller\API\Company\Create');

	// Update
	//$this->post('/company/{guid}', 'App\Controller\API\Company\Update');

	// License Search
	//$this->get('/license', 'App\Controller\API\License\Search');

	// Single
	//$this->get('/license/{guid}', 'App\Controller\API\License\Single');

	// Create
	//$this->post('/license', 'App\Controller\API\License\Update');

	// Update
	//$this->post('/license/{guid}', 'App\Controller\API\License\Update');

})
//->add('App\Middleware\RateLimit')
;


// Authentication
$app->group('/auth', function() {

	$this->get('/open', 'App\Controller\Auth\oAuth2\Open');
	$this->get('/connect', 'App\Controller\Auth\Connect');
	$this->get('/back', 'App\Controller\Auth\oAuth2\Back');
	$this->get('/fail', 'OpenTHC\Controller\Auth\Fail');
	$this->get('/ping', 'OpenTHC\Controller\Auth\Ping');
	$this->get('/shut', 'OpenTHC\Controller\Auth\Shut');

})
//->add('App\Middleware\Menu')
->add('App\Middleware\Session');

// @see https://github.com/slimphp/Slim/issues/1456
if ('routes' == $_GET['_dump']) {
	$x = $app->getContainer()->get('router')->getRoutes();
	//var_dump($x);

	$y = array_reduce($x, function ($target, $route) {
		$target[$route->getPattern()] = [
			'methods' => json_encode($route->getMethods()),
			//'callable' => $route->getCallable(),
			'middlewares' => json_encode($route->getMiddleware()),
			'pattern' => $route->getPattern(),
		];
		return $target;
	}, []);

	var_dump($y);
	exit;
}


// Run the App
$ret = $app->run();

// @see https://github.com/slimphp/Slim/issues/1456
if ('routes' == $_GET['_dump']) {
	$x = $app->getContainer()->get('router')->getRoutes();
	//var_dump($x);

	$y = array_reduce($x, function ($target, $route) {
		$target[$route->getPattern()] = [
			'methods' => json_encode($route->getMethods()),
			//'callable' => $route->getCallable(),
			'middlewares' => json_encode($route->getMiddleware()),
			'pattern' => $route->getPattern(),
		];
		return $target;
	}, []);

	var_dump($y);

}
