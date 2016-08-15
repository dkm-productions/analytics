<?php
require_once 'php/data/rec/sql/_LookupRec.php';
//
/**
 * Lookup Scheduling
 * @author Warren Hornsby 
 */
class LookupScheduling {
  //
  /**
   * @return array(id=>LuApptType,..)
   */
  static function getApptTypes() {
    return LuApptType::fetchAll();
  }
  /**
   * @param LuApptType[] $types
   * @param int $id
   * @return string 
   */
  static function getApptTypeName($types, $id) {
    return LuApptType::getName($types, $id);
  }
  /**
   * @return array(id=>LuSchedStatus,..)
   */
  static function getStatuses() {
    return LuSchedStatus::fetchAll();
  }
  /**
   * @param LuSchedStatus[] $statuses
   * @param int $id
   * @return string 
   */
  static function getStatusName($statuses, $id) {
    return LuSchedStatus::getName($statuses, $id);
  }
  /**
   * @param int $userId
   * @return LuSchedProfile
   */
  static function getProfileFor($userId) {
    return LuSchedProfile::fetch($userId);
  }
}
class LuApptType extends LookupRec {
  //
  public $name;
  public $bcolor;
  public $min;
  //
  public function getLookupTable() {
    return 1;
  }
  public function getLookupName() {
    return 'APPT_TYPE';
  }
  //
  static function getName($recs, $id) {
    $rec = geta($recs, $id);
    if ($rec)
      return $rec->name;
  }
}
class LuSchedStatus extends LookupRec {
  //
  public $name;
  public $bcolor;  
  public $active;
  //
  public function getLookupTable() {
    return 12;
  }
  public function getLookupName() {
    return 'SCHED_STATUS';
  }
  //
  static function getName($recs, $id) {
    $rec = geta($recs, $id);
    if ($rec)
      return $rec->name;
  }
}
class LuSchedProfile extends LookupRec {
  //
  public $slotSize;
  public $slotStart;
  public $slotEnd;
  public $dowStart;
  public $weekLength;
  //
  public function getLookupTable() {
    return 3;
  }
  public function getLookupName() {
    return 'SCHED_PROFILE';
  }
  //
  static function fetch($userId) {
    return parent::fetch(null, $userId);
  }
}
