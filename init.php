<?php
DEFINE ('ABSPATH', realpath(dirname(__FILE__)));
chdir (ABSPATH);

function handle_error ($errno, $errstr, $errfile, $errline, $errcontext) {
    echo "Error [{$errno}] {$errstr} in {$errfile} line {$errline}\r\n";
    debug_print_backtrace();
    die();
}
require_once ('config/config.php');
require ('classes/pdo.php');
require ('includes/common.php');

if (DEBUG) {
    set_error_handler ('handle_error');
}

$db = new pdo_mysql(DB_HOST, DB_DATABASE, DB_USER, DB_PASSWORD);
$db->setAttribute(PDO::ATTR_ERRMODE, DEBUG ? PDO::ERRMODE_WARNING : PDO::ERRMODE_SILENT);
$db->query ("SET NAMES 'utf8'");
$db->delete_old_cache();

//$client = new LogosSoapClient;
//$client->sign_in();
?>