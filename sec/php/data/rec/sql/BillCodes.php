<?php
require_once 'php/data/rec/sql/_SqlRec.php';
//
/**
 * Billing Codes
 * DAO for BillCode
 * @author Warren Hornsby
 */
class BillCodes {
  //
  /*
   * Get most recent bill code valid for new signups
   * @return BillCode
   */
  public static function getForSignUp() {
    $c = new BillCode2();
    $c->newSignups = 1;
    $recs = BillCode2::fetchAllBy($c, new RecSort('-billCode'));
    return current($recs);
  }
}
//
/**
 * Billing Code
 */
class BillCode2 extends SqlRec implements ReadOnly {
  //
  public $billCode;
  public $newSignups;
  public $upfrontCharge;
  public $monthlyCharge;
  public $minCharge;
  public $maxCharge;
  public $registerText;
  public $createDate;
  public $discountCode;
  public $firstBill;
  public $noteCharge;
  //
  public function getSqlTable() {
    return 'bill_codes';
  }
}
