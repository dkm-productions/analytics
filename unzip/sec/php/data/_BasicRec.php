<?php
/**
 * BasicRec
 * @author Warren Hornsby
 */
abstract class BasicRec {
  /*
  public $field1;
  public $field2;
  */
  protected function onload() {} 
  protected function onjson() {
    static::suppressNulls($this);
    static::toJson_traverse($this);
  } 
  //
  public function __construct(/*field1_value,field2_value,..*/) {
    $args = func_get_args();
    $this->__constructFromArray($args);
    $this->onload();
  }
  public function set($fid, $value) {
    $this->$fid = $value;
  }
  public function setByObject($o) {
    foreach ($this->getFids() as $fid)
      if (isset($o->$fid))
        $this->$fid = $o->$fid;
    return $this;
  }
  public function getValues() {
    return array_values(get_object_vars($this));
  }
  public function toJson() {
    $this->onjson();
    return json_encode($this);
  }
  //
  static function fromObject($o) {
    $me = new static();
    return $me->setByObject($o);
  }
  static function fromObjects($os) {
    $us = array();
    if ($os) {
      if (is_object($os))
        $us[] = static::fromObject($os);
      else foreach ($os as $o)
        $us[] = static::fromObject($o);
    }
    return $us;
  }
  //
  protected function __constructFromArray($arr) {
    $fids = $this->getFids();
    for ($i = 0, $l = count($fids), $k = count($arr); $i < $l && $i < $k; $i++) {
      $value = current($arr);
      $this->set($fids[$i], $value);
      next($arr);
    }
  }
  public function getFids() {
    static $fids;
    if ($fids == null)
      $fids = array_keys(get_object_vars($this));
    return $fids;
  }
  protected static function toJson_traverse($a) {
    foreach ($a as $i => $value) {
      if ($value instanceof BasicRec) {
        $value->onjson();
      } else if ($value instanceof Rec) { 
        seta($a, $i, $value->_toJsonObject());
      } else if (is_object($value)) {
        static::suppressNulls($value);
        seta($a, $i, static::toJson_traverse($value));
      } else if (is_array($value)) {
        seta($a, $i, static::toJson_traverse($value));
      }
    }
    return $a;
  }
  protected static function suppressNulls(&$o) {
    foreach ($o as $fid => $value)
      if ($value === false)
        $o->$fid = 0; 
      else if (empty($value) && ! is_numeric($value)) 
        unset($o->$fid);
  }
}