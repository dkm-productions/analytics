<?php
require_once 'php/data/rec/sql/_FsDataRec.php';
require_once 'php/data/rec/sql/_HdataRec.php';
require_once "php/dao/DataDao.php";
//
/**
 * Vitals DAO
 * - FaceVital (sid=null): Vitals record (by date)
 * - SessionVital (sid>0): Built from closed note (generates a FaceVital) 
 * @author Warren Hornsby
 */
class Vitals {
  /**
   * Build facesheet records from unprocessed session history and old facesheet records
   * @param int $cid
   */
  static function rebuild($cid) {
    Vital::buildFacesFromOldFaces($cid);
    Vital::buildFacesFromSessions($cid);
  }
  /**
   * Get active facesheet records
   * @param int $cid
   * @return array(FaceVital,..)
   */
  static function getActive($cid, $lastOnly = false) {
    self::rebuild($cid);
    $recs = FaceVital::fetchAllActive($cid);
    $recs = Rec::sort($recs, new RecSort('-date'));
    if ($recs) {
      return $lastOnly ? array(reset($recs)) : $recs;
    }
  }
  /**
   * Get vitals questions
   * @return array(prop=>JQuestion,..)
   */
  static function getQuestions() {
    $vqs = array();
    $questions = DataDao::fetchQuestionsForTable(1, nowNoQuotes(), "vitals");
    foreach (Vital::$PROPS_TO_QUID as $prop => $quid)
      $vqs[$prop] = $questions[$quid];
    return $vqs;
  }
  /**
   * Save record from UI
   * @param stdClass $o JSON object
   * @return FaceVital
   */
  static function save($o) {
    global $login;
    return FaceVital::saveFromUi($o, $login->userGroupId, $login->userId);
  }
  /**
   * Deactivate record from UI
   * @param int $id
   * @return FaceVital
   */
  static function deactivate($id) { 
    $face = FaceVital::fetch($id);
    if ($face) {
      $face->deactivate();
      return $face;
    }
  }
}
//
/**
 * Vital
 */
