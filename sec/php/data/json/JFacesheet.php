<?php
require_once "php/data/json/_util.php";
require_once "php/data/rec/sql/Auditing.php";

class JFacesheet {

	public $clientId;
	public $cuTimestamp;       // Last client update timestamp
	public $docs;              // JUser{user_id:} Providers in group
	public $contains;          // 1=all/2=meds/3=allergies...
  public $client;            // Client
  public $clientHistory;     // JClientHistory
  public $workflow;          // JWorkflow
  public $activeMeds;        // Med[]
  public $meds;              // Med[] 
	//public $medsHistByMed;     // Med[]
  public $medsHistByDate;    // Med[]
  public $medsLastReview;    // {'name':SessionMedNc_Review,..}
  public $updatedMed;        // Med
  public $activeAllers;      // JDataAllergy[]
  public $allergies;         // JDataAllergy[]
  public $allergiesHistory;  // JDataAllergy[]
  public $diagnoses;         // Diagnosis[]
	public $diagnosesHistory;  // Diagnosis[]
  public $vitals;            // Vital[]
//  public $hms;               // JDataHm[] Facesheet recs (summarized by proc)
//  public $hmsHistory;        // JDataHm[] All recs
//  public $hmProcs;           // LOOKUP_DATA HmProcs{_instance:} procs relevant for client
  public $ipcHms;            // IpcHm[]
  public $medhx;             // JDataSyncProcGroup
  public $sochx;             // JDataSyncGroup
  public $surghx;            // JDataSyncProcGroup
  public $procedures;        // Proc[]
  public $famhx;             // JDataSyncFamGroup
  public $immuns;            // DataImmun[]
  public $immunPid;          // pid of immun entry
  public $tracking;          // TrackItem[]
  public $docstubs;          // DocStub[]
  public $medsNcStale;       // bool @see MedsNewCrop::areAllNcStale()
  public $portalUser;        // PortalUserStub        
  //public $unreviewed;        // MsgThread_Stub[]
  public $immunCd;
  public $superbills;
  
  // Helpers
  public $audits;  
  
  const CONTAINS_ALL = 1;
  const CONTAINS_MEDS = 2;
  const CONTAINS_ALLERGIES = 3;
  const CONTAINS_VITALS = 4;
  const CONTAINS_DIAGNOSES = 5;
  const CONTAINS_CLIENT = 6;
  const CONTAINS_HM = 7;
  const CONTAINS_MEDHX = 8;
  const CONTAINS_SOCHX = 9;
  const CONTAINS_SURGHX = 10;
  const CONTAINS_FAMHX = 11;
  const CONTAINS_IMMUN = 12;
  const CONTAINS_TRACKING = 13;
  const CONTAINS_MSG = 99;  // for messaging
  
	public function __construct($clientId, $contains) {  // properties are optional and set independently
    $this->clientId = $clientId;
    $this->contains = $contains;
    $this->workflow = new JWorkflow();
	}
	private function getCuTimestamp() {
	  return Auditing::getClientUpdateTimestamp($this->clientId);
	}
	public function out() {
    $out = "";
    $out = nqq($out, "clientId", $this->clientId);
    $out = nqq($out, "cuTimestamp", $this->getCuTimestamp());
    $out = nqqaa($out, "docs", $this->docs);
    $out = nqqo($out, "contains", $this->contains);
    $out = nqqo($out, "client", jsonencode($this->client));
    $out = nqqj($out, "clientHistory", $this->clientHistory);
    $out = nqqo($out, "meds", jsonencode($this->meds));
    $out = nqqo($out, "activeMeds", jsonencode($this->activeMeds));
    //$out = nqqo($out, "medsHistByMed", jsonencode($this->medsHistByMed));
    $out = nqqo($out, "medsHistByDate", jsonencode($this->medsHistByDate));
    $out = nqqo($out, "medsLastReview", jsonencode($this->medsLastReview));
    $out = nqqo($out, "medsNcStale", $this->medsNcStale);
    $out = nqq($out, "updatedMed", $this->updatedMed);
    $out = nqqo($out, "activeAllers", jsonencode($this->activeAllers)); 
    $out = nqqo($out, "allergies", jsonencode($this->allergies)); 
    $out = nqqo($out, "allergiesHistory", jsonencode($this->allergiesHistory)); 
    $out = nqqo($out, "diagnoses", jsonencode($this->diagnoses));
    $out = nqqo($out, "diagnosesHistory", jsonencode($this->diagnosesHistory));
    $out = nqqo($out, "vitals", jsonencode($this->vitals));
    $out = nqqo($out, "tracking", jsonencode($this->tracking));
    $out = nqqo($out, "immuns", jsonencode($this->immuns));
    if ($this->immunCd)
      $out = nqqo($out, "immunCd", $this->immunCd->toJson());
    if ($this->superbills)
      $out = nqqo($out, 'superbills', jsonencode($this->superbills));
    $out = nqq($out, "immunPid", $this->immunPid);
    $out = nqqj($out, "workflow", $this->workflow);
//    $out = nqqa($out, "hms", $this->hms);
//    $out = nqqa($out, "hmsHistory", $this->hmsHistory);
//    $out = nqqo($out, "hmProcs", jsonencode($this->hmProcs));
    $out = nqqo($out, 'hms', jsonencode($this->ipcHms));
    $out = nqqj($out, "medhx", $this->medhx);
    $out = nqqj($out, "sochx", $this->sochx);
    $out = nqqj($out, "surghx", $this->surghx);
    $out = nqqo($out, "procedures", jsonencode($this->procedures));
    $out = nqqo($out, "docstubs", jsonencode($this->docstubs));
    $out = nqqj($out, "famhx", $this->famhx);
    $out = nqqo($out, "audits", jsonencode($this->audits));
    $out = nqqo($out, "portalUser", jsonencode($this->portalUser));
    //$out = nqqo($out, "unreviewed", jsonencode($this->unreviewed));
    return cb($out);    
	}
}
