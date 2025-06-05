<?php
ob_start();
// Application configuration

// Error reporting - set to 0 in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Application paths
define('BASE_PATH', dirname(dirname(__FILE__)));
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('MODULES_PATH', BASE_PATH . '/modules');
define('LAYOUTS_PATH', BASE_PATH . '/layouts');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('PROFILES', BASE_PATH . '/profiles');
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('ROOT_URL', '/Myparttime');

// Application settings
define('SITE_NAME', 'MyPartTime');
define('SITE_VERSION', '1.0.0');
define('RECORDS_PER_PAGE', 10);

// Include required files - changed to require_once
require_once(INCLUDES_PATH . '/database.php');
require_once(INCLUDES_PATH . '/auth.php');

// เริ่มต้นการเชื่อมต่อฐานข้อมูล
$database = new Database();
$db = $database->getConnection();
if (!$db) {
    die("Database connection failed");
}
// Initialize authentication
$auth = new Auth($db);

// รวม functions.php หลังจากกำหนด $auth แล้ว - changed to require_once
require_once(INCLUDES_PATH . '/functions.php');
?>