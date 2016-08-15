<?php
require_once 'php/data/rec/sql/_IpcRec.php';
require_once 'php/data/rec/sql/Reporting.php';
//
/**
 * IPC Health Maintenance Codes
 * @author Warren Hornsby
 */
class IProcCodes_Hm {
  //
  /**
   * @return array(IpcHm,..)
   */
  static function getAll() {
    global $myLogin;
    $recs = IpcHm::fetchTopLevels($myLogin->userGroupId);
    return Rec::sort($recs, new RecSort('Ipc.cat', 'Ipc.name'));
  }
  /**
   * @param int $cid
   * @return array(IpcHm_Client,..)
   */
  static function getForClient($cid) {
    global $myLogin;
    $recs = IpcHm_Client::fetchAll($myLogin->userGroupId, $cid);
    return $recs; 
  }
  /**
   * @param stdClass $obj
   * @return IpcHm
   */
  static function save($obj) {
    global $myLogin;
    $rec = IpcHm::fromUi($obj, $myLogin->userGroupId);
    $rec->save();
    return IpcHm::fetchOneBy($rec->asPkCriteria());
  }
  /**
   * @param stdClass $obj
   */
  static function del($obj) {
    global $myLogin;
    $rec = IpcHm::fromUi($obj, $myLogin->userGroupId);
    IpcHm::delete($rec);
  }
  //
  private static function mapByName($ugid) {
    static $map;
    if ($map == null)
      $map = Ipc::fetchMapByName($ugid);
    return $map;
  }
  private static function mapSurgByDesc($ugid) {
    static $map;
    if ($map == null)
      $map = Ipc::fetchSurgMap($ugid);
    return $map;
  } 
}
//
class IpcHm extends SqlClientLevelRec implements CompositePk {
  //
  public $ipc;
  public $userGroupId;
  public $clientId;
  public $auto;
  public $every;
  public $interval;
  public $criteria;  // serialized RepCrit_Hm
  public /*Ipc*/ $Ipc;
  //
  const INT_DAY = 1;
  const INT_WEEK = 2;
  const INT_MONTH = 3;
  const INT_YEAR = 4;
  static $INTERVALS = array(
    self::INT_DAY => 'day(s)',
    self::INT_WEEK => 'week(s)',
    self::INT_MONTH => 'month(s)', 
    self::INT_YEAR => 'year(s)');
  //
  static $EMPTY_CRITERIA;
  //
  public function getSqlTable() {
    return 'ipc_hm';
  }
  public function toJsonObject(&$o) {
    if ($this->criteria)
      $o->criteria = jsondecode($this->criteria);
    else
      $o->criteria = new RepCrit_Hm();
  }
  public function getJsonFilters() {
    return array(
    	'auto' => JsonFilter::boolean());
  }
  public function getCriteriaObject() {
    if ($this->isGroupLevel())
      return jsondecode($this->criteria);
  }
  public function setFetchCriteria() {
    $this->Ipc = Ipc::asRequiredJoin();
    return $this;
  }
  //
  /**
   * Fetch topmost level for IPC
   * @param int $ipc
   * @param int $ugid
   * @param int $cid
   * @return IpcHm
   */
  static function fetchTopLevel($ipc, $ugid, $cid = null) {
    $rec = parent::fetchTopLevel($ipc, $ugid, $cid);
    $rec->Ipc = Ipc::fetch($ipc);
    return $rec;
  }
  /**
   * @param int $ipc
   * @param int $ugid
   * @return array(Client_Rep,..) 
   */
  static function fetchAllApplicableClients($ipc, $ugid) {
    $ipchm = self::fetchTopLevel($ipc, $ugid);
    $recs = array();
    if ($ipchm) {
      $recsGroupLevel = self::fetchGroupApplicable($ipchm);
      $recsClientLevel = self::fetchClientsApplicable($ipchm);
      $recs = array_merge($recsGroupLevel, $recsClientLevel);
    }
    return $recs;
  }
  /**
   * @param int $ipc
   * @param int $ugid
   * @return array(Client_Rep,..)
   */
  static function fetchAllDueNowClients($ipc, $ugid) {
    $ipchm = self::fetchTopLevel($ipc, $ugid);
    $recs = array();
    if ($ipchm) {
      $recsGroupLevel = self::fetchGroupDueNow($ipchm);
      $recsClientLevel = self::fetchClientsDueNow($ipchm);
      $recs = array_merge($recsGroupLevel, $recsClientLevel);
    }
    return $recs;
  }
  static function fromUi($o, $ugid) {
    $o->criteria = jsonencode($o->criteria);
    $rec = new self($o);
    if (! isset($rec->userGroupId))
      $rec->userGroupId = $ugid;
    if (! isset($rec->clientId))
      $rec->clientId = self::GROUP_LEVEL_CID;
    return $rec;
  }
  static function getStaticJson() {
    self::$EMPTY_CRITERIA = new RepCrit_Hm();
    return parent::getStaticJson();
  }
  //
  private static function fetchGroupApplicable($ipchm) {
    $recs = array();
    if (! $ipchm->isClientLevel()) {
      $crit = RepCrit_Hm::asGroupApplicable($ipchm);
      $recs = $crit->fetchAll($ipchm->userGroupId);
    }
    return $recs;
  }
  private static function fetchGroupDueNow($ipchm) {
    $recs = array();
    if (! $ipchm->isClientLevel()) {
      $crit = RepCrit_Hm::asGroupDueNow($ipchm);
      $recs = $crit->fetchAll($ipchm->userGroupId);
    }
    return $recs;
  }
  static function fetchClientsApplicable($ipchm) {
    $crit = RepCrit_Hm::asClientLevelApplicable($ipchm);
    return $crit->fetchAll($ipchm->userGroupId);
  }
  static function fetchClientsDueNow($ipchm) {
    $crit = RepCrit_Hm::asClientLevelDueNow($ipchm);
    return $crit->fetchAll($ipchm->userGroupId);
  }
} 
// 
class IpcHm_Client extends IpcHm {
  /*
   * public $_overdue;  
   * public $Proc_last;
   */
  //
  public function addProcLast($procs) {
    if (! is_array($procs)) {
      $this->Proc_last = null;
      $this->_overdue = true;
    } else {
      $procs = Rec::sort($procs, new RecSort('-date'));
      $this->Proc_last = current($procs);
      $this->_overdue = false;
    }
  }
  //
  static function fetchAll($ugid, $cid) {
    $hms = self::fetchTopLevels($ugid, $cid);
    $hms = self::extractApplicables($hms, $cid);
    return $hms;
  }
  /**
   * @param IpcHm $hms
   * @param int $cid
   * @return array(IpcHm_Client,..)
   */
  static function extractApplicables($hms, $cid) {
    $recs = array();
    foreach ($hms as $hm) {
      $crit = RepCrit_Hm::asClientApplicable($hm, $cid);
      $client = $crit->fetchOne($hm->userGroupId);
      if ($client) {
        $hm->addProcLast($client->Join0);
        $recs[] = $hm;
      }
    }
    return $recs;
  }
}
class RepCrit_Hm extends RepCritRec {
  //
  public $clientId;
  public $sex;
  public $age;
  public $race;
  public $ethnicity;
  //
  static $JOINS_TO = array(
    self::T_ADDRESS, 
    self::T_DIAGNOSES,
    self::T_MEDS,
    self::T_ALLERGIES,
    self::T_PROCS,
    self::T_RESULTS,
    self::T_IMMUNS,
    self::T_VITALS,
    self::T_SESSIONS);
  //
  public function getSqlClass() {
    return 'Client_Rep';
  }
  public function getTable() {
    return self::T_CLIENTS;
  }
  public function fetchOne($ugid) {
    $recs = $this->fetchAll($ugid);
    return current($recs);
  }
  public function count($ugid) {
    $criteria = self::asSqlCriteria($this, $ugid);
    return Client_Rep::count($criteria);
  } 
  public function addNotHavingProcJoin($ipchm) {
    $this->Joins[] = RepCritJoin_Hm::asNotHavingProc($ipchm); 
  }
  public function addHavingProcJoin($ipchm) {
    $this->Joins[] = RepCritJoin_Hm::asHavingProc($ipchm); 
  }
  public function addNotHavingProcCalcIntervalJoin($ipchm) {
    $this->Joins[] = RepCritJoin_Hm::asNotHavingCalcIntervalProc($ipchm); 
  }
  public function addNotHavingClientLevelJoin($ipchm) {
    $this->Joins[] = RepCritJoin_Hm::asNotHavingClientLevel($ipchm); 
  }
  public function addHavingClientLevelJoin($ipchm) {
    $this->Joins[] = RepCritJoin_Hm::asHavingClientLevel($ipchm); 
  }
  public function addCid($cid) {
    $this->clientId = RepCritValue_Hm::asCid($cid);
  }
  //
  /**
   * @param IpcHm ipchm group-level
   * @return RepCrit_Hm to return all patients applicable  
   */
  static function asGroupApplicable($ipchm) {
    $crit = new self($ipchm->getCriteriaObject());
    $crit->addNotHavingClientLevelJoin($ipchm);
    return $crit;
  }
  /**
   * @param IpcHm ipchm any level
   * @param RepCrit_Hm to return all patients with client-level IPC_HM
   */
  static function asClientLevelApplicable($ipchm) {
    $crit = new self();
    $crit->addHavingClientLevelJoin($ipchm);
    return $crit;
  }
  /**
   * @param IpcHm ipchm group-level
   * @param int $cid (optional, to filter for specific patient)
   * @return RepCrit_Hm to return all patients applicable and are due now  
   */
  static function asGroupDueNow($ipchm, $cid = null) {
    $crit = new self($ipchm->getCriteriaObject());
    if ($cid) 
      $crit->addCid($cid);
    $crit->addNotHavingProcJoin($ipchm);
    return $crit;
  }
  /**
   * @param IpcHm ipchm any level
   * @param RepCrit_Hm to return all patients with client-level IPC_HM that are due now
   */
  static function asClientLevelDueNow($ipchm) {
    $crit = new self();
    $crit->addHavingClientLevelJoin($ipchm);
    $crit->addNotHavingProcCalcIntervalJoin($ipchm);
    return $crit;
  }
  static function asClientApplicable($ipchm, $cid) {
    $crit = new self($ipchm->getCriteriaObject());
    $crit->addCid($cid);
    $crit->addHavingProcJoin($ipchm);
    return $crit;
  }
  //
  protected function assignSqlCriteriaValue(&$criteria, $fid, $value) {
    if ($fid == 'age') 
      $fid = 'birth';
    parent::assignSqlCriteriaValue($criteria, $fid, $value);
  }
}
class RepCritJoin_Hm extends RepCritJoin {
  //
  static function asNotHavingProc($ipchm) {  // not having proc dated within group-level IpcHm interval
    $rec = new self();
    $rec->jt = self::JT_NOT_HAVE;
    $rec->table = RepCritRec::T_PROCS;
    $rec->Recs[] = RepCrit_HmProc::asProcWithinDate($ipchm);
    return $rec;
  }
  static function asHavingProc($ipchm) {  // having proc dated within group-level IpcHm interval
    $rec = new self();
    $rec->jt = self::JT_OPTIONAL;
    $rec->table = RepCritRec::T_PROCS;
    $rec->Recs[] = RepCrit_HmProc::asProcWithinDate($ipchm);
    return $rec;
  }
  static function asNotHavingCalcIntervalProc($ipchm) {  // not having proc dated within client-level IpcHm interval
    $rec = new self();
    $rec->jt = self::JT_NOT_HAVE;
    $rec->table = RepCritRec::T_PROCS;
    $rec->Recs[] = RepCrit_HmProc::asProcWithinCalcInterval($ipchm);
    return $rec;
  }
  static function asNotHavingClientLevel($ipchm) {
    $rec = new self();
    $rec->jt = self::JT_NOT_HAVE;
    $rec->table = RepCrit_IpcHm::T_IPCHM;
    $rec->Recs[] = RepCrit_IpcHm::asIpc($ipchm);
    return $rec;
  }
  static function asHavingClientLevel($ipchm) {
    $rec = new self();
    $rec->jt = self::JT_HAVE_ONE;
    $rec->table = RepCrit_IpcHm::T_IPCHM;
    $rec->Recs[] = RepCrit_IpcHm::asAutoIpc($ipchm);
    return $rec;
  }
}
class RepCrit_IpcHm extends RepCritRec {  
  //
  const T_IPCHM = '100';
  //
  public $clientId;
  public $auto;
  //
  public function getSqlClass() {
    return 'IpcHm_Rep';
  }
  public function getTable() {
    return self::T_IPCHM;
  }
  //
  static function getClassFromTable() {
    return __CLASS__;
  }
  static function asIpc($ipchm) {  // e.g. IPC_HM at client level
    $rec = new self();
    $rec->ipc = RepCritValue_Hm::asIpc($ipchm);
    return $rec;
  }
  static function asAutoPc($ipchm) {  // e.g. active (auto=1) IPC_HM at client level
    $rec = new self();
    $rec->ipc = RepCritValue_Hm::asIpc($ipchm);
    $rec->auto = '1';
    return $rec;
  }
}
class IpcHm_Rep extends IpcHm implements ReadOnly {  
  //
  protected function getPkField() {
    return 'ipc';
  }
  //
  static function asCriteria($ugid) {
    return new static();
  }
}
class RepCrit_HmProc extends RepCrit_Proc {  
  //
  static function asProcWithinDate($ipchm) {  // e.g. proc performed within group-level interval
    $rec = new self();
    $rec->ipc = RepCritValue_Hm::asIpc($ipchm);
    $rec->date = RepCritValue_Hm::asDate($ipchm);
    return $rec;
  }
    static function asProcWithinCalcInterval($ipchm) {  // e.g. proc performed within client-level interval
    $rec = new self();
    $rec->ipc = RepCritValue_Hm::asIpc($ipchm);
    $rec->date = RepCritValue_Hm::asDateWithinInterval();
    return $rec;
  }
}
class RepCritValue_Hm extends RepCritValue {
  //
  static $INTERVALS = array(
    IpcHm::INT_DAY => 'd',
    IpcHm::INT_WEEK => 'w',
    IpcHm::INT_MONTH => 'm', 
    IpcHm::INT_YEAR => 'y');
  //
  static function asIpc($ipchm) {
    $rec = new self();
    $rec->op = self::OP_IS;
    $rec->value = $ipchm->ipc;
    //$rec->text_ = $ipchm->Ipc->name;
    return $rec;
  }
  static function asDate($ipchm) {
    if ($ipchm->every) {
      $rec = new self();
      $rec->op = self::OP_WITHIN;
      $rec->value = $ipchm->every . ',' . self::$INTERVALS[$ipchm->interval];
      $rec->text_ = $ipchm->every . ' ' . IpcHm::$INTERVALS[$ipchm->interval];
      return $rec;
    } else {
      return null;
    }
  }
  static function asCid($cid) {
    $rec = new self();
    $rec->op = self::OP_IS;
    $rec->value = $cid;
    return $rec;
  }
  static function asDateWithinInterval() {
    $rec = new self();
    $rec->op = self::OP_GTEN;
    $rec->value = 'CASE WHEN `interval`=1 THEN now()-interval `every` DAY WHEN `interval`=2 THEN now()-interval `every` WEEK WHEN `interval`=3 THEN now()-interval `every` MONTH WHEN `interval`=4 THEN now()-interval `every` YEAR END';
    $rec->text_ = 'interval start date';
    return $rec; 
  }
}
 