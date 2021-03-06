<?php
require_once 'php/data/LoginSession.php';
require_once 'php/data/rec/sql/Clients.php';
require_once 'php/data/rec/sql/_PracticeInboxRec.php';
require_once 'php/data/rec/sql/_PracticeRec.php';
require_once 'php/data/rec/sql/Procedures.php';
require_once 'php/data/rec/sql/OrderEntry.php';
require_once 'php/data/rec/group-folder/GroupFolder_Practices.php';
//
/**
 * Practice Practice Interface
 * @author Chuck Sauer
	Last change: CS 7/29/2016 11:40:27 AM
 */
class CCD_Practices {
  //
  /** Import from single file upload */
  static function import_fromUpload() {
   // static::setVersion('2.5.1');
    global $login;
    $folder = GroupFolder_Practices::open();
    $file = $folder->upload();
    $practice = Practice::fetch(8)/*NIST TEST*/;
    $msg = PracticeMessage::fromPractice($file->readContents(), $practice);
    logit_r($msg);
    if ($msg)
      self::import($practice, $msg, $login->userGroupId, $file->filename);
  }
  /** Import from single SFTP file */
  static function import_fromFtpFile(/*FtpFile*/$file) {
    static::setVersion('2.3.1');
    $msgs = PracticeMessage::fromFtpFile($file, $file->Practice);
    blog($msgs, 'msgs');
    foreach ($msgs as $msg)
      static::import_fromFtpMsg($file, $msg);
  }
  /** Import from web service */
  static function import_fromWebService(/*PracticeServicesPost*/$post) {
    static::setVersion('2.3.1');
    $practice = Practice::fetch_byWebService($post);
    $msg = PracticeMessage::fromPractice($post->msg, $practice);
    if ($msg) {
      $ugid = $msg->getUgid();
      $login = LoginSession::loginBatch($ugid, __CLASS__);
      self::import($practice, $msg, $ugid);
    }
  }
  static function setVersion($version) {
    switch ($version) {
      case '2.5.1':
        require_once 'php/data/ccd-2.5.1/msg/_PracticeMessage.php';
        require_once 'php/data/ccd-2.5.1/msg/practices/PracticeMessage.php';
        break;
      default:
        require_once 'php/data/ccd/msg/_PracticeMessage.php';
        require_once 'php/data/ccd/msg/practices/PracticeMessage.php';
        break;
    }
  }
  //
  protected static function import_fromFtpMsg($file, $msg) {
    if ($msg) {
      $ugid = $msg->getUgid();
      $login = LoginSession::loginBatch($ugid, __CLASS__);
      self::import($file->Practice, $msg, $ugid, $file->filename);
    } else {
      throw new PracticeMsgInvalidEx($file->getFilepath());
    }
  }
  protected static function import(/*Practice*/$practice, /*PracticeMessage*/$msg, $ugid, $filename = null) {
    $valid = $msg->reconcile();
    $pdf = $msg->getDecodedPdf();
    $inbox = PracticeInbox_New::add($practice->practiceId, $msg, $msg->Header->getSource(), $ugid, $filename, $pdf);
    /*
    if ($valid) {
      $inbox->cid = $msg->getClientId();
      static::saveIntoChart($msg, $inbox);
    } 
    */
  }
  /** Save reconciliation, return PracticeRecon if still errors */
  static function /*PracticeRecon*/savePracticeRecon($inboxId, /*PracticeMessage with updates, e.g. Proc_, ProcResult_, TrackItem_*/$obj) {
    global $login;
    logit_r('savePracticeRecon');
    $inbox = PracticeInbox::fetch($inboxId)->applyRecon($obj);
    logit_r('111');
    $msg = $inbox->getMessage();
    logit_r('222');
    $valid = $msg->reconcile_afterUpdates($inbox->cid, $obj);
    logit_r($valid, '333 valid');
    if (! $valid) {
      logit_r('444');
      return PracticeRecon::from($inbox, $msg);
    } else { 
      logit_r('555');
      static::saveIntoChart($msg, $inbox, $login->userId);
    }
  }
  protected static function saveIntoChart($msg, $inbox, $userId = null/*to auto-reconcile*/) {  
    Dao::begin();
    try {
      logit_r($inbox, 'inbox');
      $inbox = $inbox->saveAsReconciled($userId);
      $notes = $inbox->makeReconcileNotes();
      $msg->saveIntoChart($notes, $userId, $inbox->ccdInboxId);
      Dao::commit();
    } catch (Exception $e) {
      Dao::rollback();
      throw $e;
    }
  }
  static function /*PracticeInbox[]*/getInboxes() {
    global $login;
    $recs = PracticeInbox::fetchUnreconciled($login->userGroupId);
    return $recs;
  }
  static function removeInbox($inboxId) {
    $inbox = PracticeInbox::fetch($inboxId);
    SqlRec::delete($inbox);
  }
  static function /*int*/getInboxCt() {
    $recs = self::getInboxes();
    return (empty($recs)) ? 0 : count($recs);
  }
  static function /*OruMessage*/getInboxMessage($id) {
    $inbox = PracticeInbox::fetch($id);
    if ($inbox) 
      return $inbox->getMessage();
  }
  static function /*PracticeRecon*/getPracticeRecon($id) {
    $inbox = PracticeInbox::fetch($id);
    if ($inbox) {
      $rec = PracticeRecon::from($inbox);
      return $rec;
    }
  }
  static function /*PracticeRecon*/assignInboxToClient($inboxId, $cid) {
     $inbox = PracticeInbox::fetch($inboxId);
     if ($inbox) { 
       $inbox = $inbox->saveClient($cid);
       return PracticeRecon::from($inbox);
     }
  }
}
//
class Practice extends PracticeRec {
  //
  public $practiceId;
  public $uid;
  public $name;
  public $status;
  public $sendMethod;
  public $sftpFolder;
  public $id;
  public $pw;
  public $address;
  public $contact;
  //
  public function getPracticeel() {
    return $this->uid . ' ' . $this->name; 
  }
  public function toJsonObject(&$o) {
    unset($o->status);
    unset($o->sendMethod);
    unset($o->sftpFolder);
    unset($o->id);
    unset($o->pw);
  }
  //
  static function fetchByUid($uid) {
    $c = new static();
    $c->uid = $uid;
    $me = static::fetchOneBy($c);
    return $me;
  }
  static function fetch_byAuth($id, $pw) {
    $me = static::fetchByUid($id);
    if ($me && $me->pw == $pw) {
      return $me;
    }
  }
  static function fetch_byWebService(/*PracticeServicesPost*/$post) {
    $me = static::fetchByUid($post->uid);
    if ($me == null) 
      throw new PracticeUidNotFoundEx();
    if ($me->status == static::STATUS_INACTIVE)
      throw new PracticeInactiveEx();
    if ($me->pw != $post->pw)
      throw new PracticePwInvalidEx();
    return $me;
  }
  static function fetchAll_forDropboxPolling() {
    $c = new static();
    $c->status = static::STATUS_ACTIVE;
    //$c->sendMethod = CriteriaValue::in(array(static::SEND_METHOD_SFTP, static::SEND_METHOD_SFTP_PULL)); -- modified so that webservice dumps to in folder, so all methods should apply here
    $mes = static::fetchAllBy($c);
    return $mes;
  }
  static function fetchAll_forSftpPull() {
    $c = new static();
    $c->status = static::STATUS_ACTIVE;
    $c->sendMethod = static::SEND_METHOD_SFTP_PULL;
    $mes = static::fetchAllBy($c);
    return $mes;
  }
} 
class PracticeMsgInvalidEx extends Exception {}
class PracticeLoginInvalidEx extends Exception {}
class PracticeUidNotFoundEx extends PracticeLoginInvalidEx {}
class PracticePwInvalidEx extends PracticeLoginInvalidEx {}
class PracticeInactiveEx extends PracticeLoginInvalidEx {}
class PracticeReconEx extends Exception {}
//
/**
 * SqlRec PracticeInbox
 */
