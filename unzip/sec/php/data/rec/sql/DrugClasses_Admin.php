<?php
require_once 'php/data/rec/sql/_SqlRec.php';
//
/**
 * Drug Class Administration DAO
 * @author Warren Hornsby
 */
class DrugClasses_Admin {
  //
  static function getAll() {
    $recs = DrugClass_A::fetchAll();
    return $recs;
  }
  static function saveClass($obj) {
    $rec = new DrugClass_A($obj);
    $rec->save();
    return $rec;
  }
  static function deleteClass($obj) {
    $id = $obj->classId;
    DrugClass_A::delete(DrugClass_A::fetch($id));
    return $id;
  }
  static function saveSubclass($obj) {
    $rec = new DrugSubclass_A($obj);
    $rec->save();
    return $rec;
  }
  static function deleteSubclass($obj) {
    $id = $obj->subclassId;
    DrugSubclass_A::delete(DrugSubclass_A::fetch($id));
    return $id;
  }
  static function saveDrug($obj) {
    $rec = new DrugName_A($obj);
    $rec->save();
    return $rec;
  }
  static function deleteDrug($obj) {
    $id = $obj->nameId;
    DrugName_A::delete(DrugName_A::fetch($id));
    return $id;
  }
}
/**
 * Drug Class
 */
class DrugClass_A extends SqlRec implements NoAudit {
  //
  public $classId;
  public $name;
  public /*DrugSubclass_A[]*/ $DrugSubclasses;
  //
  function getSqlTable() {
    return 'drug_classes';
  }
  function toJsonObject(&$o) {
    $o->rid = "C$this->classId"; 
  } 
  //
  static function fetchAll() {
    $c = new DrugClass_A();
    $recs = self::fetchAllBy($c);
    foreach ($recs as $rec) 
      $rec->DrugSubclasses = DrugSubclass_A::fetchAll($rec);
    return $recs; 
  }
}
/**
 * Drug Subclass
 */
class DrugSubclass_A extends SqlRec implements NoAudit {
  //
  public $subclassId;
  public $classId;
  public $name;
  public /*DrugName_A[]*/ $DrugNames;
  //
  function getSqlTable() {
    return 'drug_subclasses';
  }
  function toJsonObject(&$o) {
    $o->rid = "S$this->subclassId"; 
  } 
  //
  static function fetchAll($drugClass) {
    $c = new self();
    $c->classId = $drugClass->classId;
    $recs = self::fetchAllBy($c);
    foreach ($recs as $rec) 
      $rec->DrugNames = DrugName_A::fetchAll($rec);
    return $recs;
  }
}
/**
 * Drug Name
 */
class DrugName_A extends SqlRec implements NoAudit {
  //
  public $nameId;
  public $subclassId;
  public $name;
  //
  function getSqlTable() {
    return 'drug_names';
  }
  function toJsonObject(&$o) {
    $o->rid = "N$this->nameId"; 
  } 
  //
  static function fetchAll($drugSubclass) {
    $c = new self();
    $c->subclassId = $drugSubclass->subclassId;
    return self::fetchAllBy($c); 
  }
}
