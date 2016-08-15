<?php
require_once "php/dao/LookupDao.php";
//
/**
 * Lookup Record Base Class
 */
abstract class LookupRec extends SqlRec {
  //
  public $_level;
  public $_instance;
  //
  //
  const LEVEL_APP = "A";     // Application (system-wide) level_id=0 
  const LEVEL_GROUP = "G";   // Group (practice)          level_id=[user_group_id]
  const LEVEL_USER = "U";    // User (doctor)             level_id=[user_id]
  const LEVEL_CLIENT = "X";  // Client (most specific)    level_id=[client_id]
  //
  const ASSOCIATE_BY_INSTANCE_ID = 0;
  const ASSOCIATE_BY_ID_PROPERTY = 1;
  const ASSOCIATE_NONE = 2;
  //
  public function getSqlTable() {
    return 'lookup_data';
  }
  public function _toJsonObject() {
    if ($this->getAssociation() == self::ASSOCIATE_BY_INSTANCE_ID)
      $this->id = $this->_instance;
    $this->removeLookupProps();
    return parent::_toJsonObject(); 
  }
  public function setObject($fid, $value) {
    $this->$fid = get_object_vars($value);
  }
  //
  abstract public function getLookupTable();
  /*
   * @override 
   */
  public function getLookupName() {}
  /*
   * @override if necessary
   */
  public function getAssociation() {
    return self::ASSOCIATE_BY_INSTANCE_ID;    
  }
  /*
   * @override if necessary
   */
  public function getListKey() {
    return ($this->getAssociation() == self::ASSOCIATE_BY_ID_PROPERTY) ? $this->id : $this->_instance;
  }
  /*
   * @override if necessary
   */
  public function getListValue() {
    return $this;
  } 
  /*
   * @override if necessary
   * @return null to map lookup data to $this
   * @return string to map lookup data to $this->$fid
   */
  public function getDataFid() {
    return null;
  }
  //
  protected function isActive() {
    if (isset($this->active))
      return $this->active;
    else
      return ! $this->inactive;
  }
  protected function removeLookupProps() {
    $this->_level = null;
    $this->_instance = null;
  }
  /**
   * @return LookupRec
   */
  static function fetch($instance = null, $userId = null) {
    $class = get_called_class();
    $result = self::fetchObject($class, $instance, $userId);
    $fid = $result['fid'];
    $obj = $result['data'];
    if ($fid == null) {
      return new $class($obj);
    } else {
      $rec = new $class();
      $rec->_id = get($obj, '_id');
      $rec->_level = get($obj, '_level');
      $rec->_instance = get($obj, '_instance');
      unset($obj->_id);
      unset($obj->_level);
      unset($obj->_instance);
      $rec->$fid = $obj;
      return $rec;
    }
  }
  protected static function fetchObject($class, $instance = null, $userId = null) {
    $criteria = new $class();
    $obj = LookupDao::getSingleInstance($criteria->getLookupTable(), $instance, $userId);
    return array('fid' => $criteria->getDataFid(), 'data' => $obj);
  }
  /**
   * @return array(LookupRec,..)
   */
  static function fetchAll() {
    $criteria = new static();
    $recs = LookupDao::getInstances($criteria->getLookupTable(), LookupDao::DEFAULT_USER, LookupDao::NO_CLIENT, LookupDao::AS_PHP_OBJECT, $criteria->getAssociation());
    foreach ($recs as &$rec)
      $rec = new static($rec);
    return $recs; 
  }
  /**
   * @return array(LookupRec,..)
   */
  static function fetchAllActive() {
    $class = get_called_class();
    $recs = self::fetchAll($class);
    return self::eliminateInactives($recs);
  }
  /**
   * @param (LookupRec[],..) e.g. getJsonLists(LookupAreas::get(), LookupScheduling::getStatus())
   * @return {'NAME':[key:value,..],'NAME':[..],..}
   */
  static function getJsonLists() {
    $args = func_get_args();
    return self::getJsonListsFromArray($args);
  }
  static function getJsonListsFromArray($args) {
    $lists = array();
    foreach ($args as $recs) 
      $lists += self::toNamedList($recs);
    return jsonencode($lists);
  }
  /**
   * @param LookupRec[] $recs
   * @return array('key':'value',..)
   */
  static function toList($recs) {
    $list = array();
    foreach ($recs as $rec) 
      $list[$rec->getListKey()] = $rec->getListValue();
    return $list;
  }
  /**
   * @param LookupRec/LookupRec[] $recs
   * @return array('NAME':LookupRec} for single rec 
   * @return array('NAME':array(key:value,..} for array of recs
   */
  static function toNamedList($recs) {
    if (is_array($recs)) {
      $list = self::toList($recs);
      $name = current($recs)->getLookupName();
    } else {
      $list = $recs;
      $name = $recs->getLookupName();
    }
    return array($name => $list);
  }
  //
  protected static function eliminateInactives($recs) {
    $actives = array(); 
    foreach ($recs as $rec) {
      if ($rec->isActive())
        $actives[] = $rec;
    }
    return $actives;
  }
}
