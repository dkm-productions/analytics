<?
ob_start('ob_gzhandler');
require_once "php/data/LoginSession.php";
require_once 'php/data/rec/sql/LookupAreas.php';
require_once 'php/c/template-entry/TemplateEntry.php';
require_once 'inc/uiFunctions.php';
//
LoginSession::verify_forUser()->requires($login->Role->Artifact->markReview);
?>
<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.0 Strict//EN'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>
  <head>
    <? HEAD('Item Review', 'ReviewPage') ?>
    <? HEAD_UI('DocHistory', 'Scanning') ?>
    <? HEAD_PortalUserEntry() ?>
 </head>
  <body>
    <? BODY() ?>
      <h1>Item Review</h1>
      <? renderBoxStart('wide min-pad', null, null, 'box') ?>
        <div id='tile'>
          <div class='spacer'>&nbsp;</div>
        </div>
      <? renderBoxEnd() ?>
    <? _BODY() ?>
  </body>
  <? CONSTANTS('DocHistory', 'Scanning', 'Templates') ?>
  <? START() ?>
</html>