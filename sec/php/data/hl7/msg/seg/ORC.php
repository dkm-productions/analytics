<?php
require_once 'php/data/hl7/msg/seg/_HL7Segment.php';
//
/**
 * Common Order
 * @author Warren Hornsby
 */
class ORC extends HL7Segment {
  //
  public $segId = 'ORC';
  public $orderControl;  // Order Control (ID)
  public $placerOrder = 'EI';  // Placer Order Number (EI)
  public $fillerOrder = 'EI';  // Filler Order Number (EI)
  public $placerGroup = 'EI';  // Placer Group Number (EI)
  public $orderStatus;  // Order Status (ID)
  public $responseFlag;  // Response Flag (ID)
  public $qtyTiming = 'TQ';  // Quantity/Timing (TQ)
  public $parent;  // Parent Order (EIP)
  public $timestamp;  // Date/Time of Transaction (TS)
  public $enteredBy;  // Entered By (XCN)
  public $verifiedBy;  // Verified By (XCN)
  public $orderProvider = 'XCN';  // Ordering Provider (XCN)
  public $enterLoc;  // Enterer's Location (PL)
  public $callback;  // Call Back Phone Number (XTN)
  public $effective;  // Order Effective Date/Time (TS)
  public $controlCodeRsn;  // Order Control Code Reason (CE)
  public $enterOrg;  // Entering Organization (CE)
  public $enterDevice;  // Entering Device (CE)
  public $actionBy;  // Action By (XCN)
  public $advBeneficNotice;  // Advanced Beneficiary Notice Code (CE) 
  public $facility;  // Ordering Facility Name (XON)  
  public $facilityAddr;  // Ordering Facility Address (XAD)
  public $facilityPhone;  // Ordering Facility Phone Number (XTN)
  public $providerAddr;  // Ordering Provider Address (XAD)
  public $statusModifier;  // Order Status Modifier (CWE)
  public $advBeneficNoticeOverride;  // Advanced Beneficiary Notice Override Reason (CWE)
  public $fillerExpectedAvail;  // Filler's Expected Availability Date/Time (TS)
  public $confidentiality;  // Confidentiality Code (CWE)
  public $orderType;  // Order Type (CWE)
  public $enterAuthMode;  // Enterer Authorization Mode (CNE)
  //
  static function asStandard() {
    $me = self::asEmpty();
    $me->orderControl = 'RE';
    return $me;
  } 
  public function getOrder() {
    if ($this->placerOrder) {
      return $this->placerOrder->_data;
    }
  }
}
