<?php
require_once 'php/data/hl7/msg/seg/_HL7Tables.php';
//
/**
 * HL7 value
 */
class HL7Value extends HL7Rec {
  public $_data;  // original value (from HL7 message)
  public $_value;  // current value
  //
  public function getData() {
    return $this->_data;
  }
  public function setValue($value) {
    $this->_value = $value;
  }
  public function getValue() {
    return $this->_value;
  }
  //
  /**
   * @param string $value
   * @return HL7Value
   */
  static function from($value) {
    $me = new static();
    $me->_data = trim($value);
    $me->setValue($me->_data);
    return $me;
  }
}
/* ID Sets */
class ID_ResultStatus extends HL7Value {
  static function asFinal() {
    return 'F';
  }
}
class ID_ValueType extends HL7Value {
  /*alphanumeric*/
  const STRING = 'ST';
  const TEXT_DATA = 'TX';
  const FORMATTED_TEXT = 'FT';
  /*numerical*/
  const COMPOSITE_QTY = 'CQ';
  const MONEY = 'MO';
  const NUMERIC = 'NM';
  const SEQUENCE_ID = 'SI';
  const STRUCTURED_NUM = 'SN';
  /*identifier*/
  const HL7_ID = 'ID';
  const USERDEF_ID = 'IS';
  const PERSON_LOC = 'PL';
  const CODED_ENTRY = 'CE';
  const CODED_WITH_EXCEPTIONS = 'CWE';
  const ENCAPSULATED_DATA = 'ED';
  /*datetime*/
  const TIMESTAMP = 'TS';
  //
  public function isAlphanumeric() {
    switch ($this->_value) {
      case static::STRING:
      case static::TEXT_DATA:
      case static::FORMATTED_TEXT:
        return true;
    }
  }
}
class IS_PatientClass extends HL7Value {
  static function asOutpatient() {
    return 'O';
  }
}
class IS_Gender extends HL7Value {
  static function fromPatient($c) {
    return static::from($c->sex);
  }
}
class IS_DiagnosisType extends HL7Value {
  static function asAdmitting() {
    return 'A';
  }
  static function asFinal() {
    return 'F';
  }
}
class IS_ValueType extends HL7Value {
  static function asCodedEntry() {
    return 'CE';
  }
  static function asNumeric() {
    return 'NM';
  }
}
/* Processing Type */
class PT extends HL7Value {
  const TEST = 'T';
  const PRODUCTION = 'P';
  const DEBUGGING = 'D';
}
/**
 * HL7 component-delimited value
 */
abstract class HL7CompValue extends HL7Rec {
  public $_data;  // original value (from HL7 message)
  /**
   * @param string $value
   * @param ST_EncodingChars $encoding 
   * @return HL7CompValue
   */
  static function from($value, $encoding = null) {
    if ($encoding == null) {
      $encoding = ST_EncodingChars::asStandard();
    }
    if ($value) {
      $me = new static();
      $me->_data = trim($value);
      $me->setValues(self::decode($value, $encoding), $encoding);
      return $me;
    }
  }
  //
  protected static function isFid($var, $c1) {
    return parent::isFid($var, $c1) && ! self::isUpper($c1);
  }
  protected static function decode($value, $encoding) {  // ['value',..]
    if ($encoding) {
      $a = explode($encoding->compDelim, $value);
      if (count($a) > 1)
        return $a;
      return explode($encoding->subDelim, $value);
    } else {
      return array($value);
    }
  }
}
/**
 * Coded Entry
 */
