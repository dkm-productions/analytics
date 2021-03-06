<?php
require_once 'php/data/hl7/msg/_HL7Message.php';
require_once 'php/data/hl7/msg/seg/PID.php';
require_once 'php/data/hl7/msg/seg/PV1.php';
require_once 'php/data/hl7/msg/seg/OBX.php';
require_once 'php/data/hl7/msg/seg/DG1.php';
require_once 'php/data/hl7/msg/seg/EVN.php';
//
/**
 * Admit/Transfer Message (Biosurveillance)
 * @author Warren Hornsby
 */
class ADTMessage extends HL7Message {
  //
  /* Segments */
  public $Header;
  public $EventType = 'EVN';
  public $PatientId = 'PID';
  //
  public function getPatientId() {
    return $this->get('PatientId');  
  }
  //
  /**
   * @param Facesheet_Hl7Adt_PubHealth $fs
   * @return ADTMessage
   */
  static function asPubHealth($fs) {
    $header = MSH_ADT::asA01($fs);
    $me = static::fromHeader($header);
    $me->PatientId = PID_ADT::from($fs);
    $me->_fs = $fs;
    return $me;
  }
  /**
   * @param Facesheet_Hl7Adt_Papyrus $fs
   * @return ADTMessage
   */
  static function asPapyrus($fs) {
    $header = MSH_ADT::asA08($fs);
    $me = static::fromHeader($header);
    $me->EventType = EVN::from($header);
    $me->PatientId = PID_ADT::from($fs);
    return $me;
  }
}
//
class PID_ADT extends PID {
  //
  /* Segments */
  public $PatientVisit = 'PV1';
  public $Observation = 'OBX';
  public $Diagnoses = 'DG1[]';
  //
  static function from($fs) {
    $me = parent::from($fs);
    $me->PatientVisit = PV1_ADT::from($fs);
    $me->Observation = OBX_ADT::asAge($fs);
    $me->Diagnoses = DG1::from($fs);
    return $me;
  }
}
class PV1_ADT extends PV1 {
  //
  static function from($fs) {
    $me = static::asEmpty();
    $me->seq = 1;
    $me->class = IS_PatientClass::asOutpatient();
    if ($fs->Doctor_Mr)
      $me->attendingDoc = XCN::fromUser($fs->Doctor_Mr);
    if ($fs->Session_Mr)
      $me->admitDateTime = TS::fromDate($fs->Session_Mr->dateService);
    return $me;
  }
}
class OBX_ADT extends OBX {
  //
  static function asAge($fs) {
    $me = self::asEmpty();
    $me->seq = 1;
    $me->valueType = IS_ValueType::asNumeric();
    $me->obsId = CE_Observation::asAge();
    $me->value = $fs->Client->ageYears;
    $me->units = CE_Units::asYears();
    $me->resultStatus = ID_ResultStatus::asFinal();
    return $me;
  }
}
class MSH_ADT extends MSH {
  //
  static function asSendable($type, $fs) {
    $msgControlId = static::makeMsgControlId($fs);
    return parent::asSendable($type, $fs->UserGroup, $msgControlId);
  }
  /**
   * @param Facesheet_Hl7Immun $fs
   */
  static function asA01($fs) {
    return static::asSendable(CM_MsgType_ADT::asA01(), $fs);
  }
  static function asA08($fs) {
    return static::asSendable(CM_MsgType_ADT::asA08(), $fs);
  }
}
class CM_MsgType_ADT extends HL7CompValue {
  //
  /* A01: Admit a patient */
  static function asA01() {  
    return static::from('A01');
  }
  /* A08: Update patient information */
  static function asA08() {  
    return static::from('A08');
  }
  static function from($trigger) {
    $me = new static();
    $me->type = 'ADT';
    $me->trigger = $trigger;
    return $me;
  }
}