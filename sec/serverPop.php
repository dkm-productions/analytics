<?php
require_once 'php/data/LoginSession.php';
require_once 'php/dao/TemplateReaderDao.php';
require_once 'php/dao/SessionDao.php';
require_once 'php/dao/UserDao.php';
require_once 'php/dao/FacesheetDao.php';
require_once 'php/dao/JsonDao.php';
require_once 'php/delegates/JsonDelegate.php';
require_once 'php/data/rec/erx/ErxStatus.php'; 
require_once 'php/data/json/JHtmlCombo.php'; 
require_once 'php/data/json/JSessionEnvelope.php'; 
require_once 'php/data/json/JNewNotePop.php'; 
require_once 'php/data/json/JAjaxMsg.php'; 
require_once 'php/data/rec/sql/Procedures_FaceHx.php';
require_once 'img/charts/ChartIndex.php';
//
if (isset($_GET['action'])) {
  $action = $_GET['action'];
  if ($action != 'checkCuTimestamp') logit('serverPop.php?' . implode_with_keys('&', $_GET));
} else {
  $_POST['obj'] = stripslashes($_POST['obj']);
  $action = $_POST['action'];
  $obj = $_POST['obj'];
  logit('serverPop.php (posted)');
  logit_r($_POST);
}
try {
  LoginSession::verify_forServer();
  switch ($action) {
    
    case 'getMyUser':  // returns JUser
      $user = UserDao::getMyUser();
      $m = new JAjaxMsg('getMyUser', $user->out());
      break;
      
    case 'getMyUserGroup':  // returns User
      $ug = UserDao::getMyUserGroup();
      $m = new JAjaxMsg('getMyUserGroup', $ug->out());
      break;
    
    case 'getDocView':  // returns JDocView
      $sid = $_GET['sid'];
      $dv = SessionDao::getDocView($sid);
      $m = new JAjaxMsg('getDocView', $dv->out());
      break;
      
    case 'updateMyUser':
      //$user = JUser::constructFromJson($obj);
      $u = jsondecode($obj);
      UserDao::updateMyUser($u);
      $login->refresh();
      $m = new JAjaxMsg('updateMyUser', null);
      break;
      
    case 'updateMyPw':
      $u = jsondecode($obj);
      try {
        $login->changePassword($u->cpw, $u->pw);
        $m = new JAjaxMsg('updateMyUser', null);
      } catch (UserPasswordException $e) {
        $m = new JAjaxMsg('updateMyUser', q($e->getMessage()));
      }
      break;
  
    case 'updateMyUserGroup':
      $userGroup = JUserGroup::constructFromJson($obj);
      UserDao::updateMyUserGroup($userGroup);
      $login->refresh();
      $m = new JAjaxMsg('updateMyUserGroup', null);
      break;
  
    case 'updateNoteHeader': 
      $se = JSessionEnvelope::constructFromJson($obj);
      SessionDao::updateSessionEnvelope($se);
      $session = JsonDao::buildJSession($se->id);
      $m = new JAjaxMsg('updateNoteHeader', $session->out());
      break;
    
    case 'getSupportUser':  // returns JUser
      $id = $_GET['id'];
      $user = UserDao::getSupportUser($id);
      $m = new JAjaxMsg('getSupportUser', $user->out());
      break;  
    
    case 'newSupportUser':  // returns empty JUser
      $user = UserDao::getNewSupportUser();
      $m = new JAjaxMsg('getSupportUser', $user->out());  
      break;  
      
    case 'updateSupportUser': 
      //$user = JUser::constructFromJson($obj);
      $user = jsondecode($obj);
      $error = null;
      try {
        UserDao::updateSupportUser($user);
      } catch (DuplicateInsertException $e) {
        $error = true;
      }
      $m = new JAjaxMsg('updateSupportUser', $error);
      break;
      
    case 'getTemplateCombo':  // returns JHtmlCombo
      $combo = new JHtmlCombo(
          null,
          TemplateReaderDao::getMyTemplatesAsRows(), 
          'template_id', 
          'name');
      $m = new JAjaxMsg($action, $combo->out());  
      break;
      
    case 'getSendTos':  // returns JHtmlCombo
      $sendTos = SessionDao::getMySendTos();
      $m = new JAjaxMsg($action, $sendTos->out());
      break; 
  
    case 'getReplicateDefs':  // returns {'st':JHtmlCombo,'ovfs':bool}
      $sendTos = SessionDao::getMySendTos();
      $overrideFs = LookupDao::getReplicateOverrideFs();
      logit_r($overrideFs, 'overrideFs');
      $return = array('st' => $sendTos->keyValues, 'ovfs' => toBoolInt($overrideFs));
      $m = new JAjaxMsg($action, jsonencode($return));
      break;
    
    case 'getNewNotePopInfo':  // returns JNewNotePop
      $cid = $_GET['cid'];
      $pop = SessionDao::getNewNotePopInfo($cid);
      $m = new JAjaxMsg($action, $pop->out());  
      break;
  
    case 'getEditHeaderPopInfo':  // returns JNewNotePop
      $sendTos = new JHtmlCombo(
          '[None]',
          UserDao::getUsersOfMyGroupAsRows('active=1'), 
          'user_id', 
          'name',
          $login->userId);
      $pop = new JNewNotePop(null, null, null, $sendTos);
      $m = new JAjaxMsg($action, $pop->out());  
      break;
      
    case 'getHmHist':  // returns {JDataHm,..}
      $cid = $_GET['id'];
      $recs =FacesheetDao::getHmsHistory($cid, false);
      $m = new JAjaxMsg($action, arr($recs));
      break;
    /*
    case 'getFacesheet':  // returns JFacesheet
      $cuLastTimestamp = geta($_GET, 'cu', '');
      $cid = $_GET['id'];
      if ($cuLastTimestamp == '') {
        $facesheet = FacesheetDao::getFacesheet($cid);  
        $m = new JAjaxMsg($action, $facesheet->out());
      } else {
        $cuTimestamp = AuditDao::getClientUpdateTimestamp($cid);
        logit('timestamp check: ' . $cuLastTimestamp . ',' . $cuTimestamp);
        if ($cuTimestamp != $cuLastTimestamp) {
          $facesheet = FacesheetDao::getFacesheet($cid, false);  
          $m = new JAjaxMsg($action, $facesheet->out());
        } else {
          $m = new JAjaxMsg($action, null);  // facesheet not updated since last check, return null
        }
      }
      break;
    */
    case 'updateTimeout':
      $login->setTimeout($_GET['id']);
      $m = new JAjaxMsg($action, null);
      break;
     
    case 'getCharts': 
      $charts = ChartIndex::getChartsJson($_GET['sex'], $_GET['age']);
      $m = new JAjaxMsg($action, $charts);
      break;
      
    case 'getMedHist':  // returns JFacesheet
      $facesheet = FacesheetDao::getMedHistory($_GET['id']);
      $m = new JAjaxMsg($action, $facesheet->out());
      break;
      
    case 'getMedClientHist':  // returns JFacesheet
      $facesheet = FacesheetDao::getMedClientHistory($_GET['id']);
      $m = new JAjaxMsg($action, $facesheet->out());
      break;
      
    case 'saveMed':  // returns JFacesheet
      $med = jsondecode($obj);
      $facesheet = FacesheetDao::saveMed($med);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
  
    case 'deleteLegacyMeds': 
      $facesheet = FacesheetDao::deleteLegacyMeds($_GET['id']);
      $m = new JAjaxMsg($action, $facesheet->out());
      break;
    
    case 'deleteLegacyAllergies': 
      $facesheet = FacesheetDao::deleteLegacyAllergies($_GET['id']);
      $m = new JAjaxMsg($action, $facesheet->out());
      break;
      
    case 'deactivateMed': 
      $facesheet = FacesheetDao::deactivateMed($_GET['id']);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
  
    case 'deactivateMeds':
      $a = jsondecode($obj); 
      $facesheet = FacesheetDao::deactivateMeds($a);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
      
    case 'printMeds': 
      $a = jsondecode($obj);
      $facesheet = FacesheetDao::printRxForMeds($a);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
      
    case 'getVitalQuestions':
      $vqs = FacesheetDao::getVitalQuestions();
      $m = new JAjaxMsg('getVitalQuestions', aarr($vqs));
      break;
      
    case 'getSochxQuestions':
      $qs = FacesheetDao::getSochxQuestions();
      $m = new JAjaxMsg('getSochxQuestions', jsonencode($qs));
      break;
    
    case 'getHxQuestions':
      $qs = FacesheetDao::getHxQuestions($_GET['cat']);
      $m = new JAjaxMsg('getHxQuestions', jsonencode($qs));
      break;    
      
    case 'getFamhxQuestions':
      $qs = FacesheetDao::getFamhxQuestions();
      /*
      $out = cb(
          qqo('+male', aarr($qs['+male']) . C .
          qqo('+female', aarr($qs['+female']))));
          */
      $out = jsonencode($qs);
      $m = new JAjaxMsg('getFamhxQuestions', $out);
      break;    
    
    case 'removeFamhx':
      $cid = $_GET['cid'];
      $puid = $_GET['puid'];
      $facesheet = FacesheetDao::removeFamhx($cid, $puid);  
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
      
    case 'getQuestionByQuid':
      $q = SessionDao::getQuestion($_GET['id']);
      $m = new JAjaxMsg('getQuestionByQuid', $q->out());
      break;
      
    case 'getQuestionsForHproc':
      $id = $_POST['id'];
      $quids = jsondecode($obj);
      $qs = SessionDao::getQuestions($quids);
      $o = cb(qqo('pcid', $id) . C . qqo('qs', aarr($qs)));
      $m = new JAjaxMsg('getQuestionsForHproc', $o);
      break;
      
    case 'saveSochx': 
      $sochx = jsondecode($obj);
      $cid = $_POST['cid'];
      $facesheet = FacesheetDao::saveSochx($cid, $sochx);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
    
    case 'saveSocialHx': 
      $o = jsondecode($obj);
      $cid = $o->cid;
      $sochx = $o->rec;
      $facesheet = FacesheetDao::saveSochx($cid, $sochx);
      Procedures_SocHx::saveAll($cid, $o->shxprocs);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
  
    case 'saveHx': 
      $rec = jsondecode($obj);
      $cat = $_POST['cat'];
      $cid = $_POST['cid'];
      $facesheet = FacesheetDao::saveHx($cid, $cat, $rec);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
      
    case 'saveMedSurgHx':
      $o = jsondecode($obj);
      $facesheet = FacesheetDao::saveHx($o->cid, $o->cat, $o->rec);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
  
    case 'saveFamhx': 
      $rec = jsondecode($obj);
      $cid = $_POST['cid'];
      $facesheet = FacesheetDao::saveFamhx($cid, $rec);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
  
    case 'saveFamHxAdopted': 
      $cid = $_GET['id'];
      $facesheet = FacesheetDao::saveFamhx_asAdopted($cid);
      Procedures_FamHx::clear($cid);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
      
    case 'saveFamHxUnknown': 
      $cid = $_GET['id'];
      $facesheet = FacesheetDao::saveFamhx_asUnknown($cid);
      Procedures_FamHx::clear($cid);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
      
    case 'saveFamHxClear': 
      $cid = $_GET['id'];
      $facesheet = FacesheetDao::saveFamhx_asClear($cid);
      Procedures_FamHx::clear($cid);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
      
    case 'saveFamilyHx':
      $o = jsondecode($obj);
      $rec = $o->rec;
      $cid = $o->cid;
      $facesheet = FacesheetDao::saveFamhx($cid, $rec);
      Procedures_FamHx::saveAll($cid, $o->fhxprocs);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
      
    case 'saveHxProcs': 
      $value = $_POST['obj'];  // don't jsondecode
      $cat = $_POST['cat'];
      $cid = $_POST['cid'];
      $facesheet = FacesheetDao::saveHxProcs($cid, $cat, $value); 
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
      
    case 'saveMedSurgHxProcs':
      $o = jsondecode($obj);
      $cat = $o->cat;
      $cid = $o->cid;
      $value = jsonencode($o->rec); 
      $facesheet = FacesheetDao::saveHxProcs($cid, $cat, $value); 
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
      
    case 'saveFamHxSopts':
      $value = $_POST['obj'];  // don't jsondecode
      $suid = $_POST['suid'];
      $cid = $_POST['cid'];
      $facesheet = FacesheetDao::saveFamHxSopts($cid, $suid, $value); 
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
  
    case 'saveSuidQuestion':
      $o = jsondecode($obj);
      $suid = $o->suid;
      $cid = $o->cid;
      $value = jsonencode($o->rec);
      $facesheet = FacesheetDao::saveFamHxSopts($cid, $suid, $value); 
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
  
    case 'getProcQuestion':
      $q = FacesheetDao::getProcQuestion($_GET['cat']);
      $m = new JAjaxMsg('getProcQuestion', $q->out());
      break;
      
    case 'getSuidQuestion':
      $q = FacesheetDao::getSuidQuestion($_GET['suid']);
      $m = new JAjaxMsg('getSuidQuestion', $q->out());
      break;
  
    case 'saveImmun': 
      $immun = jsondecode($obj);
      $facesheet = FacesheetDao::saveImmun($immun);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
      
    case 'getTracking': 
      $facesheet = FacesheetDao::getTrackingFacesheet($_GET['id']);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
      
    case 'saveVital':  
      $vital = jsondecode($obj);
      $facesheet = FacesheetDao::saveVital($vital);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
  
    case 'deleteVital': 
      $facesheet = FacesheetDao::deleteVital($_GET['id']);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
  
    case 'saveDiagnosis':  
      $d = jsondecode($obj);
      $facesheet = FacesheetDao::saveDiagnosis($d);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
  
    case 'deactivateDiagnosis': 
      $facesheet = FacesheetDao::deactivateDiagnosis($_GET['id']);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
  
    case 'deactivateDiagnoses': 
      $a = jsondecode($obj); 
      $facesheet = FacesheetDao::deactivateDiagnoses($a);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
  /*
    case 'checkCuTimestamp': 
      $ts = AuditDao::getClientUpdateTimestamp($_GET['id']);
      $m = new JAjaxMsg('checkCuTimestamp', q($ts));
      break;
  */
    case 'saveHist':  
      $hist = jsondecode($obj);
      try {
        $facesheet = FacesheetDao::saveHist($hist);
        $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      } catch (JDataException $e) {
        $m = new JAjaxMsg('dataException', $e->out());
      }
      break;
  
    case 'saveDataHistProcs':
      LookupDao::saveOurDataHistProcs($_POST['hcat'], $obj);
      $facesheet = FacesheetDao::getFacesheetHist($_POST['cid'], true);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
      
    case 'saveHm':  
      $hm = jsondecode($obj);
      $facesheet = FacesheetDao::saveHm($hm);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
  
    case 'saveHmInt':  
      $hm = jsondecode($obj);
      $facesheet = FacesheetDao::saveHmInt($hm);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
  
    case 'addFacesheetHm':
      $hm = jsondecode($obj);
      $facesheet = FacesheetDao::addFacesheetHm($hm);
      $m = new JAjaxMsg('addFacesheetHm', $facesheet->out());
      break;
  
    case 'saveDataHmProcs':
      LookupDao::saveOurDataHmProcs($obj);
      $facesheet = FacesheetDao::getFacesheetHm($_POST['cid']);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
    
    case 'customizeHmProcs':
      $o = jsondecode($obj);
      $cid = $o->cid;
      $rec = $o->rec;
      LookupDao::saveOurDataHmProcs(jsonencode($rec));
      $facesheet = FacesheetDao::getFacesheetHm($cid);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
    
    case 'deactivateHist': 
      $facesheet = FacesheetDao::deactivateHist($_GET['id']);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
      
    case 'deactivateHm': 
      $facesheet = FacesheetDao::deactivateHm($_GET['id']);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
  
    case 'deactivateHms':
      $a = jsondecode($obj); 
      $facesheet = FacesheetDao::deactivateHms($a);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
     
    case 'getAllergyQuestion':
      $q = FacesheetDao::getAllergyQuestion();
      $m = new JAjaxMsg('getAllergyQuestion', $q->out());
      break;
       
    case 'saveAllergy':  
      $allergy = jsondecode($obj);
      $facesheet = FacesheetDao::saveAllergy($allergy);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
  
    case 'deactivateAllergy': 
      $facesheet = FacesheetDao::deactivateAllergy($_GET['id']);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
      
    case 'deactivateAllergies':
      $a = jsondecode($obj); 
      $facesheet = FacesheetDao::deactivateAllergies($a);
      $m = new JAjaxMsg('refreshFacesheet', $facesheet->out());
      break;
  
    case 'hideSticky':
      $login->hideSticky($_GET['id']);
      $m = new JAjaxMsg('null', null); 
      break;
      
    default:
      $m = new JAjaxMsg('error', $action);
  }
} catch (Exception $e) {
  $m = JAjaxMsg::constructError($e);
} 
if ($m != null) 
  echo $m->out();
  