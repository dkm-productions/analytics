<?php
require_once 'php/data/rec/sql/MedsLegacy.php';
//
/**
 * Medications DAO (New Crop)  
 * @author Warren Hornsby
 */
class MedsNewCrop {
  /**
   * Build face recs from NewCrop status
   * @param int $cid
   * @param array $current @see NewCrop::pullCurrentMedAllergyV2
   */
  static function rebuildFromNewCrop($cid, $current, $withAudits = true, $noinsurance = false) {
    global $login;
    $sessions = SessionMedNc::fromNewCropMeds($login->userGroupId, $cid, $current['med']);
    $actives = FaceMedNc::fetchAllActiveNewCrop($cid);
    Dao::begin();
    try {
      foreach ($sessions as $name => $sess) {
        if ($noinsurance) {
          $sess->ncFormularyChecked = 'false';
        }
        if (isset($actives[$name])) {
          $face = $actives[$name];
          /*
          if ($face->isPreU1()) {
            $face->deactivate();
            $face->setFromU1($sess);
            $face->_noAudit = true;
            $face->save();
          }
          */
          if ($face->isSigDifferent($sess)) {
            $face->deactivate();
            if ($withAudits && isToday($sess->date)) {  // only audit sig changes enacted today 
              AuditMedNc::copyDeactivate($face);
              $sess->quid = SessionMedNc::QUID_NC_ADD;
              $sess->date = nowNoQuotes();
              $sess->save();
            }
            $face = FaceMedNc::fromSession($sess);
            $face->save();
          } else if ($face->isDateDifferent($sess)) {  
            $sess->quid = SessionMedNc::QUID_NC_RX;
            $sess->save();
            $face->setFromSession($sess);
            $face->save();
          } else if ($face->shouldBeUpdatedFrom($sess)) {
            $face->save();
          }
        } else {
          $sess->quid = SessionMedNc::QUID_NC_ADD;
          $sess->save();
          $face = FaceMedNc::fromSession($sess);
          $face->save();
        }
      }
      if (! empty($sessions))
        NoneActiveMed::remove($cid);
      foreach ($actives as $name => $face) {
        if (! isset($sessions[$name])) {
          $face->deactivate();
          if ($withAudits)
            AuditMedNc::copyDeactivate($face);
        }
      }
      Dao::commit();
    } catch (Exception $e) {
      Dao::rollback();
      throw $e;
    }
  }
  /**
   * Summarize updates from NewCrop since supplied time
   * @param int $clientId
   * @param string $since 'yyyy-mm-dd hh:mm:ss'
   * @return array(
   *   'nc.add'=>array(JDataMed,..), 
   *   'nc.dc'=>array(JDataMed,..), 
   *   'nc.rx'=>array(JDataMed,..)) 
   */
  static function getNewCropAudits($cid, $since) {
    $updates = array(
      SessionMedNc::QUID_NC_ADD => array(),
      SessionMedNc::QUID_NC_DC => array(),
      SessionMedNc::QUID_NC_RX => array());
    $dcAdds = array();
    $meds = SessionMedNc::getNewCropAuditsSince($cid, $since);
    foreach ($meds as &$med)  
      switch ($med->quid) {
        case SessionMedNc::QUID_NC_RX:
          $updates[$med->quid][] = $med;
          break;
        case SessionMedNc::QUID_NC_ADD:
        case SessionMedNc::QUID_NC_DC:
          $key = "$med->name $med->text";
          //$dcAdd = geta($dcAdds, $key);
          //if ($dcAdd)
          //  unset($dcAdds[$key]);
          //else
            $dcAdds[$key] = $med;
      }
    foreach ($dcAdds as &$med) 
      $updates[$med->quid][] = $med;
    return $updates;
  }
  /**
   * Get active facesheet records
   * @param int $cid
   * @return array(FaceMedNc,..)
   */
  static function getActive($cid) {
    $recs = FaceMedNc::fetchAllActive($cid, __CLASS__);
    foreach ($recs as $rec) {
      $rec->_diet = (substr($rec->name, 0, 5) == 'Diet:') ? 1 : 0;
      if ($rec->index > 99999) {
        $rec->text .= " (RXNORM:$rec->index)";
      }  
    }
    Rec::sort($recs, new RecSort('-_diet', 'name'));
    return $recs; 
  }
  /**
   * Get active and inactive facesheet records
   * @param int $cid
   * @return array(FaceMedNc,..)
   */
  static function getAll($cid, $actives = null) {
    $recs = FaceMedNc::fetchAll($cid, $actives);
    foreach ($recs as $rec) {
      $rec->_diet = (substr($rec->name, 0, 5) == 'Diet:') ? 1 : 0;  
    }
    Rec::sort($recs, new RecSort('-_diet', 'name'));
    return $recs; 
  }
  /**
   * Get facesheet records by name
   * @param int $cid
   * @return array(name=>FaceMed,..)
   */
  static function getByName($cid) {
    return FaceMedNc::fetchMap($cid);
  }
  /**
   * @param FaceMedNc[] $meds
   * @return bool @see FaceMedNc::areAllStale()
   */
  static function areAnyNcStale($meds) {
    return FaceMedNc::areAnyNcStale($meds);
  }
  static function areAllNcStale($meds) {
    return FaceMedNc::areAllNcStale($meds);
  }
  /**
   * Get history by date
   * @param int $cid
   * @param [FaceMedNc,..] $actives (optional, to sync history active flags)
   * @return array(FaceMedNc,..)
   */
  static function getHistoryByDate($cid, $actives = null) {
    $recs = SessionMedNc::fetchAll($cid);
    if ($actives)
      SessionMedNc::syncActiveFlags($recs, $actives);
    SessionMedNc::addSortFields($recs);
    Rec::sort($recs, new RecSort('-_dateOnly', 'name', '_quid', '-date'));
    $recs = self::extractDupes($recs);
    return $recs;
  }
  /**
   * Get history by name
   * @param int $cid
   * @param [FaceMedNc,..] $actives (optional, to sync history active flags)
   * @return array(SessionMed,..)
   */
  static function getHistoryByName($cid, $actives = null) {
    $recs = SessionMedNc::fetchAll($cid);
    if ($actives)
      SessionMedNc::syncActiveFlags($recs, $actives);
    Rec::sort($recs, new RecSort('name', '-date'));
    return $recs;
  }
  /**
   * Get review/recon history
   * @param int $cid
   * @return array('name':SessionMedNc_Review,..)
   */
  static function getReviewHistory($cid) {
    $map = SessionMedNc_Review::fetchActiveMap($cid);
    return $map;
  }
  /**
   * @return string static JSON of Med data object 
   */
  static function getStaticJson() {
    return MedNc::getStaticJson();
  }
  /**
   * @param int $cid
   * @param Med[] $meds
   */
  static function saveAsReviewed($cid, $meds) {
    global $login;
    $recs = SessionMedNc_Review::from($login->userGroupId, $cid, $login->userId, $meds);
    Dao::begin();
    try {
      SessionMedNc_Review::deactivateAll($cid);
      SqlRec::saveAll($recs);
      Proc_MedsReconciled::record($cid);
      Dao::commit();
    } catch (Exception $e) {
      Dao::rollback();
      throw $e;
    }
  }
  //
  private static function extractDupes($history) {
    $recs = array();
    foreach ($history as $sess) {
      $last = end($recs);
      if ($sess->isSameAs($last))
        array_pop($recs);
      $recs[] = $sess;
    }
    return $recs;
  } 
}
//
/**
 * Medication (New Crop)
 */
