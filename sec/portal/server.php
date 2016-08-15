<?php
set_include_path('../');
require_once 'php/data/json/JAjaxMsg.php'; 
require_once 'inc/serverFunctions.php'; 
//
$action = null;
if (isset($_GET['action'])) {
  $action = $_GET['action'];
  Logger::debug(currentUrl());
} else {
  $_POST['obj'] = stripslashes($_POST['obj']);
  $action = $_POST['action'];
  $obj = json_decode($_POST['obj']);
  Logger::debug(currentUrl());
  Logger::debug_r($_POST, '$_POST');;
}
if (isset($_GET['debug']))
  echo '<pre>';
/**
 * @param object $obj
 */
function jam($obj = null) {
  if (isset($_GET['debug']))
    echo htmlentities(print_r($obj));
  else
    echo '{"id":1,"obj":' . jsonencode($obj) . '}';
  exit;
}
/**
 * @param Exception $e
 */
function jamerr($e) {
  $m = JAjaxMsg::constructError($e);
  echo $m->out();
}
?>