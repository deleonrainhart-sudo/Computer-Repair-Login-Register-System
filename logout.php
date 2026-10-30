<?php
require 'core/bootstrap.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit('Use the Logout button.'); }
validate_csrf();
$_SESSION=[];
if (ini_get('session.use_cookies')) {
    $p=session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
go('login.php');
