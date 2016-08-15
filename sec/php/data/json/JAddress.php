<?php
require_once "php/data/json/_util.php";
require_once "php/data/db/Address.php";

class JAddress extends Address0 {
  
  public $addrAsLine;
  public $csz;

	public function out() {
	  $out = "";
    $out = aqq($out, "id", $this->id); 
    $out = aqq($out, "tableCode", $this->tableCode); 
    $out = aqq($out, "tableId", $this->tableId); 
    $out = aqq($out, "type", $this->type); 
    if ($this->addrAsLine == null) {
      $out = aqq($out, "addr1", $this->addr1); 
      $out = aqq($out, "addr2", $this->addr2); 
      $out = aqq($out, "addr3", $this->addr3);
    }
    //if ($this->addrAsLine == null && $this->csz == null) { 
      $out = aqq($out, "city", $this->city); 
      $out = aqq($out, "state", $this->state); 
      $out = aqq($out, "zip", $this->zip); 
    //} 
    $out = aqq($out, "country", $this->country);
    $out = aqq($out, "phone1", $this->phone1); 
    $out = aqqo($out, "phone1Type", $this->phone1Type);
    $out = aqq($out, "phone1All", $this->phone1All); 
    $out = aqq($out, "phone2", $this->phone2); 
    $out = aqqo($out, "phone2Type", $this->phone2Type); 
    $out = aqq($out, "phone2All", $this->phone2All); 
    $out = aqq($out, "phone3", $this->phone3); 
    $out = aqqo($out, "phone3Type", $this->phone3Type); 
    $out = aqq($out, "phone3All", $this->phone3All); 
    $out = aqq($out, "email1", $this->email1); 
    $out = aqq($out, "email2", $this->email2); 
    $out = aqq($out, "name", $this->name);
    $out = aqq($out, "addrLine", $this->addrAsLine); 
    $out = aqq($out, "csz", $this->csz);
    return cb($out);
	}
	
	public function includeCsz() {
	  $this->csz = $this->buildCityStZipLine();
	}
	public function includeAddrLine() {
	  $this->addrAsLine = $this->buildAddressLine();
	}
}
?>