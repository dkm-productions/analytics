<?php
ob_start('ob_gzhandler');
require_once 'php/data/LoginSession.php';
require_once "php/dao/SessionDao.php";
require_once "php/dao/JsonDao.php";
require_once "php/dao/DataDao.php";
require_once "php/data/json/JAjaxMsg.php"; 
require_once "php/delegates/JsonDelegate.php";
require_once 'php/data/rec/sql/Procedures_Admin.php';
require_once 'php/data/rec/sql/Procedures_FaceHx.php';
//
if (isset($_GET["act"]) || isset($_GET['action'])) {
  $_GP = &$_GET;
} else {
  $_GP = &$_POST;
}
if (isset($_GP['action']))
  $act = $_GP['action'];
else
  $act = $_GP["act"];
if (isset($_GP["obj"])) {
  $_GP["obj"] = stripslashes($_GP["obj"]);
}
logit("serverSession.php?" . implode_with_keys("&", $_GP));
try { 
  LoginSession::verify_forServer();
  switch ($act) {
    
    // Get paragraph + auto-injections - existing pars
    case "getParInfos":
      logit("serverSession.getParInfos");
      $o = jsondecode($_GP["obj"]);
      logit_r($o, 'object from getParInfos');
      $m = new JAjaxMsg('pars', JsonDao::getJParInfos($o->tid, $o->nd, $o->cid, $o->pids));
      logit_r($m, 'getParInfos.m');
      break;
  
    case 'getInjects':
      logit('serverSession.getInjects');
      $o = jsondecode($_GP['obj']);
      //logit_r($o);
      $m = new JAjaxMsg('injects', JsonDao::getJParInfosByInjects($o->tid, $o->nd, $o->cid, $o->pool));
      break;
      
    // Session delete
    case "delete":
      $sessionId = $_GP["sid"];
      SessionDao::deleteSession($sessionId);
      $m = new JAjaxMsg("deleteSession", "null");
      break;
      
    case 'unsign':
      $sid = $_GP['id'];
      //SessionDao::unsign($sid);
      require_once 'php/c/sessions/Sessions.php';
      Sessions::unsign($sid);
      $m = new JAjaxMsg("unlock", "null");
      break;
      
    // Session autosave
    case "autosave":
      $o = jsondecode($_GP['obj']);
      logit_r($o, 'autosave');
      $sessionId = $o->sid;
      $actions = $o->a; //addslashes($o->a);
      $title = $o->title;  //addslashes($o->title);
      $html = $o->html;  //addslashes($o->html);
      $stub = $o->stub;
      if ($actions == "[]") {
        $actions = null;
      }
      if (! SessionDao::isLocked($sessionId)) {
        SessionDao::updateSessionActions($sessionId, $title, $actions, $html, $stub);
        SessionDao::updateVisitSum($o);
      //logit_r($o->iols, 'iols');
      //logit_r($o->instructs, 'instructs');
      //logit_r($o->out, 'out');
        $m = new JAjaxMsg("autosaveSession", q(formatNowTimestamp()));
      } else {
        $m = new JAjaxMsg("null", "null");
      }
      break;
      
    // Session save or sign
    case "save":
    case "sign":
      /*
       $obj
         id   // session ID
         t    // session title
         dos  // date of service
         s    // session standard
         h    // note HTML (for signed notes)
         out  // outputData (for signed notes)
         dout // dataSyncOuts (for signed notes)
      */
      $actions = stripslashes($_GP["a"]);
      $save = jsondecode($_GP["obj"]);
      $sessionId = $save->id;
      if ($actions == "[]") {
        $actions = null;
      }
      $nosign = false;
      if ($act == "sign") {
        $close = true;
        $html = $save->h;
        $cc = LookupDao::getPrintCustom();
        $nosign = (isset($cc->noSig) && $cc->noSig);
      } else {
        $close = false;
        $html = null;
      }
    	$clientId = SessionDao::saveSession($sessionId, $actions, $save->t, $save->dos, $save->s, $close, $nosign, $html);
    	if ($act == "sign") {
    	  logit_r($save, 'save');
    	  DataDao::saveOutputDataAndSyncs($save->out, $save->dout);
   	    Procedures_FamHx::saveAll($clientId, get($save->dout, 'fhxprocs'));
   	    Procedures_SocHx::saveAll($clientId, get($save->dout, 'shxprocs'));
   	    if ($save->tid == 1 || $save->tid == 16) { // med note or psych note
    	    Proc_OfficeVisit::record($clientId, $save->dos);
    	  }
    	  SessionDao::unlockSession($sessionId);
    	  if ($login->cerberus) {
          require_once 'php/c/patient-billing/CerberusBilling.php';
          $eid = CerberusBilling::closeNote($sessionId);
          logit_r('encounterID=' . $eid);    	    
    	  }
    	}
      if ($act == "save") {
        $session = JsonDao::buildJSession($sessionId);
        $m = new JAjaxMsg("saveSession", $session->out());
      } else {
        $m = new JAjaxMsg("sign", null);
      }
      break;
      
    // Add addendum
    case "addendum":
      $id = $_GP["id"];
      $html = stripslashes($_GP["html"]);
      SessionDao::addendum($id, $html);
      $m = new JAjaxMsg("addendum", null);
      break;
       
    // Close edit session: unlock note and save preview HTML
    case "close":
      $id = $_GP["sid"];
      $html = stripslashes($_GP["html"]);
      if ($html != "") {
        SessionDao::saveSessionHtml($id, $html);
      }
      SessionDao::unlockSession($id);
      $m = new JAjaxMsg("closeSession", null);
      break;
      
    // Save output data
    case "out":
      $out = jsondecode($_GP["obj"]);
      DataDao::saveOutputData($out);
      $m = new JAjaxMsg("null", null);
      break;
      
    // Get session
    case "get":
      $sessionId = $_GP["sid"];
      $session = JsonDao::buildJSession($sessionId, true, true);
      $m = new JAjaxMsg("getSession", $session->out());
      break;  
    
    // New customized template
    case "saveas":   
      $actions = stripslashes($_GP["a"]);
      $name = stripslashes($_GP["n"]);
      $tid = $_GP["tid"];
      try {
        $id = SessionDao::presetSaveAs($name, $tid, $actions);
        $t = SessionDao::getJTemplatePreset($id);
        $m = new JAjaxMsg("addPreset", $t->out());
      } catch (DuplicateInsertException $e) {
        $id = SessionDao::getPresetIdByName($name);
        $m = new JAjaxMsg("addPresetExists", $id);
      }
      break;
    
    // Clear send-to
    case "clearSendTo":
      $id = $_GP["sid"];
      SessionDao::clearSendTo($id);
      $m = new JAjaxMsg("clearSendTo", null);
      break;
      
    // Customized template save
    case "psave":  
      $tpid = $_GP["tpid"];
      $actions = $_GP["a"];
      //$html = $_GP["h"];
      SessionDao::presetSave($tpid, $actions, null);
      $t = SessionDao::getJTemplatePreset($tpid);
      $m = new JAjaxMsg("savePreset", $t->out());
      break;
    
    // CUstomized template delete
    case "pdelete":  
      $tpid = $_GP["tpid"];
      SessionDao::presetDelete($tpid);
      $m = new JAjaxMsg("deleteSession", "null");
      break;
      
      // Get my template presets
    case "getPresets":  
      $tid = $_GP["tid"];
      $where = " ORDER BY date_updated DESC";
      if ($tid != "") {
        $where = " AND t.template_id=" . $tid . $where; 
      }
      $presets = SessionDao::getJTemplatePresets($where);
      $m = new JAjaxMsg("getPresets", JsonDelegate::outSimpleArray($presets));
      break;
      
    // Get template preset
    case "pget":  
      $tpid = $_GP["tpid"];
      $preset = SessionDao::getJTemplatePreset($tpid);
      $m = new JAjaxMsg("getPreset", $preset->out());
      break;
      
    // New template preset
    case "pnew":  
      $tid = $_GP["tid"];
      $tn = stripslashes($_GP["tn"]);
      $preset = SessionDao::newJTemplatePreset($tid, $tn);
      $m = new JAjaxMsg("newPreset", $preset->out());
      break;
    
    // Search HPI
    case "tSearch":
      $tid = $_GP["t"];
      $suid = $_GP["s"];
      $text = stripslashes($_GP["tx"]);
      $suids = TemplateReaderDao::templateSearch($tid, $suid, $text);
      $m = new JAjaxMsg("tSearch", jsonencode($suids));
      break;
  }
} catch (Exception $e) {
  $m = JAjaxMsg::constructError($e);
}
echo $m->out();
