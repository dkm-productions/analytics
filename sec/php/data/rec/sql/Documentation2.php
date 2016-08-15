<?php
require_once 'php/c/sessions/Sessions.php';
require_once 'php/data/rec/sql/_SchedRec.php';
require_once 'php/data/rec/sql/_VisitSummaryRec.php';
require_once 'php/data/rec/sql/Diagnoses.php';
require_once 'php/data/rec/sql/Messaging.php';
require_once 'php/data/rec/sql/Scanning.php';
require_once 'php/data/rec/sql/Procedures.php';
require_once 'php/data/rec/sql/OrderEntry.php';
require_once 'php/data/rec/sql/VisitSummaries.php';
require_once 'php/data/rec/sql/Messaging_DocStubReview.php';
require_once 'php/data/xml/ClinicalXmls.php';
//
/**
 * Documentation DAO
 * @author Warren Hornsby
 */
class Documentation {
  //
  /**
   * @param int $cid
   * @return array(DocStub,..)
   */
  static function getAll($cid) {
    $recs = DocStub::fetchAllTypes($cid);
    return $recs;
  }
  /**
   * @param DocStub rec e.g. {'type':1,'id':3200}
   * @return Rec e.g. DocSession
   */
  static function preview($rec) {
    global $login;
    $rec = DocStub::fetchForPreview($rec, $login->userId);
    return $rec;
  }
  /**
   * @param DocStub rec
   * @return DocStub
   */
  static function refetch($rec) {
    $rec = DocStub::refetch($rec);
    return $rec;
  }
  /**
   * @param int threadId
   * @return Rec e.g. DocSession
   */
  static function setReviewed($threadId) {
    $thread = Messaging_DocStubReview::postReviewed($threadId);
    return static::preview($thread->Stub);
  }
  /**
   * @return array(DocStub,..)
   */
  static function getUnreviewed() {
    //$threads = Messaging_DocStubReview::getUnreviewedThreads();
    //$recs = DocStub::fromThreads($threads);
    //return Rec::sort($recs, new RecSort('Unreviewed.Client.lastName', 'cid'));
    global $login;
    $recs = DocStub::fetchAllUnreviewed($login->userGroupId, $login->userId);
    return Rec::sort($recs, new RecSort('Unreviewed.Client.lastName', 'cid'));
  }
}
class DocStub extends Rec {
  //
  public $type;
  public $id;
  public $cid;
  public $date;
  public $timestamp;
  public $name;
  public $desc;
  public $signed;  // '04-May-2012 3:55PM by Dr. Clicktate'
  public $provider;
  public $areas;
  //
  const TYPE_SESSION = 1;
  const TYPE_MSG = 2;
  const TYPE_APPT = 3;
  const TYPE_ORDER = 4;
  const TYPE_SCAN = 5;
  const TYPE_SCAN_XML = 7;
  const TYPE_RESULT = 6;
  const TYPE_VISITSUM = 8;
  static $TYPES = array(
    self::TYPE_SESSION => 'Document',
    self::TYPE_MSG => 'Message',
    self::TYPE_APPT => 'Appointment',
    self::TYPE_ORDER => 'Order',
    self::TYPE_SCAN => 'Scan',
    self::TYPE_SCAN_XML => 'Electronic',
    self::TYPE_RESULT => 'Proc/Result',
    self::TYPE_VISITSUM => 'Visit Summary');
  //
  public function getJsonFilters() {
    return array(
      'date' => JsonFilter::editableDateApprox());
  }
  public function toJsonObject(&$o) {
    $o->lookup('type', self::$TYPES);
  }
  public function lookupType() {
    return static::$TYPES[$this->type];
  }
  public function setDate($date) {
    $this->date = $date;
  }
  public function setSigned($date, $by) {
    $this->signed = formatDateTime($date) . ' by ' . UserGroups::lookupUser($by);
  }
  //
  /**
   * @param int $cid
   * @return array(DocStub,..)
   */
  static function fetchAllTypes($cid) {
    $sessions = self::fetchAll('DocSession', $cid);
    $msgs = self::fetchAll('DocMessage', $cid);
    $appts = self::fetchAll('DocAppt', $cid);
    $orders = self::fetchAll('DocOrder', $cid);
    $scans = self::fetchAll('DocScan', $cid);
    $results = self::fetchAll('DocProc', $cid);
    $visits = self::fetchAll('DocVisitSum', $cid);
    $all = array_merge($sessions, $msgs, $appts, $orders, $scans, $results, $visits);
    return Rec::sort($all, new RecSort('-date', '-timestamp', 'id'));
  }
  static function fetchAllUnreviewed($ugid, $userId) {
    $sessions = self::fetchUnreviewedStubs(static::TYPE_SESSION, $ugid, $userId);
    $scans = self::fetchUnreviewedStubs(static::TYPE_SCAN, $ugid, $userId);
    $results = self::fetchUnreviewedStubs(static::TYPE_RESULT, $ugid, $userId);
    return array_merge($sessions, $scans, $results);
  }
  /**
   * @param DocStub $stub
   * @return DocStub
   */
  static function refetch($stub) {
    $rec = self::fetch($stub->type, $stub->id);
    return $rec;
  }
  /**
   * @param DocStub $stub
   * @return SqlRec e.g. DocSession
   */
  static function fetchForPreview($stub, $userId) {
    $class = static::getRecClass($stub->type);
    $rec = $class::fetchForPreview($stub->id, $userId, get($stub, 'cid'));
    return $rec;
  }
  /**
   * @param MsgThread_Stub[] $threads
   * @return array(DocStub,..)
   */
  static function fromThreads($threads) {
    $mes = array();
    foreach ($threads as $thread) {
      $me = static::fromThread($thread);
      if ($me) 
        $mes[] = $me;
      else 
        MsgInbox_Stub::closeAll($thread->threadId);
    }
    return $mes;
  }
  static function fromThread($thread) {
    $me = DocStub::fetch($thread->stubType, $thread->stubId);
    if ($me) {
      $me->Unreviewed = $thread;
      return $me;
    }
  }
  /**
   * @param DocStub $stub
   * @param int $userId
   */
  static function postSignature($stub, $userId) {
    $class = self::getRecClass($stub->type);
    if ($class == 'DocSession')
      DocSession::postSignature($stub->id, $userId);
  }
  /**
   * @param DocStub::TYPE $type
   * @param int $id
   * @return DocStub 
   */
  static function fetch($type, $id) {
    if ($type && $id) {
      $class = static::getRecClass($type);
      $c = $class::asCriteria(null);
      $c->setPkValue($id);
      $rec = $class::fetchOneBy($c);
      if ($rec)
        return $rec->asStub();
    }
  }
  static function fetchWithPreview($type, $id) {
    $me = static::fetch($type, $id);
    $me->Preview = static::fetchForPreview($me, null);
    return $me;
  }
  //
  static function asStubs($recs) {
    foreach ($recs as &$rec) 
      $rec = $rec->asStub();
    return $recs;
  }
  static function fetchAll($class, $cid) {
    $c = $class::asCriteria($cid);
    $recs = $class::fetchAllBy($c);
    return static::asStubs($recs);
  }
  static function fetchUnreviewedStubs($stubType, $ugid, $userId) {
    $class = static::getRecClass($stubType);
    $c = $class::asCriteria(null);
    $c->userGroupId = $ugid;
    $c->Unreviewed = MsgThread_Stub::asJoin_requiresUnreviewed($userId, $stubType);
    $recs = $class::fetchAllBy($c);
    return static::asStubs($recs);
  }
  static function getRecClass($type) {
    switch ($type) {
      case self::TYPE_SESSION:
        return 'DocSession';
      case self::TYPE_MSG:
        return 'DocMessage';
      case self::TYPE_APPT: 
        return 'DocAppt';
      case self::TYPE_ORDER:
        return 'DocOrder';
      case self::TYPE_SCAN:
        return 'DocScan';
      case self::TYPE_SCAN_XML:
        return 'DocScan_Xml';
      case self::TYPE_RESULT:
        return 'DocProc'; 
      case self::TYPE_VISITSUM:
        return 'DocVisitSum'; 
    }
  }
}
/**
 * Preview Recs
 */
