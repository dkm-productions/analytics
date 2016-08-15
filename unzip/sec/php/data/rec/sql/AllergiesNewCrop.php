<?php
require_once 'php/data/rec/sql/AllergiesLegacy.php';
//
/**
 * Allergies DAO (New Crop)  
 * @author Warren Hornsby
 */
class AllergiesNewCrop {
  /**
   * Build face recs from NewCrop status
   * @param int $cid
   * @param object $current @see NewCrop::pullCurrentMedAllergy()
   */
  static function rebuildFromNewCrop($cid, $current) {
    global $login;
    $sessions = SessionAllergyNc::fromNewCropAllergies($login->userGroupId, $cid, $current['allergy']);
    $actives = FaceAllergyNc::fetchAllActiveNewCrop($cid);
    logit_r($sessions, 'sessions');
    logit_r($actives, 'actives');
    foreach ($sessions as $agent => $sess) {
      if (! isset($actives[$agent])) {
        $face = $sess->asFace();
        $face->save();
      } else {
        $face = $actives[$agent];
        if ($face->isDifferentThan($sess)) {
          $face->reactions = $sess->reactions;
          $face->index = $sess->index;
          $face->date = $sess->date;
          $face->save();
        }
      }
    }
    foreach ($actives as $agent => $face) 
      if (! isset($sessions[$agent])) 
        $face->deactivate();
  }
  /**
   * Mark actives as reconciled
   */
  static function reconcile($cid, $allers) {
    global $login;
      if ($cid) {
      $map = FaceAllergyNc::fetchAllActiveMap($cid);
      logit_r($map, 'ra0');
      logit_r($allers, 'ra1');
      foreach ($allers as $i => $aller) {
        if (isset($map[get($aller, 'dataAllergyId')])) {
          unset($map[$aller->dataAllergyId]);
          unset($allers[$i]);
        }
      }
      logit_r($map, 'ra2');
      logit_r($allers, 'ra3');
      FaceAllergyNc::deactivateMany($map);
      FaceAllergy::activateMany($cid, $login->userGroupId, $allers);
      FaceAllergyNc::reconcileActives($cid, $login->userId);
    }
  }
  /**
   * Get active facesheet records
   * @param int $cid
   * @return array(FaceAllergyNc,..)
   */
  static function getActive($cid) {
    $recs = FaceAllergyNc::fetchAllActive($cid);
    Rec::sort($recs, new RecSort('agent'));
    return $recs; 
  }
  /**
   * Get active and inactive facesheet records
   * @param int $cid
   * @return array(FaceAllergyNc,..)
   */
  static function getAll($cid) {
    $recs = FaceAllergyNc::fetchAll($cid);
    Rec::sort($recs, new RecSort('-active', 'agent'));
    $map = array();  // eliminate the inactives of active/inactive dupes
    foreach ($recs as $rec) {
      $key = $rec->agent;
      if ($rec->active || ! isset($map[$key]))
        $map[$key] = $rec;
    }   
    return array_values($map); 
  }
  /**
   * Get history by date
   * @param int $cid
   * @return array(FaceAllergyNc,..)
   */
  static function getHistoryByDate($cid) {
    $recs = SessionAllergyNc::fetchAll($cid);
    Rec::sort($recs, new RecSort('-date', 'sessionId', 'agent'));
    return $recs;
  }
}
//
/**
 * Allergy (New Crop)
 */
class AllergyNc extends Allergy {
  //
  const SOURCE_NEWCROP = 1;
  //
  public function getFaceClass() {
    return 'FaceAllergyNc';
  }
  public function deactivate() {
    parent::_deactivate($this);
  }
  public function formatReactions() {
    return $this->reactions; 
  }
  /*
   * @return FaceAllergy
   */
  public function asFace($replaceFace = null) {
    $face = FsDataRec::asFace($replaceFace);
    // Don't null index
    return $face;
  }
  public function isSourceNewCrop() {
    return $this->source == self::SOURCE_NEWCROP;
  }
  public function isSourceLegacy() {
    return $this->source == null || $this->source == 0;
  }
}
/**
 * Allergy Face Record (New Crop)
 */
