<?php
require_once 'batch/mass-mailer/MassEmail.php';
//
class EmailCampaign extends MassEmail {
  //
  public $subject = 'Clicktate Referral Program';
  public $bcc = 'wghornsby@gmail.com';
  //
  static $BODY_FILE = 'batch/mass-mailer/referral/body.html';
  static $BODY_FIELDS = array();
}