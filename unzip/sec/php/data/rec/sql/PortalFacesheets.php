<?php
require_once 'php/data/rec/sql/PortalUsers_Session.php';
require_once 'php/data/rec/sql/Clients.php';
require_once 'php/data/rec/sql/Allergies.php';
require_once 'php/data/rec/sql/Meds.php';
require_once 'php/data/rec/sql/Procedures.php';
require_once 'php/data/rec/sql/Immuns.php';
require_once 'php/data/rec/sql/Vitals.php';
require_once 'php/data/rec/sql/Diagnoses.php';
//
/**
 * Patient Facesheet Accessor
 * @author Warren Hornsby
 */
class PortalFacesheets {
  /**
   * @param int $cid
   * @return PortalFacesheet
   */
  static function getMine() {
    $sess = PortalSession::get();
    return PortalFacesheet::fetchFor($sess);
  }
}
/**
 * Rec PortalFacesheet
 */
class PortalFacesheet extends Rec {
  //
  public /*Client_P*/ $Client;
  public /*Allergy_P[]*/ $Allergies;
  public /*Med_P[]*/ $Meds;
  public /*Proc_P[]*/ $Procs;
  public /*Immun_P[]*/ $Immuns;
  public /*Vital_P[]*/ $Vitals;
  public /*Diagnosis_P[]*/ $Diagnoses;
  //
  static function fetchFor($sess) {
    $rec = new self();
    $rec->Client = Client_P::fetchFor($sess);
    $rec->Allergies = Allergy_P::fetchFor($sess);
    $rec->Meds = Med_P::fetchFor($sess);
    $rec->Procs = Proc_P::fetchFor($sess);
    $rec->Immuns = Immun_P::fetchFor($sess);
    $rec->Vitals = Vital_P::fetchFor($sess);
    $rec->Diagnoses = Diagnosis_P::fetchFor($sess);
    return $rec;
  }
}
class Client_P extends Client implements ReadOnly {
  //
  static function fetchFor($sess) {
    $rec = static::fetchWithDemo($sess->clientId, 'Address_P', 'ICard_P');
    unset($rec->userRestricts);
    return $rec;
  }
}
class Address_P extends ClientAddress implements ReadOnly {
  //
}
class ICard_P extends ICard implements ReadOnly {
  //
}
class Allergy_P extends Allergy implements ReadOnly {
  //
  static function fetchFor($sess) {
    $class = ($sess->erx) ? 'FaceAllergy' : 'FaceAllergyNc';
    return $class::fetchAllActive($sess->clientId, __CLASS__);
  }
  static function asCriteria($cid) {
    return FsDataRec::_asFaceCriteria($cid, __CLASS__);
    return $c;
  }
  static function asActiveCriteria($cid) {
    return FsDataRec::_asActiveFaceCriteria($cid, __CLASS__);
  }
}
class Med_P extends Med implements ReadOnly {
  public function toJsonObject(&$o) {
    $o->_friendlySig = Med::friendlySig($this->text);
  }
  //
  static function fetchFor($sess) {
    $class = ($sess->erx) ? 'FaceMed' : 'FaceMedNc';
    return $class::fetchAllActive_forPortal($sess->clientId, __CLASS__);
  }
  static function asCriteria($cid) {
    return FsDataRec::_asFaceCriteria($cid, __CLASS__);
    return $c;
  }
  static function asActiveCriteria($cid) {
    return FsDataRec::_asActiveFaceCriteria($cid, __CLASS__);
  }
}
class Proc_P extends Proc implements ReadOnly {
  //
  static function fetchFor($sess) {
    $ugid = $sess->userGroupId;
    $c = self::asCriteria($ugid);
    $c->Ipc = Ipc::asRequired_noAdmin($ugid);
    $c->clientId = $sess->clientId;
    $recs = self::fetchAllBy($c);
    self::loadResults($recs, $ugid);
    $recs = self::summarizeResults($recs);
    return Rec::sort($recs, new RecSort('-date', 'Ipc.name'));
  }
  //
  private static function loadResults(&$recs, $ugid) {
    foreach ($recs as &$rec)
      $rec->ProcResults = ProcResult_P::fetchAll($rec, $ugid);
    return $recs;
  }
}
class ProcResult_P extends ProcResult implements ReadOnly {
  //
  static function fetchAll($proc, $ugid) {
    $c = self::asCriteria($proc->procId, $ugid);
    return self::fetchAllBy($c, new RecSort('seq'));
  }
}
class Immun_P extends Immun implements ReadOnly {
  //
  static function fetchFor($sess) {
    return self::fetchAll($sess->clientId);
  }
}
class Vital_P extends Vital implements ReadOnly {
  //
  static function fetchFor($sess) {
    $recs = FaceVital::fetchAllActive($sess->clientId, __CLASS__);
    return Rec::sort($recs, new RecSort('-date'));
  }
  static function asCriteria($cid) {
    return FsDataRec::_asFaceCriteria($cid, __CLASS__);
    return $c;
  }
  static function asActiveCriteria($cid) {
    return FsDataRec::_asActiveFaceCriteria($cid, __CLASS__);
  }
}
class Diagnosis_P extends Diagnosis implements ReadOnly {
  //
  static function fetchFor($sess) {
    return FaceDiagnosis::fetchAllActive_forPortal($sess->clientId, __CLASS__);
  }
  static function asCriteria($cid) {
    return FsDataRec::_asFaceCriteria($cid, __CLASS__);
    return $c;
  }
  static function asActiveCriteria($cid) {
    return FsDataRec::_asActiveFaceCriteria($cid, __CLASS__);
  }
}
