<?php
require_once "php/dao/_util.php";
/**
 * Marker Interfaces
 */
interface SerializeNulls {}    // Include nulls in JSON serialization
interface Revivable{}          // Include __class in JSON serialization
/**
 * Data Record
 */
class Rec {
  /**
   * Assigns fields based upon args supplied:
   *   (value,value,..)      multiple args: each assigned in field definition order
   *   ([value,..])          single arg, array: values assigned in field definition order
   *   ({fid:value,..})      single arg, decoded JSON object: values mapped to fids
   * To call this from an overriden constructor, use:
   *   $args = func_get_args();
   *   call_user_func_array(array('Rec', '__construct'), $args);
   */
  public function __construct() {
    $args = func_get_args();
	Logger::debug('_Rec: Got args which is a ' . gettype($args) . print_r($args, true));
    if (count($args) == 1)
      if (is_array($args[0])) {
        $this->__constructFromArray($args[0]);
        $args = null;
      } else if (is_object($args[0])) {
        $this->__constructFromObject($args[0]);
        $args = null;
      }
    if ($args)
      $this->__constructFromArray($args);
  }
  public function __constructFromObject($obj) {
    if (get_class($obj) == 'stdClass' && ! isset($obj->_noFilterIn))
      $obj = RecJson::asIncoming($obj, $this);
    $fids = $this->getFids();
    for ($i = 0, $l = count($fids); $i < $l; $i++) {
      $fid = $fids[$i];
      $value = get($obj, $fid);
      if ($value !== null)
        $this->set($fid, $value);
    }
    if (! isset($obj->_noFilterIn))
      $this->fromJsonObject($obj);  // Allow inheritors to embellish
    else
      $this->_noFilterIn = true;
  }
  public function __constructFromArray($args) {
    $fids = $this->getFids();
    for ($i = 0, $l = count($args); $i < $l; $i++) {
      $value = current($args);
      $this->set($fids[$i], $value);
      next($args);
    }
  }
  /**
   * Assign field value according to type
   * @param string $fid
   * @param string $value: simple field assignment
   *        object $value: child Rec assignment from decoded JSON object {fid:value,..}
   */
  public function set($fid, $value) {
    if ($value !== null) {
      if (is_scalar($value))
        $this->setScalar($fid, $value);
      else if (is_array($value))
        $this->setObjectArray($fid, $value);
      else
        $this->setObject($fid, $value);
    }
    return $this;
  }
  public function setScalar($fid, $value) {
   // var_dump(debug_print_backtrace());
	if (strlen($fid) > 0) {
		try {
			$this->$fid = $value;
		}
		catch (Exception $e) {
			echo 'EXCEPTION: ' . $e->getMessage() . '. Trace: ' . $e->getTraceAsString();
		}
	}
  }
  public function setObject($fid, $value) {
    $class = $this->getClassFromJsonField($fid);
    Logger::debug('setObject stack trace:');
    Logger::debug(print_r(debug_backtrace(), true));
    $this->$fid = new $class($value);
  }
  public function setObjectArray($fid, $arr) {
    $class = $this->getSingular($this->getClassFromJsonField($fid));
    foreach ($arr as &$value)
      $value = new $class($value);
    $this->$fid = $arr;
  }
  public function toJson() {
    return jsonencode($this);
  }
  /**
   * Invoked by Services_JSON prior to constructing JSON of this object
   */
  public function _toJsonObject() {
    $obj = RecJson::asOutgoing($this, $this instanceof SerializeNulls);
    if ($this instanceof Revivable)
      $obj->__class = get_class($this);
    if ($obj)
      $this->toJsonObject($obj);  // allow inheritors to embellish
    return $obj;
  }
  /**
   * Override to add/change properties, e.g.:
   *   $o->existingProp = 'change';
   *   unset($o->existingPropToDelete);
   * @param stdClass $o result of self::_toJsonObject() pre-serialization object of $this
   * @return stdClass final object to serialize
   */
  public function toJsonObject(&$o) {
    //
  }
  /**
   * Override to add/change properties, e.g.:
   *   $this->prop = $o->_prop
   * @param stdClass $o deserialized JSON used to instantiate $this
   */
  public function fromJsonObject($o) {
    //
  }
  /**
   * @return bool if this record was created from an audit snapshot
   */
  public function isAuditSnapshot() {
    return isset($this->_noFilterIn);
  }
  /**
   * Invoked when creating/consuming JSON objects to apply transformations to fields
   * e.g. date formatting for UI, back to SQL format on return:
   * Example override:
   *   return array('dateGiven' => JsonFilter::approxDate());
   * @return array('fid'=>JsonFilter,..)
   */
  public function getJsonFilters() {
    return array();
  }
  /**
   * @return string 'className'
   */
  public function getMyName() {
    return get_class($this);
  }
  /**
   * Lookup friendly name from record's UI_NAMES constant array
   * @param string $fid
   * @return string from UI_NAMES if exists; else upper-cased $fid is best we can do
   */
  public function getFriendlyName($fid) {
    static $names = false;
    if ($names === false) {
      $rc = self::getReflectionClass($this);
      $props = $rc->getStaticProperties();
      $names = geta($props, 'FRIENDLY_NAMES');
    }
    $name = ($names) ? geta($names, $fid) : null;
    if ($name)
      return $name;
    switch ($fid) {
      case 'clientId':
        return 'Patient';
      default:
        return $this->camelToFriendly($fid);
    }
  }
  protected function camelToFriendly($fid) {
    $func = create_function('$c', 'return " $c[1]";');
    return substr(preg_replace_callback('/([A-Z])/', $func, ucfirst($fid)), 1);
  }
  //
  protected function getFids() {
    static $fids;
    if ($fids == null)
      $fids = array_keys(get_object_vars($this));
    return $fids;
  }
  protected function getFidCt() {
    static $ct;
    if ($ct == null)
      $ct = count($this->getFids());
    return $ct;
  }
  protected function getValues() {
    $values = get_object_vars($this);
    return $values;
  }
  /**
  * Build Rec objects from decoded JSON array
  * @param array $objects [{fid:value,..},..]
  * @return array(Rec,..)
  * DEPRECATED - doesn't invoke json filters etc.
  */
  /*
  static function reviveAll($recs) {
    foreach ($recs as &$rec)
      $rec = static::revive($rec);
    return $recs;
  }
  static function revive($rec) {
    $me = new static();
    foreach ($rec as $fid => $value)
      $me->set_fromRevive($fid, $value);
    return $me;
  }
  public function set_fromRevive($fid, $value) {
    if ($value !== null) {
      if (is_scalar($value))
        $this->setScalar($fid, $value);
      else if (is_array($value))
        $this->setObjectArray_fromRevive($fid, $value);
      else
        $this->setObject_fromRevive($fid, $value);
    }
  }
  protected function setObject_fromRevive($fid, $value) {
    $this->$fid = $this->getObject_fromRevive($value);
  }
  protected function setObjectArray_fromRevive($fid, $arr) {
    foreach ($arr as &$value)
      $value = $this->getObject_fromRevive($value);
    $this->$fid = $arr;
  }
  protected function getObject_fromRevive($value) {
    $class = get($value, '__class');
    if ($class)
      return new $class($value);
  }
  */
  /**
   * Array instantiator using existing single instantiator
   * @param Rec[] $recs
   * @param 'fromSomeRec' $method
   * @return array(Rec,..)
   */
  static function fromRecs($froms, $method) {
    $recs = array();
    foreach ($froms as $from)
      $recs[] = static::$method($from);
    return $recs;
  }
  /**
   * Get JSON-serialized array of class constants and static lists
   * @return {
   *   'CONSTANT_NAME':value,..
   *   'LIST_NAME':['value':'text',..],..
   *   }
   */
  static function getStaticJson() {
    $rc = self::getReflectionClass(get_called_class());
    $constants = $rc->getConstants();
    $lists = static::getStaticLists($rc);
    return jsonencode(array_merge($constants, $lists));
  }
  protected static function getStaticLists($rc) {
    $lists = $rc->getStaticProperties();
    return $lists;
  }
  protected static function getReflectionClass($class = null) {
    return new ReflectionClass(self::getClass($class));
  }
  /**
   * Get Someself::CONSTANT
   * @param Rec $rec
   * @param string $name 'CONSTANT_NAME'
   * @return mixed value of constant
   */
  protected static function getConstant($rec, $name) {
    $full = $rec->getMyName() . "::$name";
    if (defined($full))
      return constant($full);
  }
  protected function getClassFromJsonField($fid) {
    $a = explode('_', $fid);
    return $a[0];
  }
  protected function getSingular($fid) {
    if (substr($fid, -1) == 's') {
      switch (substr($fid, -3)) {
        case 'ses':  // 'statuses' to 'status'
        case 'xes':  // 'boxes' to 'box'
          if (substr($fid, -4) == 'oses')  // 'psychoses' to 'psychosis'
            $fid = substr($fid, 0, -2) . 'is';
          else
            $fid = substr($fid, 0, -2);
          break;
        case 'ies':  // 'tallies' to 'tally'
          $fid = substr($fid, 0, -3) . 'y';
          break;
        default:  // 'books' to 'book'
          $fid = substr($fid, 0, -1);
          break;
      }
    }
    return $fid;
  }
  protected static function isHelperFid($fid) {
    $c = substr($fid, 0, 1);
    return $c == '_';
  }
  public static function isObjectFid($fid) {
    $c = substr($fid, 0, 1);
    return ($c != '_' && self::isUpper($c));
  }
  public static function isScalarFid($fid) {
    $c = substr($fid, 0, 1);
    return ! ($c == '_' || self::isUpper($c));
  }
  protected static function isUpper($c) {
    return ($c == strtoupper($c));
  }
  protected static function getClass($class) {
    if (is_object($class))
      $class = get_class($class);
    return $class;
  }
  /**
   * Sort record set
   * @param [Rec,..] $recs
   * @param RecSort $order
   * @param bool $preserveKeys
   */
  static function sort(&$recs, $order, $preserveKeys = false) {
    if (! empty($recs)) {
      if ($preserveKeys)
        uasort($recs, array($order, 'compare'));
      else
        usort($recs, array($order, 'compare'));
      return $recs;
    }
  }
  static function sortWithKeys(&$recs, $order) {
    return self::sort($recs, $order, true);
  }
}
/**
 * Record JSON (for serialization of a Rec)
 */
