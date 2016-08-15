<?php
require_once 'php/data/rec/sql/_SqlRec.php';
/**
 * Insurance Card Record
 */
class ICard extends SqlRec {
  //
  public $clientId;
  public $seq;
  public $planName;
  public $subscriberName;
  public $nameOnCard;
  public $groupNo;
  public $subscriberNo;
  public $dateEffective;
  public $active;
  //
  const SEQ_PRIMARY = '1';
  const SEQ_SECONDARY = '2';
  public static $SEQS = array(
    ICard::SEQ_PRIMARY => 'Primary',
    ICard::SEQ_SECONDARY => "Secondary");
  //
  public function getSqlTable() {
    return 'client_icards';
  }
  //
  /**
   * Static fetchers
   */
  public static function fetchAllByClient($clientId) {
    $rec = new ICard($clientId);
    return SqlRec::fetchAllBy($rec);
  }
}
