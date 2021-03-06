<?php
require_once 'php/data/rec/sql/_ClientRec.php';
require_once 'php/data/csv/client-import/AddressImport.php';
//
class ClientImport extends ClientRec {
  //
  public $clientId;
  public $userGroupId;
  public $uid;
  public $lastName;
  public $firstName;
  public $sex;
  public $birth;
  public $img;
  public $dateCreated;
  public $active;
  public $cdata1;
  public $cdata2;
  public $cdata3;
  public $middleName;
  public $notes;
  public $dateUpdated;
  public /*Address*/ $Address_Home;
  //
  public function setFromMatch($match) {
    if ($match) {
      $this->clientId = $match->clientId;
      $this->Address_Home->setFromMatch($match->Address_Home);
    }
  }
  public function save() {
    parent::save();
    $this->Address_Home->tableId = $this->clientId;
    $this->Address_Home->save();
  }
  //
  static function fromCsv($ugid, $uid, $last, $first, $middle, $sex, $birth, $cdata1 = null, $cdata2 = null, $cdata3 = null, $notes = null) {
    $rec = new self();
    $rec->userGroupId = $ugid;
    $rec->uid = $uid;
    $rec->lastName = $last;
    $rec->firstName = $first;
    $rec->middleName = $middle;
    $rec->sex = $sex;
    $rec->setBirth($birth);
    $rec->cdata1 = $cdata1;
    $rec->cdata2 = $cdata2;
    $rec->cdata3 = $cdata3;
    $rec->notes = $notes;
    $rec->active = true;
    return $rec;
  }
  static function fetchOneBy($c) {
    $rec = parent::fetchOneBy($c);
    if ($rec)
      $rec->Address_Home = AddressImport::fetchByCid($rec->clientId);
    return $rec;
  }
  static function fetchByCsv($rec) {
    if ($rec->uid)
      return self::fetchByUid($rec->getUgid(), $rec->uid);
  }
  static function fetchByUid($ugid, $uid) {
    $c = new self();
    $c->userGroupId = $ugid;
    $c->uid = $uid;
    return self::fetchOneBy($c);
  }
}
?>