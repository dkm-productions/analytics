<?php
require_once 'php/data/csv/client-import/_CsvImportRec.php';
require_once 'php/data/csv/client-import/ClientImport.php';
//
class KerkeringRec extends CsvImportRec {
  //
  public $uid;
  public $otherId;
  public $first;
  public $last;
  public $sex;
  public $dob;
  public $age;
  public $addr1;
  public $addr2;
  public $addr3;
  public $city;
  public $state;
  public $zip;
  public $phone1;
  public $phone2;
  public $email;
  public $employer;
  public $ssn;
  //
  public function getUgid() {
    return 1817;
  }
  public function asClientImport() {
    $c = ClientImport::fromCsv($this->getUgid(), $this->uid, $this->last, $this->first, null, $this->sex, $this->dob, $this->ssn, $this->employer);
    $c->Address_Home = AddressImport::fromCsv($this->addr1, $this->addr2, $this->addr3, $this->city, $this->state, $this->zip, $this->phone1, $this->phone2, $this->email);
    return $c;
  }
  //
  static function hasHeaderRow() {
    return false;
  }
  static function read() {
    return parent::read('php\data\csv\client-import\kerk\kerkering.csv', __CLASS__);
  }
}
