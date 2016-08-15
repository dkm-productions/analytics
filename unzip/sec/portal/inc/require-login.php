<?php
set_include_path('../');
require_once 'php/data/rec/sql/PortalUsers_Session.php';
//
try {
  $me = PortalUsers_Session::reauthenticate();
} catch (PortalException $e) {
  header('Location: index.php?timeout=1');
  exit;
} catch (Exception $e) {
  header('Location: index.php?error=1');
  exit;
}
?>