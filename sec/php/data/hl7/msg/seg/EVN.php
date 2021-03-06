<?php
require_once 'php/data/hl7/msg/seg/_HL7Segment.php';
//
/**
 * Event Type
 * @author Warren Hornsby
 */
class EVN extends HL7Segment {
  //
  public $segId = 'EVN';
  public $type;  //1: Event Type Code (ID)
  public $dateRecorded = 'TS';  //2: Recorded Date/Time (TS)
  public $datePlanned = 'TS';  //3: Date/Time Planned Event (TS)
  public $reason;  //4: Event Reason Code (IS)
  public $operator = 'XCN';  //5: Operator ID (XCN)
  public $occurred = 'TS';  //6: Event Occurred (TS)
  public $facility = 'HD';  //7: Event Facility (HD)
  //
  static function from($header, $date = null) {
    $me = new static();
    $me->type = $header->msgType->trigger;
    $me->dateRecorded = ($date) ? TS::fromDate($date) : TS::fromNow();
    return $me;
  }
}
