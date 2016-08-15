<?
ob_start('ob_gzhandler');
require_once "php/data/LoginSession.php";
require_once 'inc/uiFunctions.php';
require_once 'php/dao/UserDao.php';
require_once 'php/data/rec/sql/Messaging.php';
require_once 'php/data/rec/sql/Dashboard.php';
//
LoginSession::verify_forUser()->requires($login->Role->Message->general);
$sent = isset($_GET['get']);
?>
<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.0 Strict//EN'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>
  <head>
    <? HEAD('Message Center', 'MessagesPage', 'messages.css') ?>
    <? HEAD_UI('Messaging') ?>
  </head>
  <body>
    <? BODY() ?>
      <h1>Message Center</h1>
      <? renderBoxStart('wide small-pad') ?>
        <div id='tile'>
          <div class='spacer'>&nbsp;</div>
        </div>
      <? renderBoxEnd() ?>
    <? _BODY() ?>
  </body>
  <? CONSTANTS('Messaging') ?>
  <? START() ?>
</html>
