<?php
set_include_path($_SERVER['DOCUMENT_ROOT'] . '/analytics/sec/');
require_once 'php/data/LoginSession.php';
require_once 'php/dao/_util.php';
require_once 'php/dao/RegistrationDao.php';
require_once 'php/dao/SchedDao.php';
require_once 'php/data/rec/erx/ErxStatusCount.php';
require_once 'php/data/db/UserGroup.php';
require_once 'php/data/rec/sql/Messaging.php';
require_once 'php/data/rec/sql/Messaging_DocStubReview.php';
require_once 'php/c/patient-billing/CerberusBilling.php';
require_once 'data/ApiPatient.php';
//require_once 'ApiLogger.php';
/**
 * API DAO
 * Partner data accessor
 */
class ApiDao {
  //
  private $partnerId;
  //
  const PARTNER_CERBERUS = 5001;
  //
  const ID_TYPE_PRACTICE = 1;
  const ID_TYPE_USER = 2;
  const ID_TYPE_PATIENT = 3;
  //
  const REQUIRED = true;
  //
  const CLICKTATE_BASE_URL = 'https://www.clicktate.com/cert/';
  /**
   * Constructor
   * @param int $partnerId
   */
  public function __construct($partnerId) {
    $this->partnerId = $partnerId;
  }
  /**
   * Login to Clicktate
   * @param ApiLogin $login
   * @return array(int session_id, int unread_msgs, int unreviewed, string msg_url, string review_url, string doc_url, string status_url, string pharm_url, string scan_url, string track_url, string report_url)
   */
   
