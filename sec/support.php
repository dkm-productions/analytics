<?
require_once "inc/requireLogin.php";
require_once "inc/uiFunctions.php";
require_once "php/dao/UserDao.php";

$myUser = UserDao::getMyUser();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <? renderHead("Help & Support") ?>
    <link rel="stylesheet" type="text/css" href="css/clicktate.css?3" media="screen" />
    <link rel="stylesheet" type="text/css" href="css/schedule.css" media="screen" />
    <link rel="stylesheet" type="text/css" href="css/data-tables.css" media="screen" />
    <link rel="stylesheet" type="text/css" href="css/pop.css?<?=Version::getUrlSuffix() ?>" />
    <? if (! $myLogin->vistaFonts || $myLogin->ie == "6") { ?>
    <link rel="stylesheet" type="text/css" href="css/clicktate-font.css?3" media="screen" />
    <link rel="stylesheet" type="text/css" href="css/pop-font.css?<?=Version::getUrlSuffix() ?>" />
    <link rel="stylesheet" type="text/css" href="css/schedule-font.css" media="screen" />
    <link rel="stylesheet" type="text/css" href="css/data-tables-font.css" media="screen" />
    <? } ?>
<!--[if lte IE 6]>    
    <link rel="stylesheet" type="text/css" href="css/pop-ie6.css?<?=Version::getUrlSuffix() ?>" />
<![endif]-->    
    <script language="JavaScript1.2" src="js/ajax.js"></script>
    <script language="JavaScript1.2" src="js/yahoo-min.js"></script>
    <script language="JavaScript1.2" src="js/json.js"></script>
    <script language="JavaScript1.2" src="js/connection-min.js"></script>
    <script language="JavaScript1.2" src="js/ui.js"></script>
  </head>
  <body>
    <form id="frm" method="post" action="welcome.php">
      <div id="bodyContainer">
        <? include "inc/header.php" ?>
        <div class="content">
          <h1 style="margin:0 0 0.2em 0">Help & Support Center</h1>
          <? renderBoxStart("wide small-pad") ?>
            <h4 style="margin:0">Documentation</h4>
          <? renderBoxEnd() ?>
          <? renderBoxStart("wide small-pad push") ?>
            <h4 style="margin:0">Frequently Asked Questions</h4>
          <? renderBoxEnd() ?>
        </div>
      </div>
      <? include "inc/footer.php" ?>
    </form>
  </body>
</html>