class FaceAllergyNc extends AllergyNc {
  //
  public function toJsonObject($o) {
    $o->_status = ($this->active) ? 'Active' : 'Inactive';
    $o->_date = formatDate($this->date);
    $o->_dateRecon = formatDate($this->dateRecon);
  }
  public function saveAsReconciled($date, $userId) {
    $this->dateRecon = $date;
    $this->reconBy = $userId;
    $this->save();
  }
  static function fetchAllActiveMap($cid) {
    $map = array();
    $recs = static::fetchAllActive($cid);
    foreach ($recs as $rec) {
      $map[$rec->dataAllergyId] = $rec;
    }
    return $map;
  }
  static function deactivateMany($recs) {
    foreach ($recs as $rec) {
      $rec->deactivate();
      $rec->save();
    }
  }
  /**
   * For allergies from NewCrop, RxNorm ID stored in index
   * @return int
   */
  public function getDrugId() {
    if ($this->index > 0)
      return $this->index;
  }
  public function getSeverityText() {
    $a = explode(': ', $this->reactions);
    if (count($a) > 1)
      return $a[0];
  }
  public function getReactionsText() {
    $a = explode(': ', $this->reactions);
    return end($a);
  }
  public function isDifferentThan($sess) {
    return ($this->reactions != $sess->reactions || $this->index != $sess->index || $this->date != $sess->date);
  }
  //
  /**
   * @param int $cid
   * @return array(name=>FaceAllergy,..) 
   */
  static function fetchMap($cid) {
    return parent::_fetchFacesMap($cid, __CLASS__);
  }
  /**
   * @param int $cid
   * @return array(FaceAllergyNc,..)
   */
  static function fetchAllActive($cid, $class = __CLASS__) {
    return parent::_fetchActiveFaces($cid, $class);
  }
  static function reconcileActives($cid, $userId) {
    $recs = static::fetchAllActive($cid);
    $now = nowNoQuotes();
    foreach ($recs as $rec) {
      $rec->saveAsReconciled($now, $userId);
    }
  }
  /**
   * @param int $cid
   * @return array(FaceAllergyNc,..)
   */
  static function fetchAll($cid) {
    return parent::_fetchFaces($cid, __CLASS__);
  }
  /**
   * Fetch all active with New Crop source
   * @param int $cid
   * @return array(agent=>FaceAllergyNc,..)
   */
  static function fetchAllActiveNewCrop($cid) {
    $c = self::asActiveCriteria($cid);
    $c->source = AllergyNc::SOURCE_NEWCROP;
    return self::fetchMapBy($c, 'agent');
  }
  /**
   * @param int $cid
   * @return FaceAllergyNc
   */
  static function asCriteria($cid) {
    return parent::_asFaceCriteria($cid, __CLASS__);
  }  
  static function asActiveCriteria($cid) {
    return parent::_asActiveFaceCriteria($cid, __CLASS__);
  }  
}
//
/**
 * Allergy Session Record (New Crop)
 */
class SessionAllergyNc extends AllergyNc implements ReadOnly {
  //
  const NEW_CROP_SID = 0;  // all recs from New Crop have same "SID"
  // 
  static function isDate($d) {
    $a = explode("/", $d);
    if (count($a) == 3)
      return true;
  }
  //
  /**
   * @param int $cid
   * @return array(SessionAllergyNc,..)
   */
  static function fetchAll($cid) {
    $c = self::asCriteria($cid);
    return self::fetchAllBy($c);
  }
  /**
	 * @param int $ugid
	 * @param int $cid 
	 * @param PatientAllergyHistoryV3[] $ncs 
	 * @return array(name=>SessionAllergyNc,..)
	 */
	static function fromNewCropAllergies($ugid, $cid, $ncs) {
    $dtos = array();
    if ($ncs)
      foreach ($ncs as $nc) {
        $dto = self::fromNewCropAllergy($ugid, $cid, $nc);
        $dtos[$dto->agent] = $dto;
      }
    return $dtos;
	}
  /**
   * @param int $ugid
   * @param int $cid 
   * @param PatientAllergyHistoryV3 $nc
   * @return SessionAllergyNc
   */
  static function fromNewCropAllergy($ugid, $cid, $nc) {
    logit_r($nc, 'fromNewCrop');
    $reactionsDate = self::formatNcReactions($nc);
    $dto = new SessionAllergyNc(
      null,
      $ugid,
      $cid,
      null,
      $reactionsDate['date'], 
      $nc->rxcui,
      $nc->AllergyName,
      $reactionsDate['reactions'],
      true,
      null,
      AllergyNc::SOURCE_NEWCROP);
    logit_r($dto,'allergy created');
    return $dto;
  }
  /**
   * @param PatientAllergyHistoryV3 $nc
   */
  static function formatNcReactions($nc) {
    $notes = $nc->AllergyNotes;
    $date = substr($notes, -10);
    if (self::isDate($date)) {
      $notes = substr($notes, 0, -11);
      $date = dateToString($date);
    } else {
      $date = nowNoQuotes();
    }
    $r = array();
    if ($nc->AllergySeverityName)
      $r[] = $nc->AllergySeverityName;
    if ($nc->AllergyNotes)
      $r[] = $notes;
    $reactions = implode(': ', $r);
    return array('reactions' => $reactions, 'date' => $date);
  }
  /**
   * @param int $cid
   * @return SessionAllergyNc
   */
  static function asCriteria($cid) {
    $c = parent::_asSessCriteria($cid, __CLASS__);
    return $c;
  }
}
