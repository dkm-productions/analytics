<?php
require_once "php/data/db/_util.php";
require_once 'php/data/rec/sql/_ClientRec.php';

class Client0 {

  public $clientId;
  public $userGroupId;
  public $uid;
  public $lastName;
  public $firstName;
  public $middleName;
  public $sex;
  public $birth;
  public $img;
  public $dateCreated;
  public $active;
  public $cdata1;
  public $cdata2;
  public $cdata3;
  public $cdata4;
  public $cdata5;
  public $cdata6;
  public $cdata7;
  public $notes;
  public $deceased;
  public $inactiveCode;

  // Address segments
  public $shipAddress;
  public $billAddress;
  public $emerAddress;
  public $spouseAddress;
  public $fatherAddress;
  public $motherAddress;
  public $pharmAddress;

  // Derived on construction
  public $name;
  public $formatBirth;
  public $age;   // formatted
  public $cage;  // chronological age ['y'=>#,'m'=>#,'d'=>#]
  public $yage;  // numeric age in years

  // Sex settings
  const MALE = "M";
  const FEMALE = "F";

  public function __construct($id,
        $userGroupId, $uid, $lastName, $firstName, $sex, $birth, $img, $dateCreated, $active, 
        $cdata1, $cdata2, $cdata3, $cdata4, $cdata5, $cdata6, $cdata7, $middleName, $notes, $active, $deceased) {
    $this->clientId = $id;
    $this->userGroupId = $userGroupId;
    $this->uid = $uid;
    $this->lastName = $lastName;
    $this->firstName = $firstName;
    $this->sex = $sex;
    $this->birth = $birth;
    $this->img = $img;
    $this->dateCreated = $dateCreated;
    $this->active = toBool($active);
    $this->cdata1 = $cdata1;
    $this->cdata2 = $cdata2;
    $this->cdata3 = $cdata3;
    $this->cdata4 = $cdata4;
    $this->cdata5 = $cdata5;
    $this->cdata6 = $cdata6;
    $this->cdata7 = $cdata7;
    $this->middleName = $middleName;
    $this->notes = $notes;
    $this->name = ClientRec::formatName($this);
    if ($birth != "") {
      $this->formatBirth = formatDate($birth);
      //$this->age = floor((time() - strtotime($birth)) / 31556926);
      $a = chronAge($birth);
      $this->cage = $a;
      $this->yage = $a['y'];
      if ($this->yage >= 3) {
        $this->age = $this->yage;
      } else if ($this->yage > 0) {
        $this->age = $this->yage . 'y ' . $a['m'] . 'm';
      } else {
        $this->age = $this->yage . 'y ' . $a['m'] . 'm ' . $a['d'] . 'd';
      }
    } else {
      $this->formatBirth = "";
    }
  }
}

