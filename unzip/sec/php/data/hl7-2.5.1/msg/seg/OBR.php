<?php
require_once 'php/data/hl7-2.5.1/msg/seg/_HL7Segment.php';
//
/**
 * Observation Request v2.5.1
 * @author Warren Hornsby
 */
class OBR extends HL7SequencedSegment {
  //
  public $segId = 'OBR';
  public $seq;  // Set ID - OBR (SI)
  public $placerOrderNo = 'EI';  // Placer Order Number (EI)
  public $fillerOrderNo = 'EI';  // Filler Order Number (EI)
  public $serviceId = 'CE';  // Universal Service Identifier (CE)
  public $priority;  // Priority _ OBR (ID)
  public $reqDateTime = 'TS';  // Requested Date/Time (TS)
  public $obsDateTime = 'TS';  // Observation Date/Time (TS)
  public $obsEndDateTime = 'TS';  // Observation End Date/Time (TS)
  public $collectVol;  // Collection Volume (CQ)
  public $collectId;  // Collector Identifier (XCN)
  public $specimenAction;  // Specimen Action Code (ID)
  public $danger = 'CE';  // Danger Code (CE)
  public $relevantInfo = 'ST';  // Relevant Clinical Information (ST)
  public $specimenReceived = 'TS';  // Specimen Received Date/Time (TS)
  public $specimenSource = 'SPS';  // Specimen Source (SPS)
  public $orderProvider = 'XCN';  // Ordering Provider (XCN)
  public $orderCallback;  // Order Callback Phone Number (XTN)
  public $placerField1;  // Placer Field 1 (ST)
  public $placerField2;  // Placer Field 2 (ST)
  public $fillerField1;  // Filler Field 1 (ST)
  public $fillerField2;  // Filler Field 2 (ST)
  public $resultRpt = 'TS';  // Results Rpt/Status Chng - Date/Time (TS)
  public $charge;  // Charge to Practice (MOC)
  public $diagServSect;  // Diagnostic Serv Sect ID (ID)
  public $resultStat;  // Result Status (ID)
  public $parentResult;  // Parent Result (PRL)
  public $qtyTiming = 'TQ';  // Quantity/Timing (TQ)
  public $resultCopyTo = 'XCN[]';  // Result Copies To (XCN)
  public $parentNo;  // Parent Number (EIP)
  public $transportMode;  // Transportation Mode (ID)
  public $reason = 'CE';  // Reason for Study (CE)
  public $princInterpreter;  // Principal Result Interpreter (NDL)
  public $asstInterpreter;  // Assistant Result Interpreter (NDL)
  public $tech;  // Technician (NDL)
  public $transcript;  // Transcriptionist (NDL)
  public $scheduled = 'TS';  // Scheduled Date/Time (TS)
  public $sampleContainers;  // Number of Sample Containers * (NM)
  public $sampleTransport = 'CE';  // Transport Logistics of Collected Sample (CE)
  public $collectorComment = 'CE';  // Collector's Comment * (CE)
  public $transportResponsibility = 'CE';  // Transport Arrangement Responsibility (CE)
  public $transportArranged;  // Transport Arranged (ID)
  public $escort;  // Escort Required (ID)
  public $patientTransportComment = 'CE';  // Planned Patient Transport Comment (CE)
  public $procCode = 'CE';  // Procedure Code (CE)
  public $procCodeModifier = 'CE';  // Procedure Code Modifier (CE)
  public $placerSuppInfo = 'CE';  // Placer Supplemental Service Information (CE)
  public $fillerSuppInfo = 'CE';  // Filler Supplemental Service Information (CE)
  public $dupeProcReason;  // Medically Necessary Duplicate Procedure Reason. (CWE)
  public $resultHandling;  // Result Handling (IS)
  //
  static $_seq = 0;
  //
  /* Segments */
  public $Observation = 'OBX[]';
  //
  public function getObservations() {
    if (! empty($this->Observation))
      return arrayify($this->Observation);
  }
}