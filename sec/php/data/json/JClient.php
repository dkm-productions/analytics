<?php
require_once "php/data/json/_util.php";
require_once "php/data/db/Client.php";

class JClient extends Client0 {

  // Optional children
  public $events;  // JClientEvent[]
  public $icards;  // JICard
    
	public function out() {
	  $out = "";
    $out = aqq($out, "clientId", $this->clientId); 
    $out = nqq($out, "userGroupId", $this->userGroupId); 
    $out = nqq($out, "uid", $this->uid); 
    $out = nqq($out, "firstName", $this->firstName); 
    $out = nqq($out, "lastName", $this->lastName); 
    $out = nqq($out, "middleName", $this->middleName); 
    $out = nqq($out, "name", $this->name); 
    $out = nqq($out, "sex", $this->sex); 
    $out = nqq($out, "birth", $this->birth);  // 23-Nov-2004 format 
    $out = nqq($out, "cbirth", formatConsoleDate($this->birth));  // 11/23/2004 format
    $out = nqq($out, "age", $this->age);  // '2y 5m' '32' '0y 0m 14d'
    $out = nqq($out, 'yage', $this->yage);  // numeric age in years
    $out = nqq($out, "img", $this->img); 
    $out = nqqo($out, "active", $this->active); 
    $out = nqq($out, "cdata1", $this->cdata1); 
    $out = nqq($out, "cdata2", $this->cdata2); 
    $out = nqq($out, "cdata3", $this->cdata3); 
    $out = nqq($out, "cdata4", $this->cdata4); 
    $out = nqq($out, "cdata5", $this->cdata5); 
    $out = nqq($out, "cdata6", $this->cdata6); 
    $out = nqqj($out, "shipAddress", $this->shipAddress);
    $out = nqqj($out, "emerAddress", $this->emerAddress);
    $out = nqqj($out, "spouseAddress", $this->spouseAddress);
    $out = nqqj($out, "fatherAddress", $this->fatherAddress);
    $out = nqqj($out, "motherAddress", $this->motherAddress);
    $out = nqqj($out, "pharmAddress", $this->pharmAddress);
    $out = nqqa($out, "events", $this->events);
    $out = nqq($out, "notes", $this->notes);
    $out = nqqa($out, "icards", $this->icards);
    return cb($out);
	}
}
?>