class CE extends HL7CompValue {
  public $id;
  public $text;
  public $codingSystem;
  public $altId;
  public $altText;
  public $altCodingSystem;
  //
  public function isEmptyPrimary() {
    return (empty($this->id));
  }
  public function getId() {
    return ($this->isEmptyPrimary()) ? $this->altId : $this->id;
  }
  public function getText() {
    return ($this->isEmptyPrimary()) ? $this->altText : $this->text;
  }  
  public function getCodingSystem() {
    return ($this->isEmptyPrimary()) ? $this->altCodingSystem : $this->codingSystem;
  }
}
class CE_Local extends CE {
  static function from($id, $text = null) {
    $me = new static();
    $me->id = $id;
    $me->text = ($text) ? $text : $id;
    $me->codingSystem = 'LOCAL CODE SET';
    return $me;
  }
}
class CE_ICD9 extends CE {
  static function from($id, $text = null) {
    $me = new static();
    $me->id = $id;
    $me->text = $text;
    $me->codingSystem = XCodeSystems::ICD9;
    return $me;
  }
  static function fromDiagnosis($diag) {
    $id = $diag->icd;
    if ($id)
      return static::from($id, $diag->text);
    else
      return CE_Local::from($diag->dataDiagnosesId, $diag->text);
  }
}
abstract class CE_HL7Table extends CE {
  static $TABLE = 'HL7XXXX'; 
  //
  public function set($id, $text = null) {
    $table = static::getTable();
    $this->id = $id;
    $this->text = ($text) ? $text : $table::getText($this->id); 
    $this->codingSystem = $table::getCodingSystem(); 
  }
  //
  static function from($id, $text = null) {
    $me = new static();
    $me->set($id, $text);
    return $me;
  }
  static function fromLookup($e) {  
    $table = static::getTable();
    $id = $table::lookup($e);
    if ($id) 
      return static::from($id);
    else 
      return static::asNotFound($e);
  }
  static function asNotFound($e) {
    if (is_string($e))
      return CE_Local::from($e);
  }
  static function getTable() {
    return static::$TABLE; 
  }
}
class CE_Race extends CE_HL7Table {
  static $TABLE = 'HL70005';
  //
  static function fromPatient($c) {
    return static::fromLookup($c);
  }
}
class CE_Ethnic extends CE_HL7Table {
  static $TABLE = 'HL70189';
  //
  static function fromPatient($c) {
    return static::fromLookup($c);
  }
}
abstract class CE_HL7ReverseTable extends CE_HL7Table {
  static function fromLookup($text) {
    $table = static::getTable();
    $id = $table::lookup($text);
    if ($id) 
      return static::from($id, $text);
    else 
      return static::asNotFound($id);
  }
}
class CE_Immun extends CE_HL7ReverseTable {
  static $TABLE = 'HL70292';
  //
  static function fromImmun($imm) {
    return static::fromLookup($imm->tradeName); 
  }
}
class CE_ImmunManufac extends CE_HL7ReverseTable {
  static $TABLE = 'HL70227';
  //
  static function fromImmun($imm) {
    return static::fromLookup($imm->manufac); 
  }
}
class CE_Units extends CE_HL7Table {
  static $TABLE = 'XT_UCUM';
  //
  static function fromImmun($imm) {
    if ($imm->dose)
      return static::asMl();
  }
  static function asMl() {
    return static::from(XT_UCUM::asMl());
  }
  static function asYears() {
    return static::from(XT_UCUM::asYears());
  }
}
class CE_Observation extends CE_HL7Table {
  static $TABLE = 'XT_LOINC_Observation';
  //
  static function asAge() {
    return static::from(XT_LOINC_Observation::asAge());
  }
}
/**
 * Identifier 
 */
class CX extends HL7CompValue {
  public $id;
  public $checkDigit;
  public $checkDigitScheme;
  public $assignAuth = 'HD';
  public $idTypeCode;
  public $assignFacility = 'HD';
  public $effectiveDate;
  public $expireDate;
  public $assignJuris;  // CWE
  public $assignAgency;  // CWE
  //
  const ID_TYPE_MED_RECORD = 'MR';
  //
  static function asPatientList($fs) {
    return array(
      static::asPatient_MR($fs));
  }
  static function asPatient_MR($fs) {
    $me = static::asEmpty();
    $me->id = $fs->Client->clientId;
    $me->assignAuth = HD::asPractice($fs->UserGroup);
    $me->idTypeCode = static::ID_TYPE_MED_RECORD; 
    return $me;
  }
}
class CWE extends HL7CompValue {
  public $id;
  public $text;
  public $codingSystem;
  public $altId;
  public $altText;
  public $altCodingSystem;
  public $versionId;
  public $altVersionId;
  public $origText;
}
class ED extends HL7CompValue {
  public $source;
  public $type;
  public $subtype;
  public $encoding;
  public $data;
}
class EI extends HL7CompValue {
  public $id;
  public $namespace;
  public $univId;
  public $univIdType;
}
/**
 * Time Stamp
 */
class TS extends HL7CompValue {
  public $time;
  public $precision;
  //
  public function asSqlValue() {
    return date("Y-m-d H:i:s", strtotime($this->time));
  }
  public function asSqlDate() {
    return date("Y-m-d", strtotime($this->time));
  }
  public function asFormatted() {
    return date("d-M-Y h:iA", strtotime($this->time));
  }
  public function sanitize() {
    parent::sanitize();
    $this->_time = formatTimestamp($this->time);
    $this->_date = formatDate($this->time);
    return $this;
  }
  //
  static function fromDate($date = null) {
    $e = new static();
    if ($date && strlen($date) <= 8) {
      $e->time = $date;
    } else {    
      $ts = strtotime($date);
      $time = date('H:i:s', $ts);
      if ($time == '00:00:00')
        $e->time = date('Ymd', $ts);
      else if ($time == '01:00:00')
        $e->time = date('Ym', $ts);
      else if ($time == '02:00:00')
        $e->time = date('Y', $ts);
      else
        $e->time = date("YmdHi", $ts) . ":00";
    }
    return $e;
  }
  static function fromNow() {
    return self::fromDate(nowNoQuotes());
  }
}
/**
 * Timing Quantity
 */
