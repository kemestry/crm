<?php
/**
	Bootstrap menu.openthc.com
*/

use Edoceo\Radix\DB\SQL;

define('APP_NAME', 'OpenTHC | Fire');
define('APP_SITE', 'https://fire.openthc.com');
define('APP_ROOT', __DIR__);
define('APP_SALT', sha1(APP_NAME . APP_SITE . APP_ROOT));

error_reporting(E_ALL & ~ E_NOTICE);

openlog('openthc-fire', LOG_ODELAY|LOG_PID, LOG_LOCAL0);

define('HASHID_SALT', '01CRZ5WKQWMWXRCN24MTTG9R4T');

// Composer Autoloader
$cvl = sprintf('%s/vendor/autoload.php', APP_ROOT);
if (!is_file($cvl)) {
	die("Run Composer First\n");
}
require_once($cvl);

require_once('/opt/common/lib/Controller/Auth/Connect.php');
require_once(APP_ROOT . '/lib/Base32.php');

SQL::init('pgsql:host=45.79.109.159;dbname=openthc', 'openthc', '13bb034868c4a1545d3d63801bd2266b');

define('OPT_SYNC_KEY', 'sync-crm-time');

function _phone_e164($p)
{
	$pnu = \libphonenumber\PhoneNumberUtil::getInstance();
	$r = $pnu->parse($p, 'US');
	$r = $pnu->format($r, \libphonenumber\PhoneNumberFormat::E164);
	return $r;
}

function _phone_nice($p)
{
	$pnu = \libphonenumber\PhoneNumberUtil::getInstance();
	$r = $pnu->parse($p, 'US');
	$r = $pnu->format($r, \libphonenumber\PhoneNumberFormat::INTERNATIONAL);
	return $r;
}

function _leafdata_product_type_nice($t0,$t1)
{
	$x = trim(sprintf('%s/%s', $t0, $t1), '/ ');
	switch ($x) {
	case 'intermediate_product/co2_concentrate':
		return 'Concentrate/CO2';
	case 'end_product/concentrate_for_inhalation':
		return 'Concentrate';
	case 'end_product/infused_mix':
		return 'Mix/Infused';
	case 'end_product/usable_marijuana':
		return 'Flower';
	default:
		return $x;
	}
}
