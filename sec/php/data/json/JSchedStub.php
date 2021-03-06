<?php
require_once "php/data/json/_util.php";
require_once "php/dao/SchedDao.php";
require_once "php/data/Military.php";

class JSchedStub {
  
  public $dateService;
  public $id;
  public $clientId;
  public $date;  // "11-Nov-2009"
  public $time;  // "2:30PM"
  public $type;  // "New Patient"
  public $status;  // "Seen"
  public $statusColor;  // "#F8E880"
  public $comment;
 
  public function __construct($j) {  // construct from JSched
    $this->id = $j->id;
    $this->clientId = $j->clientId;
    $this->dateService = $j->dateService;
    $this->date = formatInformalDate($j->dateService);
    $m = new Military($j->timeStart);
    $this->time = $m->formatted();
    $this->type = SchedDao::getTypeDesc($j->type);
    $status = SchedDao::getStatus($j->status);
    $comment = $j->comment;
    if ($status) {
      $this->status = $status->name;
      $this->statusColor = $status->bcolor; 
    } else {
      $this->status = "[No Status]";
      $this->statusColor = null;
    }
  }
  public function out() {
    return cb(qq("id", $this->id) 
        . C . qq("cid", $this->clientId) 
        . C . qq("date", $this->date) 
        . C . qq("time", $this->time)
        . C . qq("type", $this->type)
        . C . qq("status", $this->status)
        . C . qq("statusColor", $this->statusColor)
        . C . qq("comment", $this->comment)
        );
  }
}
?>