<?php
require_once "php/data/db/Address.php";
require_once "php/dao/LookupDao.php";
require_once "php/dao/DataDao.php";
require_once "php/dao/TemplateReaderDao.php";
require_once "php/data/db/Client.php";
require_once "php/data/db/Par.php";

/*
 * Used by front-end to generate combos
 * 
 * Example: <?=renderCombo("state", $form->states) ?>
 * 
 * However, these are simple associated lists and may be used
 * for other purposes (e.g. displaying code descriptions in data tables)
 */
class CommonCombos {

	public static function sexes() {
		$c = array();
    $c[""] = "";
		$c[Client0::FEMALE] = "female";
		$c[Client0::MALE] = "male";
		return $c;
	}
	
	public static function states() {
		$states = Array("AK","AL","AR","AZ","CA","CO","CT","DC","DE","FL","GA","HI","IA","ID","IL","IN","KS","KY","LA","MA","MD","ME","MI","MN","MO","MS","MT","NC","ND","NE","NH","NJ","NM","NY","NV","OH","OK","OR","PA","RI","SC","SD","TN","TX","UT","VA","VT","WA","WI","WV","WY");
		$c = array();
    $c[""] = "";
		for ($i = 0; $i < sizeof($states); $i++) {
			$c[$states[$i]] = $states[$i];
		}
		return $c;
	}
	
	public static function timeouts() {
	  return array(
	    '10' => '10 minutes',
	    '20' => '20 minutes',
	    '30' => '30 minutes',
	    '40' => '40 minutes',
	    '50' => '50 minutes',
	    '60' => '60 minutes');
	}
	
	public static function timezones() {
	  $c = array();
	  $c[""] = "";
    $c["0"] = "US/Eastern";
    $c["-1"] = "US/Central";
    $c["-2"] = "US/Mountain";
    $c["-3"] = "US/Pacific";
    $c["-4"] = "US/Alaska";
    $c["-5"] = "US/Hawaii";
    return $c;
	}
	
	public static function timezonesByState() {
	  $c = array();
    $c["AK"] = "-4";
    $c["AL"] = "-1";
    $c["AR"] = "-1";
    $c["AZ"] = "-2";
    $c["CA"] = "-3";
    $c["CO"] = "-2";
    $c["CT"] = "0";
    $c["DC"] = "0";
    $c["DE"] = "0";
    $c["FL"] = "0";
    $c["GA"] = "0";
    $c["HI"] = "-5";
    $c["IA"] = "-1";
    $c["ID"] = "-2";
    $c["IL"] = "-1";
    $c["IN"] = "0";
    $c["KS"] = "-1";
    $c["KY"] = "0";
    $c["LA"] = "-1";
    $c["MA"] = "0";
    $c["MD"] = "0";
    $c["ME"] = "0";
    $c["MI"] = "0";
    $c["MN"] = "-1";
    $c["MO"] = "-2";
    $c["MS"] = "-1";
    $c["MT"] = "-2";
    $c["NC"] = "0";
    $c["ND"] = "-1";
    $c["NE"] = "-1";
    $c["NH"] = "0";
    $c["NJ"] = "0";
    $c["NM"] = "-2";
    $c["NY"] = "0";
    $c["NV"] = "-3";
    $c["OH"] = "0";
    $c["OK"] = "-1";
    $c["OR"] = "-3";
    $c["PA"] = "0";
    $c["RI"] = "0";
    $c["SC"] = "0";
    $c["SD"] = "-1";
    $c["TN"] = "0";
    $c["TX"] = "-1";
    $c["UT"] = "-2";
    $c["VA"] = "0";
    $c["VT"] = "0";
    $c["WA"] = "-3";
    $c["WI"] = "-1";
    $c["WV"] = "0";
    $c["WY"] = "-2";
    if (isset($c[""])) {
      echo $c[""];
    }
	  return $c;
	}
	
