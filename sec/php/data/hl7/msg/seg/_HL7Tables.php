<?php 
/**
 * HL7 Tables
 */
abstract class HL7Table {
  static $IDS;
  //
  static function lookup($text) {
    return array_search($text, static::$IDS);  
  }  
  static function getText($id) {
    return static::$IDS[$id];
  }
  static function getCodingSystem() {
    return get_called_class();
  }
}
/* RACE */
class HL70005 extends HL7Table {
  static $IDS = array(
    'I' => 'American Indian or Alaska Native',
    'A' => 'Asian or Pacific Islander',
    'B' => 'Black or African-American', 
    'W' => 'White',
    'U' => 'Unknown');
  //
  static function lookup($client) {
    switch ($client->race) {
      case Client::RACE_NATIVE_AMER_ALASKA:
        return 'I';
      case Client::RACE_ASIAN:
      case Client::RACE_HAW_PAC_ISLAND:
        return 'A';
      case Client::RACE_BLACK:
        return 'B';
      case Client::RACE_WHITE:
        return 'W';
      default:
        return 'U';
    }
  }
}
/* ETHNIC GROUP */
class HL70189 extends HL7Table {
  static $IDS = array(
    'H' => 'Hispanic or Latino',
    'NH' => 'Not Hispanic or Latino',
    'U' => 'Unknown');
  //
  static function lookup($client) {
    switch ($client->ethnicity) {
      case Client::ETHN_HISPANIC:
        return 'H';
      case Client::ETHN_NOT_HISPANIC:
        return 'NH';
      default:
        return 'U';
    }
  }
}
/* ADDRESS TYPE */
class HL70190 extends HL7Table {
  static $IDS = array(
    'B' => 'Business',
    'C' => 'Current',
    'H' => 'Home',
    'M' => 'Mailing',
    'O' => 'Office',
    'P' => 'Permanent',
    'BR' => 'Residence at Birth');
  //
  static function asHome() {
    return 'H';
  }
}
/* TELECOMMUNICATION USE */
class HL70201 extends HL7Table {  
  static $IDS = array(
    'EMR' => 'Emergency Number',
    'NET' => 'Network (Email) Address',
    'ORN' => 'Other Residence Number',
    'PRN' => 'Primary Residence Number',
    'WPN' => 'Work Number');
  //
  static function lookup($phoneType) {
    switch ($phoneType) {
      case Address::PHONE_TYPE_PRIMARY:
        return 'PRN';
      case Address::PHONE_TYPE_WORK:
        return 'WPN';
      case Address::PHONE_TYPE_EMER:
        return 'EMR';
      case Address::PHONE_TYPE_OTHER:
        return 'ORN';
    }
  }
}
//
abstract class HL7LoadableTable extends HL7Table {
  static function loadIds($ids) {  // array('ID'=>'Text',..)
    static::$IDS = $ids;
  }
}
abstract class HL7LoadableReverseTable extends HL7Table {
  static function loadIds($ids) {  // array('Text'=>'ID',..)
    static::$IDS = $ids;
  }
  static function lookup($text) {
    return geta(static::$IDS, $text);  
  }  
}
/* VACCINE ADMINISTERED */
class HL70292 extends HL7LoadableReverseTable {  
  static $IDS;  // CVX
}
/* MANUFACTURER OF VACCINE */
class HL70227 extends HL7LoadableReverseTable { 
  static $IDS;  // MVX
}
/*
 * External Coding Systems
 */
class XCodeSystems {
  const HL7_CODING_SYSTEM_TABLE = 'HL70396';
  const UCUM = 'UCUM';
  const ICD9 = 'ICD-9-CM';
  const LOINC = 'LN';
}
class XTable extends HL7Table {
  static $CS = 'XXX';
  static function getCodingSystem() {
    return static::$CS;
  }
} 
class XT_UCUM extends XTable {
  static $CS = XCodeSystems::UCUM;
  static $IDS = array(
    'mL' => 'milliliter',
    'a' => 'year');
  //
  static function asMl() {
    return 'mL';
  }
  static function asYears() {
    return 'a';
  }
}
class XT_LOINC extends XTable {
  static $CS = XCodeSystems::LOINC;
  static $LOINC = XCodeSystems::UCUM;
}
class XT_LOINC_Observation extends XT_LOINC {
  static $IDS = array(
    '21612-7' => 'REPORTED PATIENT AGE');
  //
  static function asAge() {
    return '21612-7';
  }
}