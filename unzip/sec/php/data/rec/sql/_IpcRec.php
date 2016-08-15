<?php
require_once 'php/data/rec/sql/_SqlLevelRec.php';
/**
 * Internal Proc Code
 */
abstract class IpcRec extends SqlGroupLevelRec {
  /*
  public $ipc;
  public $userGroupId;
  public $name;
  public $desc;
  public $cat;
  public $code;  
  public $codeSystem;
  public $qref;
  public $codeSnomed;
  public $codeIcd9;
  public $codeCpt;
  public $codeLoinc;
  public $codeIcd10;
  */
  const CAT_LAB = '1';
  const CAT_NUCLEAR = '2';
  const CAT_RADIO = '3';
  const CAT_TEST = '5';
  const CAT_PROC = '6';
  const CAT_SURG = '10';
  const CAT_ADMIN = '20';
  const CAT_ADMIN_POC = '21';
  const CAT_ADMIN_FS = '22';
  const CAT_OTHER = '99';
  static $CATS = array(
    self::CAT_LAB => 'Labs',
    self::CAT_NUCLEAR => 'Nuclear Medicine',
    self::CAT_RADIO => 'Radiology',
    self::CAT_TEST => 'Tests',
    self::CAT_PROC => 'Procedures',  // diagnostic
    self::CAT_SURG => 'Surgical',
    self::CAT_ADMIN => 'Administrative',
    self::CAT_ADMIN_POC => 'AD-Plan of Care',  
    self::CAT_ADMIN_FS => 'AD-Functional Status',  
    self::CAT_OTHER => '(Other)');
  //
  const CS_ICD9 = 'I9';
  const CS_SNOMED = 'S';
  const CS_LOINC = 'L';
  const CS_CPT4 = 'C4';
  static $CODE_SYSTEMS = array(
    self::CS_ICD9 => 'ICD9',
    self::CS_SNOMED => 'SNOMED',
    self::CS_LOINC => 'LOINC',
    self::CS_CPT4 => 'CPT4'); 
  //
  public function getSqlTable() {
    return 'iproc_codes';
  }
  public function getPkValue() {
    return null;
  }
  public function getJoinPkFid() {
    return 'ipc';
  }
  public function isRadiology() {
    return $this->cat = static::CAT_RADIO;
  }
}
abstract class IpcHmRec extends SqlClientLevelRec {
  /*
  public $ipc;
  public $reportId;
  public $userGroupId;
  public $clientId;
  public $every;
  public $interval;
  public $active;
  */
  static $ID_FIELD_COUNT = 2;
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
  public function getSqlTable() {
    return 'ipc_hm';
  }
  protected function getId() {
    return $this->ipc;
  }
}