<?php
require_once 'php/data/hl7/msg/seg/_HL7Segment.php';
//
/**
 * Observation 
 * @author Warren Hornsby
 */
class OBX extends HL7Segment {
  //
  public $segId = 'OBX';
  public $seq;  // Set ID - OBX (SI)
  public $valueType = 'ID_ValueType';  // Value Type (ID)
  public $obsId = 'CE';  // Observation Identifier (CE)
  public $obsSubId;  // Observation Sub-ID (ST)
  public $value;  // Observation Value (depends on valueType)
  public $units = 'CE';  // Units (CE)
  public $range;  // References Range (ST)
  public $abnormal;  // Abnormal Flags (IS)
  public $prob;  // Probability (NM)
  public $abnormTestNature;  // Nature of Abnormal Test (ID)
  public $resultStatus;  // Observation Result Status (ID)
  public $rangeEffective = 'TS';  // Effective Date of Reference Range (TS)
  public $accessChecks;  // User Defined Access Checks (ST)
  public $timestamp = 'TS';  // Date/Time of the Observation (TS)
  public $producerId = 'CE';  // Producer's ID (CE)
  public $observer;  // Responsible Observer (XCN)
  public $method = 'CE';  // Observation Method (CE)
  public $equip;  // Equipment Instance Identifier (EI)
  public $analysisDateTime = 'TS';  // Date/Time of the Analysis (TS)
  //
  /* Segments */
  public $Comment = 'NTE[]';
  //
  public function isAlphanumeric() {
    return $this->valueType->isAlphanumeric();
  }
  //
  protected function onload() {
    switch ($this->valueType->_value) {
      case 'ED':
        $this->value = ED::from($this->value);
        break;
    }
  }
}
