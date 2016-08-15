<?php
require_once 'php/data/rec/sql/_SqlRec.php';
require_once 'php/data/rec/sql/_AddressRec.php';
/**
 * User Group Record
 */
class UserGroup extends SqlRec {
  //
  public $userGroupId;
  public $name;
  public $usageLevel;
  public $estTzAdj;
  public /*Address*/ $Address;
  //
  const USAGE_LEVEL_BASIC = '0';
  const USAGE_LEVEL_PREMIUM = '1';
  const USAGE_LEVEL_ERX = '2';
  //
  public function getSqlTable() {
    return 'user_groups';
  }
  //
  /**
   * @param int $ugid
   * @return UserGroup 
   */
  public static function fetch($ugid) {
    return parent::fetch($ugid, 'UserGroup');
  }
  /**
   * @param int $ugid
   * @return UserGroup 
   */
  public static function fetchWithAddress($ugid) {
    $rec = self::fetch($ugid);
    $rec->Address = UserGroupAddress::fetch($ugid);
  }
}