class MedNc extends Med {
  //
  public $dataMedId;
  public $userGroupId;
  public $clientId;
  public $sessionId;
  public $date;  
	public $quid;
	public $index;
	public $name;
	public $amt;
	public $freq;
	public $asNeeded;
	public $meals;
	public $route;
	public $length;
	public $disp;
	public $text;
	public $rx;
	public $active;   
	public $expires;
	public $dateUpdated;
	public $source;
	public $ncDrugName;
	public $ncGenericName;
	public $ncRxGuid;
	public $ncOrderGuid;
	public $ncOrigrxGuid;
	public $ncDosageNum;
	public $ncDosageForm;
	public $ncDosageNumId;
	public $ncDosageFormId;
	public $ncRouteId;
	public $ncFreqId;
	public $ncExtPhysId;
	public $ncFormularyChecked;
	//
  const SOURCE_NEWCROP = 1;
  //
  public function isSourceNewCrop() {
    return $this->source == self::SOURCE_NEWCROP;
  }
  public function isSourceLegacy() {
    return $this->source == null || $this->source == 0;
  }
  public function isSigDifferent($med) {
    return (trim($this->text) != trim($med->text) || $this->name != $med->name);
  }
  public function isDateDifferent($med) {
    return ($this->date != $med->date);
  }
  public function isPreU1() {  // true if created pre-Update1 web service 
    return (strpos($this->name, '(') !== false && $this->dateUpdated <= '2011-06-02');
  }
  public function isNcStale() {
    if ($this->source == self::SOURCE_NEWCROP) 
      //return ($this->active && $this->isPreU1());
      return ($this->active && $this->dateUpdated <= '2012-07-16');
  }
  public function getNameOnly($fromNewCrop = false) {
    $name = strtoupper($this->name);
    return $name;
    /*
	  if (! $fromNewCrop && $this->isPreU1()) {
      $a = explode('(', $name);
      $b = explode(' ', $a[1]);
      return trim($a[0] . self::getAmtOnly($b[0]));
    }
    $words = explode(' ', $name);
    $a = array();
    $num = false;
    foreach ($words as $word) {
      if (ctype_digit(substr($word, 0, 1))) 
        $num = true;
      else 
        if ($num)
          break;
      $a[] = $word;
    }
    $n = implode(' ', $a);
    return $n;
    */
  }
  public function getAmtOnly($s) {
    $a = explode('-', $s);
    return current($a);
  }
  public function getFreqInHours() {
    switch ($this->freq) {
      case 'DAILY':
        return 24;
      case 'Q4h':
        return 4; 
      case 'Q4-6h':
        return 4; 
      case 'Q6h':
        return 6; 
      case 'Q8h':
        return 8; 
      case 'Q12h':
        return 12; 
      case 'NIGHTLY':
        return 24; 
      case 'BEDTIME':
        return 24; 
      case 'in A.M.':
        return 24; 
      case 'Q2h WA':
        return 2; 
      case 'EVERY OTHER DAY':
        return 48; 
      case '3 TIMES WEEKLY':
        return 56; 
      case 'Q1wk':
        return 168; 
      case 'Q2wks':
        return 336; 
      case 'Q3wks':
        return 504; 
    }
  }
}
/**
 * Medication Face Record (New Crop)
 */