  public static function phoneTypes() {
    $c = array();
    $c[""] = "";
    $c[Address0::PHONE_TYPE_RES] = "primary";
    $c[Address0::PHONE_TYPE_WORK] = "work";
    $c[Address0::PHONE_TYPE_CELL] = "cell";
    $c[Address0::PHONE_TYPE_FAX] = "fax";
    $c[Address0::PHONE_TYPE_EMER] = "emergency";
    $c[Address0::PHONE_TYPE_OTHER] = "other";
    return $c; 
  }	
  
  public static function schedStatus() {
    $schedStatus = LookupDao::getActiveSchedStatus();
    $c = array();
    $c[""] = "(No Status)";
    foreach ($schedStatus as $a) {
      $c[$a->_instance] = $a->name;
    }
    return $c;
  } 
  public static function getStatusDesc($status) {
    $schedStatus = CommonCombos::schedStatus();
    if ($status != "" && isset($schedStatus[$status])) {
      return $schedStatus[$status];
    } else {
      return "[No status selected]";
    }
  }
  
  // UI uses minutes embedded in description to default duration
  // Returned as sorted list
  public static function apptTypes() {
    $apptTypes = LookupDao::getActiveApptTypes();
    $c = array();
    $c[""] = "(No Type)";
    foreach ($apptTypes as $a) {
      $c[$a->_instance] = $a->name;
    }
    return $c;
  }
  public static function getApptTypeDesc($type) {
    $apptTypes = CommonCombos::apptTypes();
    if ($type != "" && isset($apptTypes[$type])) {
      return $apptTypes[$type];
    } else {
      return "[No type selected]";
    }
  }
  
  // Data table names
  public static function outDataTables() {
    $tables = DataDao::getOutDataTables();
    $c = array();
    $c[""] = "";
    foreach ($tables as $name => $def) {
      $c[$name] = $name;
    }
    return $c;
  }
  
  public static function inDataTables() {
    $tables = DataDao::getInDataTables();
    $c = array();
    $c[""] = "";
    foreach ($tables as $name) {
      $c[$name] = $name;
    }
    $c["vitals-most-recent"] = "vitals-most-recent";
    return $c;
  }
  
  public static function inDataTypes() {
    $c = array();
    $c[""] = "";
    $c[Par::TYPE_AUTO_ADD] = "Auto-add if record(s) exist";
    $c[Par::TYPE_AUTO_ADD_IF_COND_FIELDS_NOT_NULL] = "Auto-add if record(s) exist and cond field(s) not null";
    $c[Par::TYPE_ON_DEMAND] = "On demand";
    return $c;
  }
  
  // Doctors in practice
  public static function docs() {
    global $login;
    $users = UserDao::getDocsOfGroup($login->userGroupId);
    $c = array();
    foreach ($users as $u) {
      $c[$u->id] = $u->name;
    }
    return $c;
  }
  
  // All users in practice
  public static function usersInGroup($meOnly) {
    global $login;
    $c = array();
    if ($meOnly) {
      $c[$login->userId] = $login->User->name;
    } else {
      $c["-1"] = "[Everyone]";
      $users = UserDao::getUsersOfMyGroup();
      foreach ($users as $u) {
        $c[$u->id] = $u->name;
      }
    }
    return $c;
  }
  
  // Templates authorized for user
  public static function myTemplates() {
    $templates = TemplateReaderDao::getMyTemplates();
    $c = array();
    foreach ($templates as $t) {
      $c[$t->id] = $t->name;
    }
    return $c;
  }

  // Templates authorized for user
  // in form of {"noPresets":boolean, "combo":array}
  public static function myTemplatePresets() {
    $presets = SessionDao::getPresets();
    $c = array();
    $o = array();
    if (sizeof($presets)) {
      foreach ($presets as $p) {
        $c[$p->id] = $p->name;
      }
      $o["noPresets"] = false;
    } else {
      $c[""] = "(None)";
      $o["noPresets"] = true;
    }
    $o["combo"] = $c;
    return $o;
  }
}
?>