class PracticeInbox extends PracticeInboxRec {
  //
  public $ccdInboxId;
  public $userGroupId;
  public $practiceId;
  public $msgType; 
  public $source;
  public $filename;
  public $dateReceived;
  public $patientName;
  public $cid;
  public $status;
  public $reconciledBy;
  public $data;
  public $headerTimestamp;
  public $placerOrder;
  public /*Practice*/ $Practice;
  //
  public function getJsonFilters() {
    return array(
      'dateReceived' => JsonFilter::informalDateTime());
  }
  public function getMessage() {
    if ($this->practiceId) {
      $this->Practice = Practice::fetch($this->practiceId);
      Practice_Practices::setVersion('2.3.1');
      $msg = PracticeMessage::fromPractice($this->data, $this->Practice);
      return $msg;
    }
  }
  public function getReconciledMessage() {
    $msg = $this->getMessage();
    if ($msg) {
      $msg->reconcile($this->cid);
      return $msg;
    }
  }
  public function saveClient($cid) {
    logit('saveClient ' . $cid);
    $this->cid = $cid;
    $this->save();
    return $this;
  }
  public function saveAsReconciled($userId) {
    if (empty($this->cid))
      throw new PracticeReconEx('Patient not assigned to inbox ' . $this->ccdInboxId);
    $dupe = static::fetchByOrder($this->userGroupId, $this->practiceId, $this->placerOrder, $this->ccdInboxId);
    logit_r($this, 'saveAsRecon');
    logit_r($dupe, 'dupe');
    if ($dupe && $dupe->cid == $this->cid) {
      logit_r('deleting dupe procs');
      Procedures::deleteForPracticeInbox($dupe->ccdInboxId, $this->userGroupId, $this->cid);
      $dupe->saveAsCorrected();
    }
    $this->reconciledBy = $userId;
    $this->status = static::STATUS_RECONCILED;
    $this->save();
    return static::fetchWithReconciler($this->ccdInboxId);
  }
  public function makeReconcileNotes() {
    $a = array("Via Practice Recon: $this->source ($this->ccdInboxId) received");
    $a[] = formatDateTime($this->dateReceived);
    if (isset($this->User_reconciledBy))
      $a[] = "saved by " . $this->User_reconciledBy->name; 
    return implode(' ', $a);
  }
  public function applyRecon($obj) {
    $client = getr($obj, 'PatientId.Client_');
    if ($client && $client->clientId != $this->cid)
      $this->saveClient($client->clientId);
    return $this;
  }
  public function saveAsCorrected() {
    $this->status = static::STATUS_CORRECTED;
    $this->save();
  }
  //
  static function fetchByOrder($ugid, $practiceId, $order, $notId = null) {
    $c = new static();
    $c->userGroupId = $ugid;
    $c->status = CriteriaValue::notEquals(static::STATUS_CORRECTED);
    $c->placerOrder = $order;
    if ($notId) {
      $c->ccdInboxId = CriteriaValue::notEquals($notId);
    }
    return static::fetchOneBy($c);
  }
  static function fetchWithReconciler($id) {
    $c = new static($id);
    $c->User_reconciledBy = new UserStub();
    return self::fetchOneBy($c);
  }
  static function fetchUnreconciled($ugid) {
    $c = new static();
    $c->userGroupId = $ugid;
    $c->status = static::STATUS_UNRECONCILED;
    $recs = static::fetchAllBy($c);
    return $recs;
  }
}
class PracticeInbox_New extends PracticeInboxRec {
  //
  public $ccdInboxId;
  public $userGroupId;
  public $practiceId;
  public $msgType; 
  public $source;
  public $filename;
  public $dateReceived;
  public $patientName;
  public $cid;
  public $status;
  public $reconciledBy;
  public $data;
  public $headerTimestamp;
  public $placerOrder;
  public $pdf;
  //
  static function add($practiceId, $msg, $source, $ugid, $filename = null, $pdf) {
    $order = $msg->getOrder();
    logit_r($order, 'order');
    $me = static::from($practiceId, $msg, $source, $ugid, $filename, $order, $pdf);
    logit_r($me, 'me');
    if ($me) {
      $dupe = PracticeInbox::fetchByOrder($ugid, $practiceId, $order);
      if ($dupe) {
        $me->cid = $dupe->cid;
        if ($dupe->status == static::STATUS_UNRECONCILED) {
          PracticeInbox::delete($dupe);
        }
      }
      $me->save($ugid);
    }
    return $me;
  }
  static function from($practiceId, /*PracticeMessage*/$msg, /*string*/$source, $ugid, $filename = null, $placerOrder = null, $pdf = null) {
    if ($msg) {
      $head = $msg->Header;
      $patient = $msg->getPatientId();
      $me = new static();
      $me->userGroupId = $ugid;
      $me->practiceId = $practiceId;
      $me->msgType = substr($head->msgType->_data, 0, 7);
      $me->source = ($source) ? $source : 'None';
      $me->filename = $filename;
      $me->dateReceived = nowNoQuotes();
      $me->status = static::STATUS_UNRECONCILED;
      $me->placerOrder = $placerOrder;
      $me->pdf = $pdf;
      if ($patient) 
        $me->patientName = $patient->name->makeFullName();
      if (isset($patient->Client_))
        $me->cid = $patient->Client_->clientId;
      $me->data = $msg->getData();
      if (! empty($head->timestamp))
        $me->headerTimestamp = $head->timestamp->asFormatted();
      return $me;
    }
  }
}
class PracticeRecon extends Rec {
  //
  public /*PracticeInbox*/$Inbox;
  public /*PracticeMessage*/$Msg;
  //
  public function toJsonObject(&$o) {
    //logit_r($o, 'toJsonObject');
    if (isset($o->Msg))
      $o->Msg = $o->Msg->sanitize();
  }
  //
  /**
   * @param PracticeInbox $inbox
   * @param PracticeMessage $msg (optional) 
   * @return PracticeRecon
   */
  static function from($inbox, $msg = null) {
    if ($msg == null)
      $msg = $inbox->getReconciledMessage();
    $me = new static();
    $me->Inbox = $inbox;
    $me->Msg = $msg;
    return $me;
  }
}
