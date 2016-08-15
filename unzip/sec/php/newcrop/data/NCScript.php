<?php
require_once 'php/data/Version.php';
require_once '_DomData.php';
require_once '_NameParser.php';
/**
 * NewCrop's <NCScript> structure 
 * For supplying to clickthru post
 */
class NCScript extends DomData {
  public /*CredentialsType*/ $Credentials;         
  public /*UserRoleType*/ $UserRole;            
  public /*DestinationType*/ $Destination;     
  public /*AccountType*/ $Account;             
  public /*LocationType*/ $Location;            
  public /*LicensedPrescriberType*/ $LicensedPrescriber;  
  public /*StaffType*/ $Staff;  
  public /*PatientType*/ $Patient;
  public /*PrescriptionRenewalResponseType*/ $PrescriptionRenewalResponse;
  public /*[OutsidePrescriptionType]*/ $OutsidePrescription;
  public $_xmlns = 'http://secure.newcropaccounts.com/interfaceV7';
  //
  static $FRIENDLY_NAMES = array(
    'Account.accountPrimaryFaxNumber' => 'Practice fax number',
    'Account.accountPrimaryPhoneNumber' => 'Practice phone number',
    'AccountAddress.address1' => 'Practice address',
  	'AccountAddress.city' => 'Practice city',
  	'AccountAddress.state' => 'Practice state',
  	'AccountAddress.zip' => 'Practice zip',
    'LocationAddress.address1' => 'Practice address',
  	'LocationAddress.city' => 'Practice city',
  	'LocationAddress.state' => 'Practice state',
  	'LocationAddress.zip' => 'Practice zip',
    'LicensedPrescriber.dea' => 'Licensed prescriber DEA',
    'Location.pharmacyContactNumber' => 'Practice phone number',
    'PatientAddress.address1' => 'Patient home address',
  	'PatientAddress.city' => 'Patient home city',
  	'PatientAddress.state' => 'Patient home state',
  	'PatientAddress.zip' => 'Patient home zip'
  );
  /**
   * @param string $date
   * @return 'CCYYMMDD'
   */
  public static function formatDate($date) {
    return date('Ymd', strtotime($date));
  }
}
// Types
//
class AccountType extends DomData {
  public $_ID;
  //
  public $accountName;
  public $siteID;
  public /*AddressType*/ $AccountAddress;  
  public $accountPrimaryPhoneNumber = DomData::REQUIRED;
  public $accountPrimaryFaxNumber = DomData::REQUIRED;
  /**
   * @param User $user
   * @param Credentials $cred
   * @return AccountType
   */
  public static function fromUser($user, $cred) {
    $ug = $user->userGroup;
    $id = $cred->accountId;
    return new AccountType(
      $id,
      LocationType::fixCodes($ug->name),
      $cred->siteId,
      AddressType::fromAddress($ug->address),
      $ug->address->phone1,
      $ug->address->getFax());
  }
}  
//
class AddressType extends DomData {
  public $address1 = DomData::REQUIRED;
  public $address2;
  public $city = DomData::REQUIRED;
  public $state = DomData::REQUIRED;
  public $zip = DomData::REQUIRED;
  public $zip4;
  public $country = 'US';
  /**
   * @param Address $addr
   * @return AddressType
   */
  public static function fromAddress($addr) {
    $zip = AddressType::splitZip($addr->zip);
    return new AddressType(
      static::fixCodes($addr->addr1),
      static::fixCodes($addr->addr2),
      $addr->city,
      $addr->state,
      $zip['zip5'],
      $zip['zip4']);
  }
  //
  private static function fixCodes($text) {
    $text = str_replace(';', '-', $text);
    $text = str_replace('&', '-', $text);
    $text = str_replace('*', '-', $text);
    $text = str_replace('+', ',', $text);
    $text = str_replace('[', '(', $text);
    $text = str_replace(']', ')', $text);
    $text = str_replace('?', '-', $text);
    $text = str_replace('/', '-', $text);
    return substr($text, 0, 35);
  }
  private static function splitZip($zip) {
    $zip5 = array(substr($zip, 0, 5));
    if (strlen($zip) >= 9)
      $zip4 = substr($zip, -4);
    else
      $zip4 = '';
    return array(
      'zip5' => $zip5, 
      'zip4' => $zip4);
  }
}
//
class ContactType extends DomData {
  public $homeTelephone;
  public $workTelephone;
  public $cellularTelephone;
  public $pagerTelephone;
  public $fax;
  public $email;
  public $backOfficeTelephone;
  public $backOfficeFax;
  /**
   * @param Client $client
   * @return ContactType
   */
  public static function fromClient($client) {
    return new ContactType(
      static::fixCodes($client->Address_Home->phone1),
      static::fixCodes($client->Address_Home->phone2),
      static::fixCodes($client->Address_Home->phone3),
      null,
      null,
      $client->Address_Home->email1);
  }
  private static function fixCodes($text) {
    $text = str_replace(';', '-', $text);
    $text = str_replace('&', '-', $text);
    $text = str_replace('*', '-', $text);
    $text = str_replace('+', ',', $text);
    $text = str_replace('[', '(', $text);
    $text = str_replace(']', ')', $text);
    $text = str_replace('?', '-', $text);
    $text = str_replace('/', '-', $text);
    return substr($text, 0, 35);
  }
}
//
class CredentialsType extends DomData {
  public $partnerName;
  public $name;
  public $password;
  public $productName;
  public $productVersion;
  /**
   * @param NewCrop.Credentials $cred
   * @return CredentialsType
   */
  public static function fromCredentials($cred) {
    return new CredentialsType(
      $cred->partner,
      $cred->name, 
      $cred->password, 
      'www.clicktate.com', 
      Version::MAJOR);
  }
}
//
class DestinationType extends DomData {
  public /*RequestedPageType*/ $requestedPage;
  public $logoutPage;
  public $sessionTimeoutInMinutes;
  public $messageTransactionId;
}
//
class LicensedPrescriberType extends DomData {
  public $_ID;
  //
  public /*PersonNameType*/ $LicensedPrescriberName;  
  public $dea = DomData::REQUIRED;
  public $prescriberStatus;
  public $upin;
  public $licenseState;
  public $licenseNumber;
  public $prescriberNetwork;
  public $prescriberStartDateTime;
  public $prescriberStopDateTime;
  public $npi;
  public $freeformCredentials;
  /**
   * @param User $user
   * @param Credentials $cred
   * @return LicensedPrescriberType
   */
  public static function fromUser($user, $cred) {
    return new LicensedPrescriberType(
      $cred->lpId,
      PersonNameType::fromuser($user),
      $user->dea,
      null,  // prescriberStatus
      null,  // upin
      $user->licenseState,
      $user->license,
      null,  // prescriberNetwork
      null,  // prescriberStartDateTime
      null,  // prescriberStopDateTime
      $user->npi,
      $user->NcUser->freeformCred);    
  }
}
//
class LocationType extends DomData {
  public $_ID;
  //
  public $locationName;
  public $locationShortName;
  public /*AddressType*/ $LocationAddress = DomData::REQUIRED;  
  public $primaryPhoneNumber;
  public $primaryFaxNumber;
  public $pharmacyContactNumber = DomData::REQUIRED;
  /**
   * @param User $user
   * @param Credentials $cred
   * @return LocationType
   */
  public static function fromUser($user, $cred) {
    $addr = $user->userGroup->address;
    $rxContact = $addr->phone1; 
    return new LocationType(
      $cred->locationId,
      static::fixCodes($user->userGroup->name),
      null,  // shortName
      AddressType::fromAddress($addr),
      $addr->phone1,
      $addr->getFax(),
      $rxContact);
  }
  public static function fixCodes($text) {
    $text = str_replace(';', '-', $text);
    $text = str_replace('&', '', $text);
    $text = str_replace('*', '-', $text);
    $text = str_replace('"', "'", $text);
    $text = str_replace('+', ',', $text);
    $text = str_replace('=', '-', $text);
    $text = str_replace('?', '-', $text);
    $text = str_replace('<', '(', $text);
    $text = str_replace('>', ')', $text);
    $text = str_replace('$', 'USD', $text);
    $text = str_replace('[', '(', $text);
    $text = str_replace(']', ')', $text);
    return substr($text, 0, 50);
  }
}
//
class OutsidePrescriptionType extends DomData {
  public $externalId;
  public $date;
  public $doctorName;
  public $drug;
  public $dispenseNumber;
  public $sig;
  public $refillCount;
  public $prescriptionType = 'reconcile';
  /**
   * @param [JDataMed] $meds
   * @param array(OutsidePrescriptionType,..)
   * @return OutsidePrescriptionType
   */
  public static function fromMeds($meds) {
    if ($meds && count($meds) > 0) { 
      $recs = array();
      foreach ($meds as $med) {
        if ($med->name) 
          $recs[] = OutsidePrescriptionType::fromMed($med);
      }
      return $recs;
    }
  }
  //
  private static function fromMed($med) {
    $refills = null;
    if ($med->rx != null) {
      $a = explode('Refills: ', $med->rx);
      if (count($a) > 1) 
        $refills = substr($a[1], 0, -1);
    }
    return new OutsidePrescriptionType(
      $med->dataMedId,
      NCScript::formatDate($med->date),
      null,
      OutsidePrescriptionType::fixCodes($med->name),
      intval($med->disp),
      OutsidePrescriptionType::fixCodes($med->formatSig()),
      intval($refills));
  }
  private static function fixCodes($text) {
    $text = str_replace(';', '-', $text);
    $text = str_replace('&', ',', $text);
    $text = str_replace('*', '-', $text);
    $text = str_replace('+', ',', $text);
    $text = str_replace('=', 'eq', $text);
    $text = str_replace('<', 'lt', $text);
    $text = str_replace('>', 'gt', $text);
    $text = str_replace('[', '(', $text);
    $text = str_replace(']', ')', $text);
    $text = str_replace('?', '-', $text);
    $text = str_replace('/', '-', $text);
    return substr($text, 0, 80);
  }
}
// 
class PatientDiagnosisType extends DomData {
  public $diagnosisID = DomData::REQUIRED;
  public $diagnosisType;
  public $onsetDate;
  public $diagnosisName;
  public $recordedDate;
  /**
   * @param [JDataDiagnosis] $diagnoses
   * @return array(PatientDiagnosisType,..)
   */
  public static function fromDiagnoses($diagnoses) {
    if ($diagnoses && count($diagnoses) > 0) {
      $recs = array();
      foreach ($diagnoses as $diagnosis) {
        if ($diagnosis->icd) 
          $recs[] = PatientDiagnosisType::fromDiagnosis($diagnosis);
      }
      return $recs;
    }
  }
  //
  private static function fromDiagnosis($diagnosis) {
    return new PatientDiagnosisType(
      $diagnosis->icd,
      'ICD9',
      null,
      PatientDiagnosisType::fixCodes($diagnosis->text));
  }
  private static function fixCodes($text) {
    $text = str_replace(';', '-', $text);
    $text = str_replace('&', ',', $text);
    $text = str_replace('*', '-', $text);
    $text = str_replace('"', "'", $text);
    $text = str_replace('+', ',', $text);
    $text = str_replace('=', '-', $text);
    $text = str_replace('?', '-', $text);
    $text = str_replace('<', '(', $text);
    $text = str_replace('>', ')', $text);
    $text = str_replace('$', 'USD', $text);
    $text = str_replace('[', '(', $text);
    $text = str_replace(']', ')', $text);
    return substr($text, 0, 255);
  }
}
//
class PatientAllergyFreeformType extends DomData {
  public $allergyName = DomData::REQUIRED;
  public $allergySeverityTypeId;
  public $allergyComment;
  /**
   * @param [Allergy,..] $allergies
   * @return array(PatientAllergyFreeformType,..)
   */
  public static function fromAllergies($allergies) {
    if ($allergies && count($allergies) > 0) {
      $recs = array();
      foreach ($allergies as $allergy) {
        if ($allergy->agent) 
          $recs[] = PatientAllergyFreeformType::fromAllergy($allergy);
      }
      return $recs;
    }
  }
  //
  private static function fromAllergy($allergy) {
    logit_r($allergy,'allergy123');
    $reactions = is_array($allergy->getReactions()) ? implode(' - ', $allergy->getReactions()) : $allergy->reactions;
    $comment = "Date: " . formatDate($allergy->date); 
    if (! empty($reactions)) 
      $comment .= " Reactions:" . $reactions;
    return new PatientAllergyFreeformType(
      $allergy->agent,
      null,
      PatientAllergyFreeformType::fixCodes($comment));
  }
  private static function fixCodes($text) {
    $text = str_replace(';', '-', $text);
    $text = str_replace('&', ',', $text);
    $text = str_replace('?', '-', $text);
    $text = str_replace('*', '-', $text);
    $text = str_replace('+', ',', $text);
    $text = str_replace('=', '-', $text);
    $text = str_replace('"', "'", $text);
    return substr($text, 0, 70);
  }
}
//
class PatientCharacteristicsType extends DomData {
  public $dob;
  public $gender;
  public $height;
  public $heightUnits;
  public $weight;
  public $weightUnits;
  public $language;
  /**
   * @param Client $client
   * @return PatientChacteristicsType
   */
  public static function fromClient($client) {
    return new PatientCharacteristicsType(
      NCScript::formatDate($client->birth),
      $client->sex);
  }
}
//
class PatientType extends DomData {
  public $_ID;
  //
  public /*PersonNameType*/ $PatientName = DomData::REQUIRED;          
  public $medicalRecordNumber;
  public $socialSecurityNumber;
  public $memo;
  public /*AddressType*/ $PatientAddress;          
  public /*ContactType*/ $PatientContact;           
  public /*PatientCharacteristicsType*/ $PatientCharacteristics = DomData::REQUIRED;
  public /*[PatientDiagnosisType]*/ $PatientDiagnosis;
  public /*[PatientAllergyFreeformType]*/ $PatientFreeformAllergy;
  /**
   * @param RequestedPageType::X $dest
   * @param Client $client
   * @param [Allergy,..] $allergies
   * @return PatientType
   */
  public static function fromClient($dest, $client, $allergies, $diagnoses) {
    $rec = null;
    if ($client) {
      $id = ($client->emrId) ? $client->emrId : $client->clientId; 
      $rec = new PatientType(
        $id,
        new PersonNameType(
          $client->lastName,
          $client->firstName,
          $client->middleName),
        self::fixCodes($client->uid),
        null,  // ssn
        null,  // memo
        AddressType::fromAddress($client->Address_Home),
        ContactType::fromClient($client),
        PatientCharacteristicsType::fromClient($client),
        PatientDiagnosisType::fromDiagnoses($diagnoses),
        PatientAllergyFreeformType::fromAllergies($allergies));
    } else {
      if ($dest == RequestedPageType::RENEWAL) 
        $rec = PatientType::newNotFoundPatient();
    }
    return $rec;    
  }
  /**
   * Fake 'not found' patient to allow pharmacy request denial clickthru
   * @return PatientType
   */
  public static function newNotFoundPatient() {
    return new PatientType(
      'NOTFOUND0',
      new PersonNameType(
        'NOT FOUND',
        'PATIENT'),
      null, 
      null,
      null,
      null,
      null,
      new PatientCharacteristicsType(),
      null,
      null);
  }
  private static function fixCodes($text) {
    $text = str_replace(';', '-', $text);
    $text = str_replace('?', '-', $text);
    $text = str_replace('&', ',', $text);
    $text = str_replace('*', '-', $text);
    $text = str_replace('+', ',', $text);
    $text = str_replace('=', '-', $text);
    $text = str_replace('"', "'", $text);
    return substr($text, 0, 40);
  }
}
//
class PersonNameType extends DomData {
  public $last;
  public $first;
  public $middle;
  public $prefix;
  public $suffix;
  /**
   * @param string $fullname 'Dr. Henry S. Collier Jr'
   * @return PersonNameType
   */
  public static function fromFullName($fullname) {
    $parser = new NameParser($fullname);
    return new PersonNameType(
      $parser->last,
      $parser->first,
      $parser->middle,
      $parser->title,
      $parser->suffix);
  }
  /**
   * @param ErxUser $user
   * @return PersonNameType
   */
  public static function fromUser($user) {
    return new PersonNameType(
      $user->NcUser->nameLast,
      $user->NcUser->nameFirst,
      $user->NcUser->nameMiddle,
      $user->NcUser->namePrefix,
      $user->NcUser->nameSuffix);
  }
}
//
class PrescriptionRenewalResponseType extends DomData {
  public $renewalRequestIdentifier = DomData::REQUIRED;
  public $responseCode = DomData::REQUIRED;
  public $refillCount;
  public $drugSchedule;
  public $responseDenyCode;
  public $messageToPharmacist;
  /**
   * @param string $renewalRequestIdentifer
   * @return PrescriptionRenewalResponseType 
   */
  public static function fromId($renewalRequestIdentifer) {
    if ($renewalRequestIdentifer)
      return new PrescriptionRenewalResponseType(
        $renewalRequestIdentifer,
        'Undetermined');
  }
}
//
class StaffType extends DomData {
  public $_ID;
  //
  public /*PersonNameType*/ $StaffName;
  public $license;
  public $npi;
  /**
   * @param ErxUser $user
   * @return StaffType
   */
  public static function fromUser($user) {
    if ($user) 
      return new StaffType(
        $user->userId,
        PersonNameType::fromUser($user));
  }
}
//
class UserRoleType extends DomData {
  public /*UserType*/ $user;  
  public /*RoleType*/ $role;
  public $name;
  public $password;
  /**
   * @param User $User
   * @return UserRoleType
   */
  public static function fromUser($user) {
    return new UserRoleType(
      $user->NcUser->userType,
      $user->NcUser->roleType);
  }
}
/**
 * NewCrop's NCStandard simple types (enumerations)
 */
class RequestedPageType {
  const COMPOSE = 'compose';
  const STATUS = 'status';
  const MEDENTRY = 'medentry';
  const RENEWAL = 'renewal';
}
class UserType {
  const LP = 'LicensedPrescriber';
  const MIDLEVEL = 'MidlevelPrescriber';  
  const STAFF = 'Staff';
  const SUP_MD = 'SupervisingDoctor';
}
class RoleType {
  const DOCTOR = 'doctor';
  const NURSE = 'nurse';
  const ADMIN = 'admin';
  const MANAGER = 'manager';
  const NURSE_NO_RX = 'nurseNoRx';
  const DOCTOR_NO_RX = 'doctorNoRx';
  const DOCTOR_RO = 'doctorReadOnly';
  const IP_RO = 'interestedPartyReadOnly';
  const SUP_MD = 'supervisingDoctor';
  const MIDLEVEL = 'midlevelPrescriber';
}
?>