class FaceMedNc extends MedNc {
  //
  /**
   * For meds from NewCrop, RxNorm ID stored in index
   * @return int
   */
  public function getDrugId() {
    if ($this->index > 0)
      return $this->index;
  }
  public function wasAdminInOffice() {
    return (strpos($this->name, '(Administered') !== false);
  }
  /**
   * Update from NC med if any new information
   * @param SessionMedNc $sess
   * @return true if $this updated and needs to be saved
   */
  public function shouldBeUpdatedFrom($sess) {
    $dirty = false;    
    if ($this->index == null && $sess->index > 0) {  // update RxNorm
      $this->index = $sess->index;
      $dirty = true;
    }
    if (empty($this->ncDrugName) && $sess->ncDrugName) {  // update NC fields
      $this->ncDrugName = $sess->ncDrugName;
      $this->ncGenericName = $sess->ncGenericName;
    	$this->ncRxGuid = $sess->ncRxGuid; 
    	$this->ncOrderGuid = $sess->ncOrderGuid;
    	$this->ncOrigrxGuid = $sess->ncOrigrxGuid;
    	$this->ncDosageNum = $sess->ncDosageNum;
    	$this->ncDosageForm = $sess->ncDosageForm;
    	$this->ncDosageNumId = $sess->ncDosageNumId;
    	$this->ncDosageFormId = $sess->ncDosageFormId;
    	$this->ncRouteId = $sess->ncRouteId;
    	$this->ncFreqId = $sess->ncFreqId;
    	$this->ncExtPhysId = $sess->ncExtPhysId;
    	$this->ncFormularyChecked = $sess->ncFormularyChecked;
    	$dirty = true;
    }
    return $dirty;
  }
  //
  public function setFromSession($sess) {
    $rec = clone $sess;
    self::copyNonNullValues($this, $rec);
    $this->sessionId = null;
    $this->active = true;
    $this->quid = null;
  }
  public function setFromU1($sess) {
    $this->dataMedId = null;
    $this->name = $sess->name;
    $this->index = $sess->index;
    $this->active = true;
  }
  public function deactivate() {
    parent::_deactivate($this);
  }
  /**
   * @param int $cid
   * @return array(name=>FaceMedNc,..) 
   */
  static function fetchMap($cid) {
    return parent::_fetchFacesMap($cid, __CLASS__);
  }
  static function fetchAll($cid, $actives = null) {
    $c = static::asInactiveCriteria($cid);
    $map = parent::fetchMapBy($c, 'name');
    $recs = array_values($map);
    if ($actives == null)
      $actives = static::fetchAllActive($cid);
    return array_merge($recs, $actives);
//    $c = static::asCriteria($cid);
//    $map = parent::fetchMapBy($c, 'name');
//    $recs = array_values($map); 
//    if (empty($recs)) 
//      $recs = NoneActiveMed::fetchAll($cid);
//    return $recs; 
  }
  /**
   * @param int $cid
   * @return array(FaceMedNc,..)
   */
  static function fetchAllActive($cid) {
    $recs = parent::_fetchActiveFaces($cid, __CLASS__);
    if (empty($recs)) 
      $recs = NoneActiveMed::fetchAll($cid);
    return $recs; 
  }
  static function asInactiveCriteria($cid) {
    $c = parent::_asFaceCriteria($cid, __CLASS__);
    $c->active = false;
    return $c;
  }
  static function fetchAllActive_forPortal($cid, $class) {
    $recs = parent::_fetchActiveFaces($cid, $class);
    return $recs;
  }
  /**
   * Fetch all active with New Crop source
   * @param int $cid
   * @return array(name=>FaceMedNc,..)
   */
  static function fetchAllActiveNewCrop($cid) {
    $c = self::asCriteria($cid);
    $c->source = MedNc::SOURCE_NEWCROP;
    $c->active = true;
    $recs = self::fetchAllBy($c, new RecSort('-dataMedId'));
    $map = array();
    foreach ($recs as $rec) {
      $key = $rec->getNameOnly();
      $exists = geta($map, $key); 
      if ($exists) {
        if ($exists->ncRxGuid == $rec->ncRxGuid) {
          static::delete($rec);
        }
      } else {
        $map[$key] = $rec;
      }
    }
    return $map;
  }
  /**
   * @param SessionMed $sess
   * @return FaceMedNc
   */
  static function fromSession($sess) {
    return parent::_faceFromSession($sess, __CLASS__);
  } 
  /**
   * @param int $cid
   * @return FaceMedNc
   */
  static function asCriteria($cid) {
    $c = parent::_asFaceCriteria($cid, __CLASS__);
    $c->text = static::getTextCriteria();
    return $c;
  }  
  static function asActiveCriteria($cid) {
    $c = parent::_asActiveFaceCriteria($cid, __CLASS__);
    $c->text = static::getTextCriteria();
    return $c;
  }
  static function getTextCriteria() {
    return CriteriaValues::_or(CriteriaValue::notEquals(NoneActiveMed::NONE_ACTIVE), CriteriaValue::isNull());
  }
  static function areAnyNcStale($meds) {
    if (! empty($meds)) {
      foreach ($meds as $med) {
        if (! $med instanceof NoneActiveMed) {
          if ($med->isNcStale()) {
            return true;
          }
        }
      }
    }
  }   
  static function areAllNcStale($meds) {
    $stale = false;
    if (! empty($meds)) {
      foreach ($meds as $med) {
        if ($med instanceof NoneActiveMed)
          return false;
        if ($med->source == self::SOURCE_NEWCROP) { // ensure legacy meds don't count
          if (! $med->isNcStale()) 
            return false;
          else  
            $stale = true;
        }
      }
    }
    return $stale;
  }  
  //
  private static function copyNonNullValues($to, $from) {
    foreach ($from as $fid => $value) 
      if ($value !== null) 
        $to->$fid = $value;
  }
}
//
/**
 * Medication Session Record (New Crop)
 */