class Vital extends FsDataRec implements AutoEncrypt {
  //
  public $dataVitalsId;
  public $userGroupId;
  public $clientId;
  public $sessionId;
  public $date;  
  public $pulse;
  public $resp;
  public $bpSystolic;
  public $bpDiastolic;
  public $bpLoc;
  public $temp;
  public $tempRoute;
  public $wt;
  public $wtUnits;
  public $height;
  public $hc;
  public $hcUnits;
  public $wc;
  public $wcUnits;
  public $o2Sat;
  public $o2SatOn;
  public $bmi;
  public $htUnits;
  public $dateUpdated;
  public $active;
  public $wtLbs;
  public $htIn;
  public $updatedBy;
  //
  static $PROPS_TO_QUID = array(
    'pulse'        => 'vitals.pulse',
    'resp'         => 'vitals.rr', 
    'bpSystolic'   => 'vitals.sbp', 
    'bpDiastolic'  => 'vitals.dbp', 
    'bpLoc'        => 'vitals.loc', 
    'temp'         => 'vitals.temp',
    'tempRoute'    => 'vitals.tempRoute', 
    'wt'           => 'vitals.#Weight', 
    'wtUnits'      => 'vitals.lbsKgs', 
    'height'       => 'vitals.#Height', 
    'htUnits'      => 'vitals.unitsHt', 
    'hc'           => 'vitals.#hc', 
    'hcUnits'      => 'vitals.inCm', 
    'wc'           => 'vitals.#wc', 
    'wcUnits'      => 'vitals.inCmWC', 
    'o2Sat'        => 'vitals.#O2Sat', 
    'o2SatOn'      => 'vitals.O2SatOn', 
    'bmi'          => 'vitals.bmi');
  //
  public function getSqlTable() {
    return 'data_vitals';
  }
  public function getEncryptedFids() {
    return array('date');
  }
  public function getKey() {
    return 'date';
  }
  public function getFaceClass() {
    return 'FaceVital';
  }
  public function getJsonFilters() {
    return array(
      'date' => JsonFilter::editableDateTime(),
      'dateUpdated' => JsonFilter::informalDateTime());
  }
  public function save() {
    parent::save();
    Hdata_VitalsDate::from($this)->save();
  }
  public function toJsonObject(&$o) {
    if (! $this->isAuditSnapshot()) {
      $o->all = $this->getAllValues();
      $o->htcm = $this->getHtCm();
      $o->wtkg = $this->getWtKg();
      $o->bp = $this->getBp();
      $o->o2 = $this->getO2();
    }
  }
  public function getAuditRecName() {
    return 'Vital';
  }
  public function validate(&$rv) {
    //$rv->isTodayOrPast('date');
  }
  public function getDateOnly() {
    return substr($this->date, 0, 10);
  }
  /*
   * @return FaceVital
   */
  public function asFace($replaceFace = null, $allowEmptyRecord = false) {
    logit_r($replaceFace, 'asFace');
    $face = parent::asFace($replaceFace);
    if ($replaceFace) 
      $face->date = $replaceFace->date;
    logit_r($face, 'face');
    $face->bpSystolic = str_replace('/', '', $face->bpSystolic); 
    $face->toNumeric(
      'pulse',
    	'bpSystolic',
      'bpDiastolic',
      'temp',
      'wt',
      'height',
      'hc',
      'wc',
      'o2Sat',
      'bmi');
    if ($face->temp) 
      $face->temp = number_format($face->temp, 1);
    if ($face->bpSystolic == null && $face->bpDiastolic == null)
      $face->bpLoc = null;
    if ($face->wt) 
      $face->wtLbs = $face->getWtLb();
    if ($face->height)
      $face->htIn = $face->getHtIn(); 
    if ($face->hc)
      $face->hcIn = $face->getHcIn();
    if (! $allowEmptyRecord)
      if (! $face->isAnySet())
        $face = null; 
    return $face;
  }
  //
  public function getBp() {
    return "$this->bpSystolic/$this->bpDiastolic $this->bpLoc";
  }
  public function getO2() {
    $s = $this->o2Sat;
    if ($s && $this->o2SatOn) 
      $s = "$s - $this->o2SatOn";
    return $s;
  }
  public function getWtKg() {
    if (self::formatWtUnits($this->wtUnits) == 'KG')
      return $this->wt;
    else
      return round($this->wt * 0.45359, 2);
  }
  public function getWtLb() {
    if (self::formatWtUnits($this->wtUnits) == 'KG')
      return round($this->wt * 2.20462, 2);
    else 
      return $this->wt;
  }
  public function getHtCm() {
    if (self::formatHtUnits($this->htUnits) == 'CM') 
      return $this->height;
    else
      return round($this->height * 2.54, 2);
  }
  public function getHtIn() {
    if (self::formatHtUnits($this->htUnits) == 'CM') 
      return round($this->height * 0.3937, 2);
    else
      return $this->height;
  }
  public function getHcCm() {
    if (self::formatHtUnits($this->hcUnits) == 'CM') 
      return $this->hc;
    else
      return round($this->hc * 2.54, 2);
  }
  public function getHcIn() {
    if (self::formatHtUnits($this->hcUnits) == 'CM') 
      return round($this->hc * 0.3937, 2);
    else
      return $this->hc;
  }
  public function getAllValues() {
    $a = array();
    if ($this->pulse)
      $a[] = 'Pulse: ' . $this->pulse;
    if ($this->resp)
      $a[] = 'Resp: ' . $this->resp;
    if ($this->bpSystolic)
      $a[] = 'BP: ' . $this->getBp();
    if ($this->temp)
      $a[] = 'Temp: ' . $this->temp;
    if ($this->wt)
      $a[] = 'Wt: ' . $this->wt;
    if ($this->height)
      $a[] = 'Height: ' . $this->height;
    if ($this->hc)
      $a[] = 'HC: ' . $this->hc;
    if ($this->wc)
      $a[] = 'WC: ' . $this->wc;
    if ($this->o2Sat)
      $a[] = 'O2: ' . trim($this->o2Sat . ' ' . $this->o2SatOn);
    if ($this->bmi)
      $a[] = 'BMI: ' . $this->bmi;
    return $a;
  }
  public function getAllFriendlyValues() {
    $a = array();
    if ($this->pulse)
      $a[] = 'Pulse: ' . $this->pulse . ' Beats Per Minute';
    if ($this->resp)
      $a[] = 'Respiratory Rate: ' . $this->resp . ' Breaths Per Minute';
    if ($this->bpSystolic)
      $a[] = 'Blood Pressure: ' . "$this->bpSystolic (Systolic) / $this->bpDiastolic (Diastolic)";
    if ($this->temp)
      $a[] = 'Temperature: ' . $this->temp . ' F';
    if ($this->wt)
      $a[] = 'Weight: ' . "$this->wt $this->wtUnits"; 
    if ($this->height)
      $a[] = 'Height: ' . "$this->height $this->htUnits";
    if ($this->hc)
      $a[] = 'Head Circumference: ' . "$this->hc $this->hcUnits";
    if ($this->wc)
      $a[] = 'Waist Circumference: ' . "$this->wc $this->wcUnits";
    if ($this->bmi)
      $a[] = 'BMI: ' . $this->bmi;
    if ($this->o2Sat)
      $a[] = 'Oxygen: ' . trim($this->o2Sat . ' ' . $this->o2SatOn);
    return $a;
  }
  protected function isBpSet() {
    if ($this->bpDiastolic && $this->bpSystolic)
      return true;
  }
  protected function isHtWtSet() {
    if ($this->height && $this->wt)
      return true;
  }
  private function isAnySet() {
    return
      $this->pulse ||
      $this->resp ||
      $this->bpSystolic ||
      $this->temp ||
      $this->wt ||
      $this->height ||
      $this->hc ||
      $this->wc ||
      $this->o2Sat;
  }
  //
  protected static function formatWtUnits($u) {
    return (strtoupper(substr($u, 0, 1)) == 'K') ? 'KG' : 'LB';
  }
  protected static function formatHtUnits($u) {
    return (strtoupper(substr($u, 0, 1)) == 'C') ? 'CM' : 'IN';
  }
  /**
   * @param object $o JSON
   * @param int $ugid
   * @return Vital 
   */
  static function fromUi($o, $ugid, $userId) {
    $rec = new Vital($o);
    $rec->userGroupId = $ugid;
    $rec->updatedBy = $userId;
    return $rec;
  }
  /**
   * Build new face records from session history
   * @param int cid
   */
  static function buildFacesFromSessions($cid) {
    $sessions = SessionVital::fetchAllUnbuilt($cid);
    if ($sessions) {
      $faces = FaceVital::fetchMap($cid);
      parent::_buildFacesFromSessions($sessions, $faces);
    }
  }
  /**
   * Build new face records from old (active=null)
   * @param int $cid
   */
  static function buildFacesFromOldFaces($cid) {
    $c = FaceVital::asCriteria($cid);
    $c->active = CriteriaValue::isNull();
    $oldFaces = parent::fetchAllBy($c);
    foreach ($oldFaces as $oldFace) {
      $face = $oldFace->asFace();
      if ($face)
        $face->save();
    }
  }
}
/**
 * Vital Face Record
 */