  public function login($login, $isAutomatedLogin = false) {
    $this->lookupUserId($login, ApiDao::REQUIRED);
    try {
	  //echo 'ApiDao: ID Looked up! Logging user in......';
      session_start();
      unset($_SESSION["mylogin"]);
      session_regenerate_id(true);
      $sid = session_id();
      $papuid = $login->userId;
      $pappw = $login->password;
      $papsess = $login->session;
      $papcookie = $login->cookie;
	  //echo 'Starting LoginSession for login with ' . $login->getUserUid() . ' and ' . $login->password;
	  $r = LoginSession::login($login->getUserUid(), $login->password, $sid, $isAutomatedLogin)->setUi(false);  // TODO get tablet setting
	  //echo 'Done with LoginSession';
      $_SESSION['mylogin'] = $login;
      session_write_close();
      global $login;
      $login = $r;
	  
	  if (!$isAutomatedLogin) {
		  $unread = Messaging::getMyUnreadCt();
		  $unreviewed = Messaging_DocStubReview::getUnreviewedCt();
	  }
      $urlMsg = $this->buildUrl("messages.php", $sid);
      $urlReview = $this->buildUrl("review.php", $sid);
      $urlDoc = $this->buildUrl("documents.php", $sid);
      $urlStatus = $this->buildUrl("erxstatus.php", $sid);
      $urlPharm = $this->buildUrl("erxpharm.php", $sid);
      $urlScan = $this->buildUrl("scanning.php", $sid);
      $urlTrack = $this->buildUrl("tracking.php", $sid);
      $urlReport = $this->buildUrl("reporting.php", $sid);
      $urlDash = $this->buildUrl("welcome.php", $sid);
      $urlFace = $this->buildUrl("face.php", $sid, "aid=");
      //CerberusBilling::login($login->userGroupId, $papuid, $pappw);
	  //echo 'login: Before billing...';
      if (!$isAutomatedLogin) CerberusBilling::activateSession($r->userGroupId, $papuid, $pappw, $papsess, $papcookie);
	  //echo 'CerberusBilling done in ApiDao. Returning an array';
      return array($sid);//, $unread, $unreviewed, $urlMsg, $urlReview, $urlDoc, $urlStatus, $urlPharm, $urlScan, $urlTrack, $urlReport, $urlDash, $urlFace, 'dummy');
    } catch (LoginInvalidException $e) {
      $login->throwApiException('ID or password not recognized! We got message ' . $e->getMessage() . ', login is ' . print_r($login));
	  //var_dump(debug_backtrace());
    }
  }
  /**
   * Poll status numbers
   * @param ApiPollStatus $pollStatus
   * @return array(int unread_msgs, int unreviewed, int status, int status_all, int pharm, int pharm_all)
   */
  public function pollStatus($pollStatus) {
    require_once '../sec/php/newcrop/NewCrop.php';
    try {
      $newcrop = new NewCrop();
      $unread = Messaging::getMyUnreadCt();
      $unreviewed = Messaging_DocStubReview::getUnreviewedCt();
      $ncStatuses = $newcrop->pullAcctStatusDetails();
      $ncPharmReqs = $newcrop->pullAllRenewalRequests();
      $s = ErxStatusCount::fromNewCrop($ncStatuses, $ncPharmReqs);
      return array($unread, $unreviewed, $s->statusCount, $s->statusCountAll, $s->pharmComCount, $s->pharmComCountAll);
    } catch (Exception $e) {
      log2_r($e->getMessage(), 'Exception: pollStatus');
      throw new ApiException("Unable to contact ERX system.");
    }
  }
  public function requireLogin($sessionId) {
    try {
      global $login;
      session_id($sessionId);
      @session_start();
      if (! isset($_SESSION['mylogin']) || $_SESSION['mylogin'] == null)
        throw new ApiException("Invalid session.");
      $login = $_SESSION['mylogin'];
    } catch (Exception $e) {
      log2_r($e->getMessage(), 'Exception: openSession');
      throw new ApiException("Session open failed.");
    }
  }
  /**
   * Select patient and return facesheet URL
   * @param ApiSelectPatient $select
   * @return relative url 'sec/face.php?..'
   */
  public function selectPatient($select) {
    $cid = $this->lookupPatientId($select, ApiDao::REQUIRED);
    $page = $select->getUrlPage();
    $sid = trim($select->sessionId);
    $sn = session_name();
    return $this->buildUrl($page, $sid, "id=$cid");
  }
  //
  private function buildUrl($page, $sid, $params = null) {
    $rnd = mt_rand(0, 99999999);
    $params = ($params) ? "&$params" : "";
    return ApiDao::CLICKTATE_BASE_URL . "sec/$page?sess=$sid&rnd=$rnd$params";
  }
  /**
   * Save user record
   * @param ApiUser $user
   * @return int $id
   * @throws ApiException
   */
  public function saveUser($user) {
    $ugid = $this->lookupPracticeId($user, ApiDao::REQUIRED);
    $id = $this->lookupUserId($user);
    if ($id == null) {
      $id = $this->addUser($ugid, $user);
      // $this->addUserId($user->practiceId, $user->internalStaffId, $id);
    } else { 
      $this->updateUser($ugid, $user, $id);
    }
    return $id;
  }
  /**
   * Add user to USERS
   * @param int $ugid
   * @param ApiUser $user
   * @return int $id
   */
  private function addUser($ugid, $user) {
    if (empty($user->password))
      throw new ApiException("Missing required field for user add: password.");
    try {
      $iuser = User_Api::from($user, $ugid);
      log2_r($iuser, 'iUser');
      $iuser->save();
      // TODO: Add NC_USER
      return $iuser->userId;
    } catch (Exception $e) {
      log2_r($e->getMessage(), 'Exception: addUser');
      throw new ApiException("Add user failed.");
    }
  }
  private function updateUser($ugid, $user, $id) {
    try {
      $iuser = User_Api::asUpdate($user, $ugid, $id);
      log2_r($iuser, 'iUser');
      $iuser->save();
    } catch (Exception $e) {
      log2_r($e->getMessage(), 'Exception: updateUser');
      throw new ApiException("Update user failed.");
    }
  } 
  /**
   * Save practice record
   * @param ApiPractice $practice
   * @return int $ugid
   * @throws ApiException
   */
  public function savePractice($practice) {
    $ugid = $this->lookupPracticeId($practice);
    if ($ugid == null) 
      $ugid = $this->addUserGroup($practice);
    else 
      $this->updateUserGroup($ugid, $practice);
    return $ugid;
  }
  /**
   * Add practice to USER_GROUPS
   * @param ApiPractice $practice
   * @return int $ugid
   */
  private function addUserGroup($practice) {
    $userGroup = $practice->toUserGroup();
    $ugid = RegistrationDao::addUserGroup($userGroup);
    $this->addPracticeId($practice->practiceId, $ugid);
    RegistrationDao::addAddress($userGroup->toAddress($ugid));
    return $ugid;
  }
  /**
   * Update USER_GROUPS fields
   * @param int $ugid
   * @param ApiPractice $practice
   */
  private function updateUserGroup($ugid, $practice) {
    $userGroup = $practice->toUserGroup($ugid);
    $jug = UserDao::getUserGroup($ugid, true, true);
    UserDao::updateMyUserGroup($userGroup, true);
    $address = $practice->toAddress($ugid, $jug->address->id);
    if ($address->id) {
      SchedDao::updateAddress($address, null, true);
    } else {
      RegistrationDao::addAddress($address);
    }
  }
  /**
   * Save patient record
   * @param ApiPatient $practice
   * @return int $cid
   * @throws ApiException
   */
  public function savePatient($patient) {
    $ugid = $this->lookupPracticeId($patient, ApiDao::REQUIRED);
    $cid = $this->lookupPatientId($patient);
    $idPhys = $this->lookupUserId($patient);
    if ($cid == null) 
      $cid = $this->addClient($ugid, $patient, $idPhys);
    else 
      $this->updateClient($ugid, $cid, $patient, $idPhys);
    return $cid;
  }
  //
  public function /*cid*/mapPatient(/*ApiPatient*/$patient) {
    $ugid = $this->lookupPracticeId($patient, ApiDao::REQUIRED);
    $cid = $patient->externalId;
    if ($cid == null)
      throw new ApiException("ExternalID not present on supplied record.");
    $this->addPatientId($patient->practiceId, $patient->patientId, $cid);
    return $cid;
  }
  /**
   * Add patient to CLIENTS
   * @param int $ugid
   * @param ApiPatient $patient
   * @return int $cid
   */
  private function addClient($ugid, $patient, $idPhys) {
    try {
      $client = Client_Api::from($patient, $ugid, $idPhys);
      $client->save();
      $cid = $client->clientId;
      static::saveClientSegments($cid, $patient);
      $this->addPatientId($patient->practiceId, $patient->patientId, $cid);
      return $cid;
    } catch (Exception $e) {
      log2_r($e->getMessage(), 'Exception: addClient');
      throw new ApiException("Add patient failed.");
    }
  }
  /**
   * Update CLIENT fields
   * @param int $ugid
   * @param int $cid
   * @param ApiPatient $patient
   */
  private function updateClient($ugid, $cid, $patient, $idPhys) {
    try {
      $client = Client_Api::from($patient, $ugid, $idPhys, $cid);
      $client->save();
      static::saveClientSegments($cid, $patient);
    } catch (Exception $e) {
      log2_r($e->getMessage(), 'Exception: updateClient');
      throw new ApiException("Update patient failed.");
    }
  }
  private function saveClientSegments($cid, $patient) {
    $client = Client_Api::fetch($cid);
    $patient->saveAddressPrimary($cid, $client);
    $patient->saveAddressEmergency($cid, $client);
    $patient->saveAddressRx($cid, $client);
    $patient->saveICardPrimary($cid);
    $patient->saveICardSecondary($cid);
  }
  /**
   * Build patient from CLIENTS
   * @param int $cid
   * @return ApiPatient
   * @throws ApiException
   */
  public function getPatient($cid) {
    $client = Client_Api::fetch($cid);
    if ($client == null) {
      throw new ApiException("Patient ID '$cid' does not exist.");
    }
    $patient = ApiPatient::fromClient($client);
    return $patient;
  }
  /**
   * Lookup ID cross-reference
   * @param int $partnerId
   * @param int $practiceId
   * @param ApiDao::ID_TYPE_ $type
   * @param int $id
   * @return int internalId if found, null if not
   */
  public function lookupId($partnerId, $practiceId, $type, $id) {
    $sql = <<<eos
SELECT internal_id
FROM api_id_xref
WHERE partner=$partnerId AND practice_id=$practiceId AND type=$type AND external_id=$id
eos;
    return fetchField($sql);
  }
  private function lookupPracticeId($record, $required = false) {
    $ugid = $this->lookupId($this->partnerId, $record->practiceId, ApiDao::ID_TYPE_PRACTICE, $record->practiceId);
    if ($required && $ugid == null) {
      $record->throwApiException('Practice ID does not exist');
    }
    return $ugid;
  }
  private function lookupPatientId($record, $required = false) {
    $cid = $this->lookupId($this->partnerId, $record->practiceId, ApiDao::ID_TYPE_PATIENT, $record->patientId);
    if ($required && $cid == null)
      $record->throwApiException('Patient ID does not exist');
    return $cid;
  }
  /**
   * Lookup user on internal USER table
   * @param ApiUser $user
   * @return int $id if found, null if not
   */
  public function lookupUserId($record, $required = false) {
    $uid = $record->getUserUid();
	//echo 'uid is "' . $uid . '"';
    $sql = <<<eos
SELECT user_id
FROM users
WHERE uid_='$uid'
eos;
	if (MyEnv::$IS_ORACLE) {
		//echo 'lookupUserId: this is ORACLE! Adding user_id as argument.';
		$id = fetchField($sql, 'user_id');
	}
    else {
		$id = fetchField($sql);
	}
	//echo 'lookupUserId: required is ' . $required . ' and id is ' . $id;
    if ($required && $id == null) {
      $record->throwApiException('User ID does not exist');
    }
	//echo 'lookupUserId: Returning ' . $id;
    return $id;
  }
  /**
   * Add ID cross-reference
   * @param int $partnerId
   * @param int $practiceId
   * @param ApiDao::ID_TYPE_ $type
   * @param int $externalId
   * @param int $internalId
   */
  public function addId($partnerId, $practiceId, $type, $externalId, $internalId) {
    $sql = <<<eos
INSERT INTO api_id_xref
VALUES($partnerId, $practiceId, $type, $externalId, $internalId)
eos;
    insert($sql);
  }
  private function addPracticeId($practiceId, $ugid) {
    return $this->addId($this->partnerId, $practiceId, ApiDao::ID_TYPE_PRACTICE, $practiceId, $ugid);
  }
  private function addPatientId($practiceId, $patientId, $cid) {
    return $this->addId($this->partnerId, $practiceId, ApiDao::ID_TYPE_PATIENT, $patientId, $cid);
  }
  private function addUserId($practiceId, $internalStaffId, $id) {
    return $this->addId($this->partnerId, $practiceId, ApiDao::ID_TYPE_USER, $internalStaffId, $id);
  }
}