class SessionMedNc extends MedNc implements NoAudit {
  //
  const QUID_NC_ADD = 'nc.add';
  const QUID_NC_DC  = 'nc.dc';
  const QUID_NC_RX  = 'nc.rx';
  const QUID_RECON = 'recon';
  static $QUIDS = array(
    self::QUID_NC_ADD => 'Ordered',
    self::QUID_NC_DC => 'Discontinued',
    self::QUID_NC_RX => 'Prescribed',
    self::QUID_RECON => 'Reviewed/Reconciled');
  static $FINAL_DEST_TYPES = array(
    '0' => 'Ordered/Not Transmitted', 
    '2' => 'Faxed',
    '1' => 'Printed',
    '3' => 'Electronic to Retail Pharmacy',
    '4' => 'Electronic to Mail Order Pharmacy');
  static $FORMULARY_CHECKED = array(
    'true' => 'Checked',
    'false' => 'Not Checked');
  static $DEA_CLASS_CODES = array(
    '0' => 'Non-Scheduled',
    '2' => 'Class 2',
    '3' => 'Class 3',
    '4' => 'Class 4',
    '5' => 'Class 5');
  //
  const NEW_CROP_SID = 0;  // all recs from New Crop have same "SID"
  // 
  public function toJsonObject(&$o) {
    $o->quid = $this->getQuidText();
  }
  public function isDiscontinued() {
    switch ($this->quid) {
      case self::QUID_NC_DC:
      case 'plan.meds.@dcMed':
      case 'plan.plan.@dcMed':
        return true;
    }
  }
  public function wasEPrescribed() {
    return $this->ncOrderGuid == '0';
  }
  public function isNonScheduled() {
    return $this->ncOrigrxGuid == '0';
  }
  public function isSameAs($sess) {
    if ($sess)
      return $this->getQuidText() == $sess->getQuidText() &&
        trim($this->name) == trim($sess->name) && 
        trim($this->rx) == trim($sess->rx) &&
        trim($this->date) == trim($sess->date) &&
        $this->getExtractedText() == $sess->getExtractedText();
  }
  protected function getExtractedText() {  // extract () and disp from session rec text, to match with original NC text
    if ($this->sessionId != self::NEW_CROP_SID) {
      $a = explode("[(:)]", $this->text);
      if (count($a) > 1)
        return $a[1];
      $a = explode('(Disp:', $this->text);
      if (count($a) > 1)
        return $a[0];
    }
    return $this->text;
  }
  protected function getQuidText() {
    switch ($this->quid) {
      case self::QUID_NC_ADD:
        return 'Added';
      case self::QUID_NC_DC:
        return 'Discontinued';
      case self::QUID_NC_RX:
        return 'Refilled';
      case self::QUID_RECON:
        return 'Reviewed';
      default:
        return SessionMed::formatQuidText($this->quid);
    }
  }
  /**
   * @param int $cid
   * @return array(SessionMedNc,..)
   */
  static function fetchAll($cid) {
    $c = self::asCriteria($cid);
    return self::fetchAllBy($c);
  }
  /**
   * @param int $cid
   * @param string $since 'yyyy-mm-dd hh:mm:ss'
   * @return array(SessionMedNc,..)
   */
  static function getNewCropAuditsSince($cid, $since) {
    $c = self::asCriteria($cid);
    $c->source = MedNc::SOURCE_NEWCROP;
    $c->Hd_date = Hdata_MedDate::join(CriteriaValue::greaterThan($since));
    //$c->date = CriteriaValue::greaterThan($since);
    return self::fetchAllBy($c); 
  }
  /**
	 * @param int $ugid
	 * @param int $cid 
	 * @param PatientFullMedicationHistoryV6[] $ncs 
	 * @return array(name=>SessionMedNc,..)
	 */
	static function fromNewCropMeds($ugid, $cid, $ncs) {
	  $dtos = array();
	  if ($ncs)
  	  foreach ($ncs as $nc) {
  	    $dto = self::fromNewCropMed($ugid, $cid, $nc);
  	    $name = $dto->getNameOnly(true);
  	    $old = geta($dtos, $name);
  	    if ($old && compareDates($old->date, $dto->date) == 1) {
          // don't replace newer one already there  	      
  	    } else {  
    	    $dtos[$name] = $dto;
  	    }
  	  }
	  return $dtos;
	}
  /**
   * @param int $ugid
   * @param int $cid 
   * @param PatientFullMedicationHistoryV6 $nc 
   * @return SessionMedNc
   */
  static function fromNewCropMed($ugid, $cid, $nc) {
    $inoffice = (strpos($nc->PrescriptionNotes, 'administered in office') !== false); 
    $addlsigs = $nc->replaceSingleAddlSig();
    $name = $nc->DrugInfo;
    if ($name == 'None' && ! empty($nc->ExternalDrugConcept)) 
      $name = $nc->ExternalDrugConcept;
    if ($inoffice) 
      $name .= ' (Administered)';
    $amt = $nc->DosageNumberDescription;
    $amt = "$amt $nc->DosageForm";
    $freq = $nc->DosageFrequencyDescription;
    $dto = new SessionMedNc();
    $dto->dataMedId = null;
    $dto->userGroupId = $ugid;
    $dto->clientId = $cid;
    $dto->sessionId = self::NEW_CROP_SID;
    $dto->date = datetimeToString($nc->PrescriptionDate);
  	$dto->index = $nc->rxcui;
  	$dto->name = $name;
  	$dto->amt = $amt;
  	$dto->freq = $freq;
  	$dto->asNeeded = ($nc->TakeAsNeeded == 'Y') ? 1 : 0;
  	$dto->route = $nc->Route;
  	$dto->length = null;
  	$dto->disp = $nc->Dispense;
  	$dto->rx = self::rxFromNewCrop($nc);
  	$dto->source = MedNc::SOURCE_NEWCROP;
    $dto->ncDrugName = $nc->DrugName;
    if ($nc->DrugName == 'None' && ! empty($nc->ExternalDrugConcept)) 
      $dto->ncDrugName = $nc->ExternalDrugConcept;
    $dto->ncGenericName = $nc->GenericName;
  	$dto->ncRxGuid = $nc->PrescriptionGuid; 
  	$dto->ncOrderGuid = $nc->FinalDestinationType;
  	$dto->ncOrigrxGuid = $nc->DeaClassCode;
  	$dto->ncDosageNum = $nc->DosageNumberDescription;
  	$dto->ncDosageForm = $nc->DosageForm;
  	$dto->ncDosageNumId = $nc->DosageNumberTypeID;
  	$dto->ncDosageFormId = $nc->DosageFormTypeId;
  	$dto->ncRouteId = $nc->DosageRouteTypeId;
  	$dto->ncFreqId = $nc->DosageFrequencyTypeID;
  	$dto->ncExtPhysId = $nc->ExternalPhysicianID;
  	$dto->ncFormularyChecked = $nc->FormularyChecked;
  	if ($nc->PrescriptionNotes) {
      if ($addlsigs)
        $dto->text = $nc->PrescriptionNotes;
      else 
        $dto->text = $dto->formatSig() . ' ' . $nc->PrescriptionNotes;
    } else {
      $dto->text = $dto->formatSig();
    }
  	return $dto;
  }
  /**
   * @param int $cid
   * @return SessionMedNc
   */
  static function asCriteria($cid) {
    $c = parent::_asSessCriteria($cid, __CLASS__);
    $c->quid = CriteriaValues::_and(
      CriteriaValue::notEquals(SessionMed::QUID_CURRENT), 
      CriteriaValue::notEquals(self::QUID_RECON));
    $c->name = CriteriaValue::isNotNull();
    return $c;
  }
  /**
   * Sync active flags of history with current actives  
   * @param [SessionMedNc,..] $meds
   * @param [FaceMedNc,..] $actives
   */
  static function syncActiveFlags(&$meds, $actives) {
    SessionMed::syncActiveFlags($meds, $actives);
  }
  /**
   * Add sorting fields to meds in array
   * @param [SessionMedNc,..] $meds
   * @return array(SessionMedNc,..)
   */
  static function addSortFields(&$meds) {
    foreach ($meds as &$med) { 
      $med->_dateOnly = dateToString($med->date);
      $med->_quid = $med->getQuidText();
    }
    return $meds;
  }
  //
  private static function rxFromNewCrop($nc) {
    $rx = null;
    if ($nc->OrderGUID != '00000000-0000-0000-0000-000000000000') {
      $date = formatDateTime($nc->PrescriptionDate);
      $disp = $nc->Dispense;
      $refill = $nc->Refills;
      $leaflet = ($nc->PrintLeaflet == 'T') ? '(Patient leaflet printed)' : '';
      $rx = ($nc->isErx()) ? 'ERX' : 'RX'; 
      $rx .= ": $date Disp: $disp, Refills: $refill $leaflet";
    } 
    return $rx;
  }
}
class SessionMedNc_Review extends SessionMedNc {
  //
  public function getReviewerId() {
    return $this->source;
  }
  //
  static function from($ugid, $cid, $userId, $meds) {  // returns array(self,..)
    $date = nowNoQuotes();
    $mes = array();
    foreach ($meds as $med)
      $mes[] = self::fromMed($date, $ugid, $cid, $userId, $med);
    return $mes;
  }
  protected static function fromMed($date, $ugid, $cid, $userId, $med) {
    $me = new self($med);
    $me->dataMedId = null;
    $me->userGroupId = $ugid;
    $me->clientId = $cid;
    $me->sessionId = self::NEW_CROP_SID;
    $me->quid = self::QUID_RECON;
    $me->active = true;
    $me->date = $date;
    $me->source = $userId;
    return $me;
  }
  static function deactivateAll($cid) {
    $sql = "UPDATE data_meds SET active=0 WHERE quid='" . self::QUID_RECON . "' AND client_id=$cid AND active=1";
    Dao::query($sql);
  }
  static function fetchActiveMap($cid) {
    $c = self::asCriteria(null, $cid);
    return SqlRec::fetchMapBy($c, 'name'); 
  }
  static function asCriteria($ugid, $cid) {
    $c = new static();
    $c->userGroupId = $ugid;
    $c->clientId = $cid;
    $c->sessionId = self::NEW_CROP_SID;
    $c->quid = self::QUID_RECON;
    $c->User = CriteriaJoin::requires(new UserStub(), 'source');
    $c->active = true;
    return $c;
  }
}
//
/**
 * Medication Face Audit Record (New Crop)
 */
class AuditMedNc extends MedNc implements NoAudit {
  /**
   * @param string $quid MedNc:QUID_
   * @param string $rx 
   */
  static function copy($face, $quid) {
    $rec = new AuditMedNc($face);
    $rec->setPkValue(null);
    $rec->sessionId = '0';
    $rec->active = false;
    $rec->date = nowNoQuotes();
    $rec->quid = $quid;
    if ($quid == SessionMedNc::QUID_NC_DC) {
      $rec->rx = null;
      $rec->ncOrderGuid = null;
    }
    $rec->save();
    return $rec->dataMedId;
  }
  static function copyDeactivate($face) {
    self::copy($face, SessionMedNc::QUID_NC_DC);
  }
}
