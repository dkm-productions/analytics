<?php
require_once 'php/data/rec/sql/UserLogins.php';
//
/**
 * User Registrations DAO
 * @author Warren Hornsby 
 */
class UserRegistrations {
  //
  /**
   * @param Registration $obj
   * @return User_TrialUser
   * @throws RecValidatorException
   */
  static function create($obj) {
    $reg = Registration::revive($obj);
    $reg->save();
    $userGroup = UserGroup_TrialUser::create($reg);
    $user = User_TrialUser::create($reg, $userGroup->userGroupId);
    $address = Address_TrialUser::create($reg, $userGroup->userGroupId);
    return $user;
  }
}
class Registration extends SqlRec implements NoAuthenticate {
  //
  public $regId;
  public $uid;
  public $pw;  // not used
  public $name;
  public $email;
  public $company;
  public $license;
  public $licState;
  public $phoneNum;
  public $phoneExt;
  public $howFound;
  public $dateCreated;
  public $referrer;
  //
  static $FRIENDLY_NAMES = array(
    'uid' => 'User ID',
    'pw' => 'Password',
    'company' => 'Practice Name',
    'phoneNum' => 'Phone Number');
  //
  public function getSqlTable() {
    return 'registrations';
  }
  public function validate(&$rv) {
    $rv->requires('uid', 'name', 'email', 'company', 'licState', 'license', 'phoneNum');
    $rv->isEmail('email');
    //$rv->isPassword('pw');
    if (User_TrialUser::isUidTaken($this->uid))
      $rv->set('uid', ' is already in use. Please choose another.');
    if (User_TrialUser::isLicenseUsed($this->licState, $this->license))
      $rv->set('license', ' is already in use by another account.');
  }
  static function revive($o) {
    $me = new static($o);
    $me->dateCreated = null;
    return $me;
  }
}
class UserGroup_TrialUser extends UserGroupRec implements NoAuthenticate {
  //
  public $userGroupId;
  public $name;
  public $usageLevel;
  public $estTzAdj;
  public $sessionTimeout;
  //
  static function create($reg) {
    $rec = static::from($reg);
    $rec->save();
    return $rec;
  }
  static function from($reg) {
    $me = new static();
    $me->name = $reg->company;
    $me->usageLevel = static::USAGE_LEVEL_ERX;
    $me->estTzAdj = geta(static::$TIMEZONES_BY_STATE, $reg->licState, '0');
    $me->sessionTimeout = 60;
    return $me;
  }
}
class User_TrialUser extends UserLogin implements NoAuthenticate {
  //
  public $userId;
  public $uid;
  public $pw;
  public $name;
  public $admin;
  public $subscription;
  public $active;
  public $regId;
  public $trialExpdt;
  public $userGroupId;
  public $userType;
  public $licenseState;
  public $license;
  public $dea;
  public $npi;
  public $email;
  public $expiration;
  public $expireReason;
  public $pwExpires;
  public $tosAccepted;
  public $roleType;
  public $mixins;
  //
  /**
   * @param Registration $reg
   * @param int $ugid
   * @param int $trialLength (optional, default 14 days)
   */
  static function create($reg, $ugid, $trialLength = 14) {
    $rec = static::from($reg, $ugid, $trialLength);
    $rec->savePassword_asTemporary($reg->pw);  // does save() also
    return $rec;
  }
  static function from($reg, $ugid, $trialLength) {
    $me = new static();
    $me->uid = $reg->uid;
    $me->name = $reg->name;
    $me->admin = false;
    $me->subscription = static::SUBSCRIPTION_TRIAL;
    $me->active = true;
    $me->regId = $reg->regId;
    $me->trialExpdt = date('Y-m-d H:m:s', strtotime("+$trialLength days"));
    $me->userGroupId = $ugid;
    $me->userType = static::TYPE_DOCTOR;
    $me->licenseState = $reg->licState;
    $me->license = $reg->license;
    $me->email = $reg->email;
    $me->tosAccepted = null;  // indicates trial needs to complete setup on first login
    $me->roleType = UserRole::TYPE_TRIAL;
    return $me;
  }
  /**
   * @param string $uid
   * @return true if UID already in use
   */
  static function isUidTaken($uid) {
    if ($uid) {
      $me = new static();
      $me->uid = $uid;
      return static::count($me) > 0;
    }
  } 
  /**
   * @param string $licenseState
   * @param string $license
   * @return true if license already used for prior registration
   */ 
  static function isLicenseUsed($licenseState, $license) {
    if ($licenseState && $license) {
      $me = new static();
      $me->licenseState = $licenseState;
      $me->license = $license;
      return static::count($me) > 0;
    }
  }
}
class Address_TrialUser extends Address implements NoAuthenticate {
  //
  public $addressId;
  public $tableCode;
  public $tableId;
  public $type;
  public $addr1;
  public $addr2;
  public $addr3;
  public $city;
  public $state;
  public $zip;
  public $country;
  public $phone1;
  public $phone1Type;
  public $phone2;
  public $phone2Type;
  public $phone3;
  public $phone3Type;
  public $email1;
  public $email2;
  public $name;
  //
  static function create($reg, $ugid) {
    $rec = static::from($reg, $ugid);
    $rec->save();
    return $rec;
  }
  static function from($reg, $ugid) {
    $me = new static();
    $me->tableCode = static::TABLE_USER_GROUPS;
    $me->tableId = $ugid;
    $me->type = static::TYPE_SHIP;
    $me->state = $reg->licenseState;
    $me->phone1 = $reg->phoneNum;
    $me->phone1Type = static::PHONE_TYPE_PRIMARY;
    return $me;
  }
}
/**
 * Exceptions
 */
class RegException extends DisplayableException {}