class RecJson {
  //
  public function __construct($rec) {
    foreach ($rec as $fid => $value) {
      $value = jeval($value);
      if ($value === false || ! empty($value) || is_numeric($value))
        $this->$fid = $value;
    }
  }
  /**
   * @return bool
   */
  public function isEmpty() {
    foreach ($this as $fid => &$value)
      return false;
    return true;
  }
  /**
   * Add "_prop" to outgoing JSON object
   * @param string $fid
   * @param mixed $value
   */
  public function add($fid, $value) {
    $prop = "_$fid";
    $this->$prop = $value;
  }
  /**
   * Add "_prop" by looking up prop value in constant list
   * @param string $fid
   * @param array $value ['value'=>'text',..]
   */
  public function lookup($fid, $list) {
    if (isset($this->$fid))
      $this->add($fid, geta($list, $this->$fid));
  }
  public function rename($fid, $new) {
    if (isset($this->$fid)) {
      $this->$new = $this->$fid;
      unset($this->$fid);
    }
  }
  public function int() {
    $fids = func_get_args();
    foreach ($fids as $fid) {
      $value = intval($this->$fid);
      if ($value == 0)
        unset($this->$fid);
      else
        $this->$fid = $value;
    }
  }
  public function bool() {
    $fids = func_get_args();
    foreach ($fids as $fid) {
      if (asBool($this->$fid))
        $this->$fid = 1;
      else
        unset($this->$fid);
    }
  }
  public function remove() {
    $fids = func_get_args();
    foreach ($fids as $fid)
      unset($this->$fid);
  }
  //
  protected function applyFilter($rec, $direction) {
    if ($rec->getJsonFilters()) {
      foreach ($rec->getJsonFilters() as $fid => $filter) {
        $fid0 = ($filter->fid) ? $filter->fid : $fid;
        if (isset($this->$fid0)) {
          $fn = $filter->$direction;
          if ($fn) {
            if ($fn == JsonFilter::OMIT)
              unset($this->$fid);
            else
              $this->$fid = $fn($this->$fid0);
          }
        }
      }
    }
  }
  //
  static function filterIncomingValue($rec, $fid, $value) {
    $filters = $rec->getJsonFilters();
    if (! empty($filters)) {
      $filter = geta($filters, $fid);
      if ($filter) {
        $fn = $filter->in;
        if ($fn)
          $value = ($fn == JsonFilter::OMIT) ? null : $fn($value);
      }
    }
    return $value;
  }
  /**
   * Instantiate from server-side
   * @param Rec $rec
   * @param bool withNulls
   * @return RecJson (or null if no data props set)
   */
  static function asOutgoing($rec, $withNulls = false) {
    $j = ($withNulls) ? new RecJsonWithNulls($rec) : new RecJson($rec);
    if ($j->isEmpty())
      return null;
    $j->applyFilter($rec, 'out');
    return $j;
  }
  /**
   * Instantiate from client-side
   * @param stdClass $obj object coming from client-side
   * @param Rec $rec record to receive this object
   * @return RecJson
   */
  static function asIncoming($obj, $rec) {
    $j = new RecJson($obj);
    $j->applyFilter($rec, 'in');
    return $j;
  }
}
/**
 * RecJson without null filtering
 */
