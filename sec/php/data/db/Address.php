<?php
require_once "php/data/db/_util.php";

class Address0 {

  public $id;
  public $tableCode;
  public $tableId;
  public $type;  
  public $addr1;
  public $addr2;
  public $addr3;
  public $city;
  public $state;
  public $zip;
  public $country;
  public $phone1;
  public $phone1Type;
  public $phone2;
  public $phone2Type;
  public $phone3;
  public $phone3Type;
  public $email1;
  public $email2;
  public $name;
  
  // Derived
  public $phoneAll1;  // 502 930-0777 (residence)
  public $phoneAll2;
  public $phoneAll3;
  
  const TABLE_USERS = "U";
  const TABLE_USER_GROUPS = "G";
  const TABLE_CLIENTS = "C";
  
  const ADDRESS_TYPE_SHIP = 0;
  const ADDRESS_TYPE_BILL = 1;
  const ADDRESS_TYPE_EMER = 2;
  const ADDRESS_TYPE_SPOUSE = 3;
  const ADDRESS_TYPE_RX = 4;
  const ADDRESS_TYPE_MOTHER = 5;  
  const ADDRESS_TYPE_FATHER = 6;  
  
  const PHONE_TYPE_RES = 0;
  const PHONE_TYPE_WORK = 1;
  const PHONE_TYPE_CELL = 2;
  const PHONE_TYPE_EMER = 3;
  const PHONE_TYPE_FAX = 4;
  const PHONE_TYPE_OTHER = 9;
    
  public function __construct(
      $id, $tableCode, $tableId, $type, $addr1 = null, $addr2 = null, $addr3 = null, 
      $city = null, $state = null, $zip = null, $country = null, 
      $phone1 = null, $phone1Type = null, $phone2 = null, $phone2Type = null, $phone3 = null, $phone3Type = null, 
      $email1 = null, $email2 = null, $name = null) {
     
    $this->id = $id;
    $this->tableCode = $tableCode;
    $this->tableId = $tableId;
    $this->type = $type;
    $this->addr1 = $addr1;
    $this->addr2 = $addr2;
    $this->addr3 = $addr3;
    $this->city = $city;
    $this->state = $state;
    $this->zip = $zip;
    $this->country = $country;
    $this->phone1 = $phone1;
    $this->phone1Type = $phone1Type;
    $this->phone2 = $phone2;
    $this->phone2Type = $phone2Type;
    $this->phone3 = $phone3;
    $this->phone3Type = $phone3Type;
    $this->email1 = $email1;
    $this->email2 = $email2;
    $this->name = $name;
    $this->phone1All = $this->formatPhone($this->phone1, $this->phone1Type);
    $this->phone2All = $this->formatPhone($this->phone2, $this->phone2Type);
    $this->phone3All = $this->formatPhone($this->phone3, $this->phone3Type);
  }

  public function getFax() {
    $phone = null;
    if ($this->phone1Type == Address0::PHONE_TYPE_FAX) {
      $phone = $this->phone1;
    } else if ($this->phone2Type == Address0::PHONE_TYPE_FAX) {
      $phone = $this->phone2;
    } else if ($this->phone3Type == Address0::PHONE_TYPE_FAX) {
      $phone = $this->phone3;
    }
    return $phone;
  }
  
  private function formatPhone($phone, $type) {
    static $phoneTypes;
    if ($phone == null) return null;
    if ($type == null) return $phone;
    if ($phoneTypes == null) $phoneTypes = CommonCombos::phoneTypes();
    $pType = $phoneTypes[$type];
    if ($pType) {
      return $phone . " (" . $pType . ")";
    } else {
      return $phone;
    }
  }
  public function buildAddressLine() {
    $a = $this->addr1;
    if ($this->addr2 != "") {
      $a .= " " . $this->addr2;
    }
    if ($this->addr3 != "") {
      $a .= " " . $this->addr3;
    }
    $a .= " " . $this->buildCityStZipLine();
    return trim($a);
  }
  public function buildCityStZipLine() {
    $a = "";
    if ($this->city != "") {
      $a = $this->city;
      if ($this->state != "") {
        $a .= ", " . $this->state;
      }
      if ($this->zip != "") {
        $a .= " " . $this->zip;
      }
    }
    return trim($a);
  }
}