class TQ extends HL7CompValue {
  public $qty;  // CQ
  public $interval;  // RI
  public $duration;
  public $start;  // TS
  public $end;  // TS
  public $priority;
  public $cond;
  public $text;
  public $conj;
  public $orderSeq;  // OSD
  public $occurDuration;  // CE
  public $occurTotal;   
}
/**
 * Specimen Source
 */
class SPS extends HL7CompValue {
  public $source = 'CWE';
  public $additives = 'CWE';
  public $collectMethod = 'CWE';
  public $bodySite = 'CWE';
  public $siteModifier = 'CWE';
  public $collectMethodModifier = 'CWE';
  public $role = 'CWE';
}
/**
 * Extended Address 
 */
class XAD extends HL7CompValue {
  public $addr1;
  public $addr2;
  public $city;
  public $state;
  public $zip;
  public $country;
  public $type;
  //
  static function fromAddress($a) {
    $me = static::asEmpty();
    $me->addr1 = $a->addr1;
    $me->addr2 = $a->addr2;
    $me->city = $a->city;
    $me->state = $a->state;
    $me->zip = $a->zip;
    return $me;
  }
  static function asHome($a) {
    $me = static::fromAddress($a);
    $me->type = HL70190::asHome();
    return $me;
  }
}
/**
 * Extended Telephone
 */
class XTN extends HL7CompValue {
  public $phone;
  public $useCode;
  public $equipType;
  public $email;
  public $countryCode;
  public $areaCode;
  public $local;
  public $ext;
  public $anyText;
  public $extPrefix;
  public $speedCode;
  public $unformatted;
  //
  static function from($phone, $phoneType) {
    $pf = Phone::from($phone);
    $me = static::asEmpty();
    $me->useCode = HL70201::lookup($phoneType);
    $me->areaCode = $pf->area;
    $me->local = $pf->local;
    return $me;
  }
  static function asHome($a) {
    $me = static::from($a->phone1, $a->phone1Type);
    return $me;
  }
}
/**
 * Extended Composite Name And Number For Persons 
 */
class XCN extends HL7CompValue {
  public $id;
  public $familyName;  // FN
  public $givenName;
  public $secondName;
  public $suffix;
  public $prefix;
  public $degree;
  public $source;
  public $assignAuth;  // HD
  public $nameType;
  public $idCheckDigit;
  public $checkDigitScheme;
  public $idTypeCode;
  public $assignFacility;  // HD
  public $nameRepresentCode;
  public $nameContext;  // CE
  public $nameValidityRange;  // DR
  public $nameAssembly; 
  public $effective;  // TS
  public $expiration;  // TS
  public $profSuffix;
  public $assignJurisdic;  // CWE
  public $assignAgency;  // CWE
  //
  public function asFormatted() {
    $s = array();
    pushIfNotEmpty($s, $this->prefix);
    pushIfNotEmpty($s, $this->givenName);
    pushIfNotEmpty($s, $this->secondName);
    pushIfNotEmpty($s, $this->familyName);
    pushIfNotEmpty($s, $this->suffix);
    return implode(' ', $s);
  }
  //
  static function fromUser($user) {
    $me = static::asEmpty();
    $me->id = $user->userId;
    $me->familyName = $user->name; 
    return $me;
  }
}
/**
 * Extended Organization Name
 */
class XON extends HL7CompValue {
  public $name;
  public $typeCode;
  public $id;
  public $checkDigit;
  public $checkDigitScheme;
  public $assignAuth;  // HD
  public $idTypeCode;
  public $assignFacility;  // HD
  public $nameRepresentCode;
  public $orgId;
}
/**
 * Extended Person Name
 */
class XPN extends HL7CompValue {
  public $last;  // FN
  public $first;
  public $middle;
  public $suffix;
  //
  public function makeFullName() {
    if (! empty($this->last) && ! empty($this->first)) 
      return trim($this->last . ', ' . $this->first . ' ' . $this->middle);
  }
  //
  static function asPatient($c) {
    $me = static::asEmpty();
    $me->last = $c->lastName;
    $me->first = $c->firstName;
    $me->middle = $c->middleName;
    return $me;
  }
}
/**
 * Identifier Manager  
 */
class HD extends HL7CompValue {
  public $namespaceId;
  public $universalId;
  public $universalIdType;
  //
  static function asPractice($ug) {
    $me = new static();
    if (is_object($ug)) {
      $me->namespaceId = $ug->userGroupId;  
    } else {
      $me->namespaceId = $ug;
    }
    return $me;
  }
  static function asClicktate() {
    $me = new static();
    $me->namespaceId = 'Clicktate';
    return $me;
  }
}