class FaceVital extends Vital {
  //
  public function save() {
    logit_r($this, 'facevital save');
    parent::save();
    $this->saveTriggers();
  }
  protected function saveTriggers() {
    $c = Client::fetch($this->clientId);
    $c->setChronAge();
    logit_r($c, 'facevital savetriggers');
    $record = ($c->ageYears > 3) ? 
      $this->isHtWtSet() && $this->isBpSet() : 
      $this->isHtWtSet();
    if ($record) {
      Proc_VitalsRecorded::record($this->clientId);
    }
    if ($this->bmi) {
      Proc_BMI::record($this->clientId, $this->date, $this->bmi);
    }
    if ($this->bpDiastolic && $this->bpSystolic) {
      Proc_Diastolic::record($this->clientId, $this->date, $this->bpDiastolic); 
      Proc_Systolic::record($this->clientId, $this->date, $this->bpSystolic); 
    }
  }
  public function deactivate() {
    parent::_deactivate($this);
  }
	/**
   * @param int $id
   * @return FaceVital
   */
  static function fetch($id) {
    return parent::_fetchFace($id, __CLASS__);
  }
  /**
   * @param int $cid
   * @return array(date=>FaceVital,..) 
   */
  static function fetchMap($cid) {
    $c = self::asCriteria($cid);
    $c->active = true;
    $recs = static::fetchAllBy($c, new RecSort('-date'));
    $map = array();
    foreach ($recs as $rec) {
      $date = $rec->getDateOnly();
      if (geta($map, $date) == null) 
        $map[$date] = $rec;
    }
    return $map;
  }
  /**
   * @param int $cid
   * @return array(FaceVital,..)
   */
  static function fetchAllActive($cid, $class = __CLASS__) {
    return parent::_fetchActiveFaces($cid, $class);
  }
  /**
   * @param object $o JSON
   * @param int $ugid
   * @return FaceVital saved
   */
  static function saveFromUi($o, $ugid, $userId) {
    $vital = Vital::fromUi($o, $ugid, $userId);
    $face = $vital->asFace(null, true);
    $vital = parent::_saveFromUi($face);
    return $vital;
  }
  /**
   * @param int $cid
   * @return FaceVital 
   */
  static function asCriteria($cid) {
    $c = parent::_asFaceCriteria($cid, __CLASS__);
    return $c;
  }  
  static function asActiveCriteria($cid) {
    $c = parent::_asActiveFaceCriteria($cid, __CLASS__);
    $c->UpdatedBy = User_Doctor::asOptionalJoin('updatedBy');
    return $c;
  } 
}
//
/**
 * Vital Session Record
 */
class SessionVital extends Vital implements ReadOnly {
  //
  public function getKeyValue() {
    return $this->getDateOnly();
  }
  /**
   * @param int $cid
   * @return array(SessionVital,..)
   */
  static function fetchAllUnbuilt($cid) {
    $c = self::asUnbuiltCriteria($cid);
    return self::fetchAllBy($c);
  }
  /**
   * @param int $cid
   * @return SessionVital
   */
  static function asUnbuiltCriteria($cid) {
    $c = parent::_asUnbuiltSessCriteria($cid, __CLASS__);
    return $c;
  }
}