class RecJsonWithNulls extends RecJson {
  //
  public function __construct($rec) {
    foreach ($rec as $fid => $value) {
      $this->$fid = jeval($value);
    }
  }
  public function isEmpty() {
    return false;
  }
}
/**
 * JSON Filter
 * @see self::getJsonFilters()
 */
class JsonFilter {
  //
  public $out;
  public $in;
  public $fid;
  //
  // Special 'functions'
  const OMIT = '[omit]';
  /**
   * @param string $out transform function for outgoing
   * @param string $in transform function for incoming (optional)
   * @param string $fid if different from filter key (optional)
   */
  public function __construct($out, $in = null, $fid = null) {
    $this->out = $out;
    $this->in = $in;
    $this->fid = $fid;
  }
  /**
   * Static builders
   */
  static function omit() {
    return new JsonFilter(JsonFilter::OMIT);
  }
  static function oneWay() {
    return new JsonFilter(null, JsonFilter::OMIT);
  }
  static function editableDate() {
    return new JsonFilter('formatDate', 'formatFromDate');
  }
  static function editableDateTime() {/*does not perform timezone adjustment (by design)*/
    return new JsonFilter('formatDateTime', 'formatFromDateTime');
  }
  static function editableDateApprox() {
    return new JsonFilter('formatApproxDate', 'formatFromApproxDate');
  }
  static function editableDateTimeApprox() {
    return new JsonFilter('formatApproxDateTime', 'formatFromDateTime');
  }
  static function editableTimeInformal($fid = null) {/*e.g. a readonly label*/
    return new JsonFilter('formatInformalTimeNoAdj', JsonFilter::OMIT, $fid);
  }
  static function informalDate($fid = null) {
    return new JsonFilter('formatInformalDate', JsonFilter::OMIT, $fid);
  }
  static function informalDateTime($fid = null) {/*will adjust timezone; DO NOT use for editable field, use editableTimeInformal instead*/
    return new JsonFilter('formatInformalTime', JsonFilter::OMIT, $fid);
  }
  static function serializedObject() {
    return new JsonFilter('formatSerializedObject', 'formatFromSerializedObject');
  }
  static function boolean() {
    return new JsonFilter('asBoolInt', 'toBoolInt');
  }
  static function integer() {
    return new JsonFilter('intval', 'strval');
  }
  static function reportDate() {
    return new JsonFilter('formatDate', 'formatFromDate');
  }
  static function reportDateTime() {
    return new JsonFilter('formatDateTime', 'formatFromDate');
  }
}
/**
 * Record Comparator
 */
