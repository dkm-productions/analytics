<?php
require_once 'php/data/LoginSession.php';
require_once "php/dao/JsonDao.php";
require_once "php/dao/TemplateReaderDao.php";
require_once "php/dao/SessionDao.php";
require_once "php/dao/UsageDao.php";
require_once "php/delegates/JsonDelegate.php";
require_once "php/data/json/JAjaxMsg.php";
//
logit("serverJson.php?" . implode_with_keys("&", $_GET));
$id = $_GET["id"];
try {
  LoginSession::verify_forServer();
  switch ($_GET["action"]) {
    case "getTemplate":
      $m = new JAjaxMsg("template", JsonDelegate::jsonJTemplate($id));
      break;
    case "getParInfo":
      $m = new JAjaxMsg("par", JsonDelegate::jsonJParInfo($id));
      break;
    case "getParTemplates":
      $m = new JAjaxMsg("parTemplates", JsonDelegate::jsonJParTemplates($id));
      break;
    case "getPars":
      $m = new JAjaxMsg("getPars", JsonDelegate::getPars($id));    
      break;
    case "getPars2":
      $m = new JAjaxMsg("getPars", JsonDelegate::getPars2($id));    
      break;
    case "getQuestions":
      $m = new JAjaxMsg("questions", JsonDelegate::jsonJQuestions($id));
      break;
    case "getTemplates":
      $m = new JAjaxMsg("templates", JsonDelegate::jsonJTemplates());
      break;
    case "getDefaultMap":
      $m = new JAjaxMsg("map", JsonDelegate::jsonJDefaultMap($id));
      break;
    case "preview":
      $m = new JAjaxMsg("preview", TemplateReaderDao::parPreview($id, $_GET["tid"], $_GET["nd"]));
      break;
    case "lock":
      SessionDao::lockSession($id);
      $m = null;
      break;
    case "unlock":
      SessionDao::unlockSession($id);
      $m = null;
      break;
    case "searchMeds":
      $m = new JAjaxMsg("meds", JsonDelegate::jsonJMeds($id));
      break;
    case "getIcdCodes":
      $icdcodes = SessionDao::getIcdCodes($id); 
      $m = new JAjaxMsg("getIcdCodes", $icdcodes->out());
      break;
    case "searchIcdCodes":
      $icdcodes = SessionDao::searchIcdCodes($id); 
      $m = new JAjaxMsg("searchIcdCodes", $icdcodes->out());
      break;
    case "us2":  // usage download-print
      UsageDao::createUsageDetail($id, 2, $_GET["ci"], $_GET["cn"]);
      $m = null;
      break;
    case "us3":  // usage copy
      UsageDao::createUsageDetail($id, 3, $_GET["ci"], $_GET["cn"]);
      $m = null;
      break;
    case "usc":  // clear doc
      UsageDao::setBilled($id, false);
      $m = null;
      break;
  }
} catch (Exception $e) {
  $m = JAjaxMsg::constructError($e);
}
if ($m != null) 
  echo $m->out();
else 
  echo JAjaxMsg::asNull();
