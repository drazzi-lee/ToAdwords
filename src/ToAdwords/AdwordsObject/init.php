<?php

error_reporting(E_STRICT | E_ALL);

$depth = '../';
define('SRC_PATH', dirname(__FILE__));
define('AD_LIB_PATH', 'Google/Api/Ads/AdWords/Lib');
define('UTIL_PATH', 'Google/Api/Ads/Common/Util');
define('ADWORDS_UTIL_PATH', 'Google/Api/Ads/AdWords/Util');

define('ADWORDS_VERSION', 'v201306');

// Configure include path
ini_set('include_path', implode(array(
    ini_get('include_path'), PATH_SEPARATOR, SRC_PATH
)));

// Include the AdWordsUser
require_once AD_LIB_PATH . '/AdWordsUser.php';