class DocSession extends SessionNote implements ReadOnly {
  //
  public function formatDiagnoses() {
    if (isset($this->Diagnoses)) {
      $names = DocDiagnosis::formatNames($this->Diagnoses);
      return implode(', ', $names);
    }
  }
  public function asStub() {
    $rec = $this->createStub();
    $rec->type = $this->getDocStubType();
    $rec->id = $this->sessionId;
    $rec->cid = $this->clientId;
    $rec->setDate($this->dateService);
    $rec->timestamp = $this->dateCreated;
    $rec->name = $this->getLabel();
    $rec->desc = $this->formatDiagnoses();
    if ($this->isClosed())
      $rec->setSigned($this->dateClosed, $this->closedBy); 
    return $rec; 
  }
  protected function createStub() {
    return new DocStub();
  }
  public function getDocStubType() {
    return DocStub::TYPE_SESSION;
  }
  //
  static function asCriteria($cid) {
    $c = new static();
    $c->clientId = $cid;
    $c->Diagnoses = array(new DocDiagnosis());
    return $c;
  }
  static function fetchForPreview($id) {
    $rec = parent::fetch($id);
    return $rec;
  }
  static function postSignature($sid, $userId) {
  }
}
class DocDiagnosis extends Diagnosis implements ReadOnly {
  //
  public function formatName() {
    $name = $this->text;
    if ($this->icd) 
      $name .= " ($this->icd)";
    return $name;
  }
  //
  static function formatNames($recs) {
    $names = array();
    foreach ($recs as $rec) 
      $names[] = $rec->formatName();
    return $names;
  }  
}
class DocMessage extends MsgThread implements ReadOnly {
  //
  public function asStub() {
    $rec = new DocStub();
    $rec->type = DocStub::TYPE_MSG;
    $rec->id = $this->threadId;
    $rec->cid = $this->clientId;
    $rec->setDate($this->dateCreated);
    $rec->timestamp = $this->dateCreated;
    $rec->name = $this->getLabel();
    $rec->desc = $this->creator;
    return $rec; 
  }
  //
  static function asCriteria($cid) {
    $c = new self();
    $c->clientId = $cid;
    $c->type = CriteriaValue::notEqualsNumeric(MsgThread::TYPE_STUB_REVIEW);
    return $c;
  }
  static function fetchForPreview($id) {
    $rec = self::fetchWithPosts($id);
    $rec->_html = self::formatHtml($rec);
    return $rec;
  }
  static function formatHtml($rec) {
    $h = array();
    foreach ($rec->MsgPosts as $post) {
      $h[] = '<div class="posthead"><b>From:</b> ';
      $h[] = $post->author;
      if ($post->sendTo) {
        $h[] = ' <b>To:</b> ';
        $h[] = $post->sendTo;
      }
      $h[] = '<br><b>Date:</b> ';
      $h[] = $post->dateCreated;
      $h[] = '</div>';
      $h[] = $post->body;
    }
    return implode($h);
  }
}
class DocAppt extends SchedRec implements ReadOnly {
  //
  public function asStub() {
    $rec = new DocStub();
    $rec->type = $this->getDocStubType();
    $rec->id = $this->schedId;
    $rec->cid = $this->clientId;
    $rec->setDate($this->date);
    $rec->timestamp = $this->date;
    $rec->name = $this->getLabel();
    $rec->desc = null;
    return $rec; 
  }
  public function getDocStubType() {
    return DocStub::TYPE_APPT;
  }
  //
  static function asCriteria($cid) {
    $c = new self();
    $c->clientId = $cid;
    return $c;
  }
  static function fetchForPreview($id) {
    $rec = parent::fetch($id);
    return $rec;
  }
}
class DocOrder extends TrackItem implements ReadOnly {
  //
  public function asStub() {
    $rec = new DocStub();
    $rec->type = DocStub::TYPE_ORDER;
    $rec->id = $this->trackItemId;
    $rec->cid = $this->clientId;
    $rec->setDate($this->orderDate);
    $rec->timestamp = $this->orderDate;
    $rec->name = $this->trackDesc;
    $rec->desc = $this->orderNotes;
    return $rec; 
  }
  //
  static function asCriteria($cid) {
    $c = new self();
    $c->clientId = $cid;
    $c->closedDate = CriteriaValue::isNull();
    return $c;
  }
  static function fetchForPreview($id) {
    $rec = self::fetch($id);
    return $rec;
  }
}
class DocScan extends ScanIndex implements ReadOnly {
  //
  public function formatName() {
    $label = $this->getTypeName();
    if ($this->Ipc)
      $label .= ': ' . $this->Ipc->name;
    return $label;
  }
  public function formatDesc() {
    $d = array();
    if (isset($this->Ipc))
      $d[] = $this->Ipc->name;
    $d[] = Provider::formatProviderFacility($this);
    return implode(' ', $d);
  }
  public function asStub() {
    $rec = new DocStub();
    $rec->type = $this->getDocStubType();
    $rec->id = $this->scanIndexId;
    $rec->cid = $this->clientId;
    $rec->setDate($this->datePerformed);
    $rec->timestamp = $this->datePerformed;
    $rec->name = $this->formatName();
    $rec->desc = $this->formatDesc();
    if (isset($this->Provider))
      $rec->provider = $this->Provider->formatName();
    if (isset($this->Address_addrFacility))
      $rec->facility = $this->Address_addrFacility->name;
    //$rec->Unreviewed = getr($this, 'Unreviewed.Inbox');
    $rec->Unreviewed = getr($this, 'Unreviewed');
    return $rec;
  }
  public function getDocStubType() {
    if ($this->scanType == ScanIndex::TYPE_XML) 
      return DocStub::TYPE_SCAN_XML;
    else
      return DocStub::TYPE_SCAN;
  }
  //
  static function fetchAll($cid) {
    $c = self::asCriteria($cid);
    return self::fetchAllBy($c);
  }
  static function asCriteria($cid) {
    $c = new static();
    $c->clientId = $cid;
    $c->Ipc = Ipc::asOptionalJoin();
    $c->Provider = new Provider();
    $c->Address_addrFacility = new Address();
    $c->Proc = CriteriaJoin::notExists(new Proc());
    $c->Unreviewed = MsgThread_Stub::asUnreviewedJoin($c);
    return $c;
  }
  static function fetchForPreview($id, $userId) {
    $rec = static::fetch($id);
    $rec->ReviewThread = MsgThread_Stub::fetchForPreview($rec, $userId);
    return $rec;
  }
}
class DocScan_Xml extends ScanIndex_Xml implements ReadOnly {
  //
  static function fetchForPreview($id) {
    $rec = self::fetch($id);
    $file = $rec->getGroupFile();
    $xml = ClinicalXmls::parse($file->readContents());
    $rec->_html = $xml->asHtml();
    return $rec;
  }
}
class DocVisitSum extends VisitSummaryRec implements ReadOnly {
  //
  public $clientId;
  public $finalId;
  public $dos;
  public $sessionId;
  public $finalHead;
  public $finalBody;
  public $finalizedBy;
  public $diagnoses;
  public $iols;
  public $instructs;
  public $vitals;
  public $meds;
  //
  public function asStub() {
    $rec = new DocStub();
    $rec->type = DocStub::TYPE_VISITSUM;
    $rec->id = $this->finalId;
    $rec->cid = $this->clientId;
    $rec->setDate(unixToSqlDate($this->finalId));
    $rec->timestamp = unixToSqlTime($this->finalId);
    $rec->name = 'Visit Summary: ' . formatDate($this->dos);
    return $rec; 
  }
  //
  static function asCriteria($cid) {
    $c = new static();
    $c->clientId = $cid;
    $c->finalId = CriteriaValue::greaterThanNumeric(0);
    return $c;
  }
  static function fetchAllBy($c) {
    $recs = parent::fetchMapBy($c, 'sessionId');
    return array_values($recs);
  }
  static function fetchForPreview($id, $userId, $cid) {
    $c = new static();
    $c->clientId = $cid;
    $c->finalId = $id;
    return static::fetchOneBy($c);
  }
}
class DocProc extends Proc implements ReadOnly {
  //
  public function formatName() {
    return ($this->Ipc) ? $this->Ipc->name : null;
  }
  public function formatDesc() {
    $s = Ipc::$CATS[$this->Ipc->cat];
    if ($this->Provider) 
      $s .= " - " . Provider::formatProviderFacility($this);
    //if (! empty($this->ProcResults))
    //  $s .= ' ' . $this->formatSummarizeResults($this, $this->ProcResults);
    return $s;
  }
  public function asStub() {
    $rec = new DocStub();
    $rec->type = static::getDocStubType();
    $rec->id = $this->procId;
    $rec->cid = $this->clientId;
    $rec->setDate($this->date); 
    $rec->timestamp = $this->date;
    $rec->name = $this->formatName();
    $rec->desc = $this->formatDesc();
    if (isset($this->Provider))
      $rec->provider = $this->Provider->formatName();
    //$rec->Unreviewed = getr($this, 'Unreviewed.Inbox');
    $rec->Unreviewed = getr($this, 'Unreviewed');
    return $rec;
  }
  public function getDocStubType() {
    return DocStub::TYPE_RESULT;
  }
  //
  static function asCriteria($cid) {
    $c = new static();
    $c->clientId = $cid;
    $c->Ipc = Ipc::asRequiredJoin();
    $c->ProcResults = ProcResult::asOptionalJoin();  // CriteriaJoin::requiresAsArray(new ProcResult());
    $c->Provider = Provider::asOptionalJoin();
    $c->Facility = FacilityAddress::asOptionalJoin();
    $c->Unreviewed = MsgThread_Stub::asUnreviewedJoin($c);
    return $c;
  }
  static function asPreviewCriteria($id, $ugid) {
    $c = new static();
    $c->procId = $id;
    $c->Ipc = Ipc::asRequiredJoin($ugid); 
    $c->ProcResults = ProcResult::asOptionalJoin();  
    $c->Provider = Provider::asOptionalJoin();
    $c->Facility = FacilityAddress::asOptionalJoin();
    $c->LabInbox = HL7InboxStub::asOptionalJoin();
    return $c;
  }
  static function fetchAllBy($c) {
    $c->Ipc = Ipc::asRequiredJoin_noAdmin();
    return parent::fetchAllBy($c);
  }
  static function fetchForPreview($id, $userId) {
    global $login;
    $c = static::asPreviewCriteria($id, $login->userGroupId);
    $rec = static::fetchOneBy($c);
    if ($rec) {
      $rec->ReviewThread = MsgThread_Stub::fetchForPreview($rec, $userId);
      if ($rec->scanIndexId) 
        $rec->ScanIndex = ScanIndex_DocProc::fetch($rec->scanIndexId, $login->userGroupId);
    }
    return $rec;
  }
} 
class ScanIndex_DocProc extends ScanIndex {
  //
  static function fetch($sxid, $ugid) {
    $c = new static();
    $c->userGroupId = $ugid;
    $c->scanIndexId = $sxid;
    $rec = static::fetchOneBy($c);
    $rec->ScanFiles = ScanFile::fetchAllIndexedTo($sxid, $ugid);
    return $rec;
  }
}
class HL7InboxStub extends SqlRec implements ReadOnly {
  //
  public $hl7InboxId;
  public $userGroupId;
  public $labId;
  public $msgType; 
  public $source;
  public $dateReceived;
  public $patientName;
  public $cid;
  public $status;
  public $reconciledBy;
  //
  public function getSqlTable() {
    return 'hl7_inbox';
  }
  public function getJsonFilters() {
    return array(
      'dateReceived' => JsonFilter::reportDateTime());
  }
}
//
require_once 'php/data/rec/sql/LookupScheduling.php';