class RecSort {
  public $fids;
  //
  const ASC = 1;
  const DESC = -1;
  /**
   * @param ('fid','-fid',..) where '-fid' indicates DESC
   *        fid may be recursive, e.g. 'UserStub.userId'
   */
  public function __construct() {
    $fids = func_get_args();
    if (! empty($fids))
      $this->setFids($fids);
  }
  protected function setFids($fids) {
    $this->fids = array();
    foreach ($fids as $fid_) {
      if (! is_array($fid_))
        $fid_ = array($fid_);
      foreach ($fid_ as $fid) {
        $fid = explode('-', trim($fid));
        if (count($fid) == 2)
          $this->fids[$fid[1]] = RecSort::DESC;
        else
          $this->fids[$fid[0]] = RecSort::ASC;
      }
    }
  }
  public function compare(/*Rec*/$r1, /*Rec*/$r2) {
    $a = array();
    foreach ($this->fids as $fid => $dir) {
      $v1 = get_recursive($r1, $fid);
      $v2 = get_recursive($r2, $fid);
      if (is_string($v1) || is_string($v2))
        $icmp = $dir * strnatcasecmp($v1, $v2);
      else
        $icmp = $dir * bccomp($v1, $v2);
      if ($icmp != 0)
        return $icmp;
    }
    return 0;
  }
  /**
   * Ex. $recs = RecSort::sort($recs, '-date', 'TrackItem.cat')
   */
  static function sort() {
    $args = func_get_args();
    $recs = array_shift($args);
    $me = new static();
    $me->setFids($args);
    usort($recs, array($me, 'compare'));
    return $recs;
  }
}
/**
 * Record Validator
 * For use in validate() methods, e.g.
 *   public function validate() {
 *     RecValidator::from($this)->requires('cat', 'name')->isNumeric('age')->validate();
 *   }
 */
