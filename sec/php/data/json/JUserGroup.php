<?php
require_once "php/data/db/_util.php";
require_once "php/data/db/UserGroup.php";

class JUserGroup extends UserGroup0 {
 
  public function out() {
    $out = "";
    $out = nqq($out, "id", $this->id);
    $out = nqq($out, "name", $this->name);
    $out = nqq($out, "usageLevel", $this->usageLevel);
    $out = nqqj($out, "address", $this->address);
    $out = nqq($out, "estAdjust", $this->estAdjust);
    $out = nqq($out, "sessionTimeout", $this->sessionTimeout);
    return cb($out);
  }
  
  public static function constructFromJson($json) {
    $o = jsondecode($json);
    $userGroup = new JUserGroup(
        get($o, "id"),
        get($o, "name"),
        get($o, "usageLevel"),
        get($o, "estAdjust"),
        get($o, 'sessionTimeout')
        );
    $a = $o->address;
    if ($a != null) {
      $userGroup->address = new JAddress(
          get($a, "id"), 
          get($a, "tableCode"), 
          get($a, "tableId"), 
          get($a, "type"), 
          get($a, "addr1"), 
          get($a, "addr2"), 
          get($a, "addr3"), 
          get($a, "city"), 
          get($a, "state"), 
          get($a, "zip"), 
          get($a, "country"), 
          get($a, "phone1"), 
          get($a, "phone1Type"), 
          get($a, "phone2"), 
          get($a, "phone2Type"), 
          get($a, "phone3"), 
          get($a, "phone3Type"), 
          get($a, "email1"), 
          get($a, "email2"), 
          get($a, "name")
          );
    }
    return $userGroup;
  }
}
?>