class RecValidator {
  //
  public $rec;
  public $errors;  // array('fid'=>'message',..)
  //
  /**
  * @param Rec $rec
  */
  public function __construct($rec) {
    $this->rec = $rec;
  }
  /**
   * @param ('fid',..) of required fields
   */
  public function requires() {
    $fids = func_get_args();
    foreach ($fids as $fid)
      if (static::isBlank($this->rec->$fid))
        $this->setRequired($fid, null);
    return $this;
  }
  /**
   * @param ('fid',..) of date fields
   */
  public function isDate() {
    $fids = func_get_args();
    foreach ($fids as $fid) {
      $value = $this->rec->$fid;
      $this->_isDate($fid, $value);
    }
    return $this;
  }
  private function _isDate($fid, $value) {
    if (! static::isBlank($value))
      if (strtotime($value))
        return true;
      else
        $this->set($fid, ' is not a valid date.');
  }
  /**
   * @param ('fid',..) of date fields
   */
  public function isTodayOrPast() {
    $fids = func_get_args();
    foreach ($fids as $fid) {
      $value = $this->rec->$fid;
      $this->_isTodayOrPast($fid, $value);
    }
    return $this;
  }
  private function _isTodayOrPast($fid, $value) {
    if ($this->_isDate($fid, $value))
      if (isTodayOrPast($value))
        return true;
      else
        $this->set($fid, ' cannot be a date in the future');
  }
  /**
   * @param string $fid
   */
  public function isEmail($fid) {
    $value = $this->rec->$fid;
    if (! isValidEmail($value))
      $this->set($fid, ' is not a valid email address.');
    return $this;
  }
  /**
   * @param string $fid
   */
  public function isPassword($fid, $minLen = 8) {
    $value = $this->rec->$fid;
    if (strlen($value) < $minLen)
      $this->set($fid, " must be at least $minLen characters in length.");
    // TODO
    return $this;
  }
  /**
   * @param ('fid',..) of numeric fields
   */
  public function isNumeric() {
    return $this;
  }
  /**
   * Add error to collection
   * @param string $fid 'clientId'
   * @param string $msg ' is required'
   * @param string $friendlyName 'Patient' (optional)
   */
  public function set($fid, $msg, $friendlyName = null) {
    if ($this->errors == null)
      $this->errors = array();
    else if (isset($this->errors[$fid]))
      return;
    if ($friendlyName == null)
      $friendlyName = $this->rec->getFriendlyName($fid);
    $this->errors[$fid] = "<b>$friendlyName</b>$msg";
    return $this;
  }
  public function setRequired($fid, $friendlyName = null) {
    $this->set($fid, ' is required.', $friendlyName);
  }
  /**
   * @throws RecValidatorException if any accumulated erros
   */
  public function validate() {
    if (! empty($this->errors))
      throw new RecValidatorException($this);
  }
  //
  static function from($rec) {
    return new self($rec);
  }
  static function isBlank($s) {
    return (trim($s) == "");
  }
}
//
class RecValidatorException extends DisplayableException {
  //
  public $rec;     // 'Rec'
  public $errors;  // array('fid'=>'message',..)
  /**
   * @param RecValidator $rv
   */
  public function __construct($rv) {
    $this->rec = $rv->rec->getMyName();
//	var_dump(debug_backtrace());
	$this->errors = $rv->errors;
	//echo 'data/rec/_Rec: Backtrace is ';
	//var_dump(debug_backtrace());
	$this->message = 'Please correct the following validation error(s):&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<ul><li>' . implode('</li><li>', array_values($rv->errors)) . '</li></ul>.';
  }
}