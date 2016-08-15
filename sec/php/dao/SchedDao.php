<?php
p_i('SchedDao');
require_once 'php/c/scheduling/Scheduling.php';
require_once 'php/data/rec/sql/Clients.php';
require_once 'php/data/rec/cryptastic.php';

// Scheduling exceptions
class ClientUidExistsException extends Exception {}

/*
 * Scheduling and clients
 */
class SchedDao {

  const SQL_SELECT_CLIENT = "SELECT c.client_id, user_group_id, uid, last_name, first_name, sex, birth, img, date_created, active, cdata1, cdata2, cdata3, trial, living_will, poa, gest_weeks, middle_name, notes, active, deceased FROM clients c ";   
  const SQL_SELECT_CLIENT_ADDRESS = "SELECT c.client_id, user_group_id, uid, last_name, first_name, sex, birth, img, date_created, active, cdata1, cdata2, cdata3, cdata4, cdata5, cdata6, cdata7, middle_name, notes, active, deceased, address_id, table_code, table_id, a.type, addr1, addr2, addr3, city, state, zip, country, phone1, phone1_type, phone2, phone2_type, phone3, phone3_type, email1, email2, name FROM clients c LEFT OUTER JOIN addresses a ON (a.table_code = 'C' AND a.table_id = c.client_id AND a.type = 0) ";   
  const SQL_SELECT_SCHED = "SELECT sched_id, user_id, user_group_id, client_id, date, time_start, duration, closed, status, comment, type, sched_event_id FROM scheds ";
  const SQL_SELECT_SESSION_STUB = "SELECT s.session_id, s.template_id, c.last_name, c.first_name, c.sex, s.client_id, s.sched_id, s.date_created, s.date_updated, s.closed, s.closed_by, s.send_to, s.date_closed, s.date_service, u2.name AS created_by_name, s.title, s.standard, u3.name AS updated_by_name, u4.name AS send_to_name FROM sessions s INNER JOIN clients c ON (c.client_id=s.client_id) LEFT OUTER JOIN users u2 ON (u2.user_id=S.created_by) LEFT OUTER JOIN users u3 ON (u3.user_id=S.updated_by) LEFT OUTER JOIN users u4 ON (u4.user_id=S.send_to) ";
  const SQL_SELECT_QUICK_SESSION_STUB = "SELECT s.session_id, s.template_id, c.last_name, c.first_name, c.sex, s.client_id, s.sched_id, s.date_created, s.date_updated, s.closed, s.closed_by, s.send_to, s.date_closed, s.date_service, s.title, s.standard, u4.name AS send_to_name FROM sessions s INNER JOIN clients c ON (c.client_id=s.client_id) LEFT OUTER JOIN users u4 ON (u4.user_id=S.send_to) ";
  
  const USAGE_SESSION = 0;
  const USAGE_DOWNLOADS = 1;
  const USAGE_COPY = 2;
  
  // Returns JClients object
  // TODO unescape name?
  public static function searchClients($uid, $lastName, $firstName, $address, $phone, $email, $custom) {
    global $login;
    if ($uid == "" && $lastName == "" && $firstName == "" && $address == "" && $phone == "" && $email == "" && $custom == "") {
      $sql = SchedDao::SQL_SELECT_CLIENT_ADDRESS;
      $sql .= " LEFT JOIN client_updates cu ON c.client_id=cu.client_id WHERE c.user_group_id=" . $login->userGroupId;
      $sql .= " ORDER BY date DESC LIMIT 16";  // no criteria specified, just return most recent updates 
    } else {
      $sql = SchedDao::SQL_SELECT_CLIENT_ADDRESS . "WHERE c.user_group_id=" . $login->userGroupId;
      if ($uid != "") {
        $sql .= " AND c.uid LIKE " . quote($uid . "%");
      } else { 
        if ($lastName != "") {
          $sql .= " AND c.last_name LIKE " . quote("%" . $lastName . "%");
        }
        if ($firstName != "") {
          $sql .= " AND c.first_name LIKE " . quote("%" . $firstName . "%");
        }
      }
      if ($address != "") {
        $sql .= " AND (a.addr1 LIKE " . quote("%" . $address . "%") . " OR a.addr2 LIKE " . quote("%" . $address . "%") . ")";
      }
      if ($phone != "") {
        $sql .= " AND (a.phone1 LIKE " . quote("%" . $phone . "%") . " OR a.phone2 LIKE " . quote("%" . $phone . "%") . ")";
      }
      if ($email != "") {
        $sql .= " AND a.email1 LIKE " . quote("%" . $email . "%");
      }
      if ($custom != "") {
        $sql .= " AND (c.cdata1 LIKE " . quote("%" . $custom . "%") . " OR c.cdata2 LIKE " . quote("%" . $custom . "%") . " OR c.cdata3 LIKE " . quote("%" . $custom . "%") . ")";
      }
      $sql .= " ORDER BY last_name, first_name";
    }
    $res = query($sql);
    $dtos = array();
    while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
      $dto = SchedDao::buildJClient($row);
      $dtos[] = $dto;
    }
    return new JClients($dtos);
  }
  
  // Get specific client without address
  public static function getClient($clientId) {
    $row = fetch(
        "SELECT client_id, user_group_id, uid, last_name, first_name, sex, birth, img, date_created, active, cdata1, cdata2, cdata3, cdata4, cdata5, cdata6, cdata7, middle_name, notes, phone1, active, deceased" .
        " FROM clients c LEFT JOIN addresses a ON a.table_code='C' AND a.table_id=$clientId AND a.type=0" .
        " WHERE c.client_id = ". $clientId);
    $client = SchedDao::buildClient($row);
    if ($client != null) {
      LoginDao::authenticateUserGroupId($client->userGroupId);
      $client->phone1 = $row['phone1'];
    }
    return $client;
  }
  
  // Return a page of clients according to paging specs
  public static function getClients($paging) {
    global $login;
    /*
    $sql = "SELECT c.client_id, c.user_group_id, c.uid, c.last_name, c.first_name, c.sex, c.birth, c.img, c.date_created, c.active, c.cdata1, c.cdata2, c.cdata3, c.cdata4, c.cdata5, c.cdata6, c.cdata7, c.middle_name, c.notes, c.deceased, c.active, cu.date, cu.type, cu.id, cu.descr"
        . " FROM clients c LEFT JOIN client_updates cu ON c.client_id=cu.client_id"
        . " WHERE user_group_id=" . $login->userGroupId . " AND " . $paging->buildSql();
        */
    $sql = "SELECT c.client_id, c.user_group_id, c.uid, c.last_name, c.first_name, c.sex, c.birth, c.img, c.date_created, c.active, c.cdata1, c.cdata2, c.cdata3, c.cdata4, c.cdata5, c.cdata6, c.cdata7, c.middle_name, c.notes, c.deceased, c.active, cu.date, cu.type, cu.id, cu.descr"
        . " FROM clients c LEFT JOIN client_updates cu ON c.client_id=cu.client_id"
        . " WHERE user_group_id=" . $login->userGroupId . " AND " . $paging->buildSql();
    $res = query($sql);
    $clients = array();
    while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
      $client = SchedDao::buildJClient($row, false);
      $client->events = array();
      $client->events[] = SchedDao::buildJClientEventFromUpdate($row);
      $clients[] = $client;
    }
    return $clients;
  }

  // Return date range set by user's profile which includes $date 
  public static function getSchedPageForWeek($date, $userId) {
    $user = UserDao::getUser($userId, false);
    $profile = SchedDao::getSchedProfile($userId);
    $schedPage = new SchedPage($profile);
    $ts = SchedDao::getWeekStart($date, $profile);
    for ($i = 0; $i < $profile->weekLength; $i++) {
      $schedPage->days[] = SchedDao::getSchedDay(date("Y-m-d", $ts), $user);
      $ts = strtotime("1 day", $ts);
    }
    return $schedPage;
  }
  
  public static function getSchedPageForDay($date, $userId) {
    $user = UserDao::getUser($userId, false);
    $profile = SchedDao::getSchedProfile($userId);
    $schedPage = new SchedPage($profile);
    $schedPage->days[] = SchedDao::getSchedDay($date, $user);
    return $schedPage;
  }

  // Return a day's schedule for a particular user
  public static function getSchedDay($date, $user) {

    // Slot defaults
    $schedDay = new SchedDay($date);
    /*
    $sql = SchedDao::SQL_SELECT_SCHED .
        " WHERE user_id=" . $user->id . " AND date=" . quote($date) .
        " ORDER BY time_start, client_id";
    $res = query($sql);    
    $scheds = array();
    while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
      $sched = SchedDao::buildSched($row);
      $sched->client = SchedDao::getClient($sched->clientId);
      $scheds[] = $sched;
    }
    */
    logit_r('here getschedday');
    $scheds = Scheduling::getAppts($date, $user->id);
    $schedUser = new SchedUser($user->id, $user->uid, $user->name);
    $schedUser->scheds = $scheds;
    $schedDay->users[] = $schedUser;
    return $schedDay;
  }
  
  public static function getJSchedStubs($clientId) {
    // assumes client already authenticated
    $sql = SchedDao::SQL_SELECT_SCHED . "WHERE client_id=" . $clientId; 
    $res = query($sql);
    $stubs = array();
    while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
      $sched = SchedDao::buildJSched($row);
      $stub = new JSchedStub($sched);
      $stubs[$sched->id] = $stub;
    }
    return $stubs;
  }
  
  public static function getJSched($schedId, $withClient = true) {
    $sched = SchedDao::buildJSched(fetch(
        SchedDao::SQL_SELECT_SCHED . "WHERE sched_id=". $schedId));
    if ($sched != null) {
      LoginDao::authenticateUserGroupId($sched->userGroupId);    
      if ($sched->schedEventId) {
        if ($sched->clientId) {
          $sched->client = Client::fetchWithDemo($sched->clientId);
          $sched->client->Appts = Scheduling::getHistory($sched->clientId);
        } else {
          $sched->client = null;
        } 
        $sched->schedEvent = SchedDao::getJSchedEvent($sched->schedEventId);
      } else {
        if ($withClient) {
          //$sched->client = SchedDao::getJClientWithEvents($sched->clientId);
          $sched->client = Client::fetchWithDemo($sched->clientId);
          $sched->client->Appts = Scheduling::getHistory($sched->clientId);
          //$sql = "SELECT a.time, a.user_id, u.name FROM audits a JOIN users u ON a.user_id=u.user_id WHERE entity='K' AND action='C' AND entity_id=" . $sched->id;
          //$row = fetch($sql);
          //$sched->_by = formatInformalTime($row['time']) . ' by ' . $row['name']; 
        }
      }
      //$sched->sessions = SchedDao::getJSessionStubsForSched($schedId);
    }
    return $sched;
  }
  
  public static function getJSchedEvent($id) {
    return SchedDao::buildJSchedEvent(fetch(
        "SELECT sched_event_id, rp_type, rp_every, rp_until, rp_on, rp_by, comment FROM sched_events WHERE sched_event_id=" . $id));
  }
  
  // Get JClient with JAddress and JICards
  public static function getJClient($clientId, $fromApi = false) {
    $client = SchedDao::buildJClient(fetch(
        SchedDao::SQL_SELECT_CLIENT . "WHERE client_id=" . $clientId), false);
    if ($client != null && ! $fromApi) {
      LoginDao::authenticateUserGroupId($client->userGroupId);
    }
    if ($client) {
      $client->shipAddress = SchedDao::getAddressOrBlank(Address0::TABLE_CLIENTS, $clientId, Address0::ADDRESS_TYPE_SHIP);
      $client->emerAddress = SchedDao::getAddressOrBlank(Address0::TABLE_CLIENTS, $clientId, Address0::ADDRESS_TYPE_EMER);
      $client->pharmAddress = SchedDao::getAddressOrBlank(Address0::TABLE_CLIENTS, $clientId, Address0::ADDRESS_TYPE_RX);
      $client->spouseAddress = SchedDao::getAddressOrBlank(Address0::TABLE_CLIENTS, $clientId, Address0::ADDRESS_TYPE_SPOUSE);
      $client->fatherAddress = SchedDao::getAddressOrBlank(Address0::TABLE_CLIENTS, $clientId, Address0::ADDRESS_TYPE_FATHER);
      $client->motherAddress = SchedDao::getAddressOrBlank(Address0::TABLE_CLIENTS, $clientId, Address0::ADDRESS_TYPE_MOTHER);
      $client->icards = SchedDao::getJICards($clientId);
    }
    return $client;
  }
  
  // Returns requested address, or a blank one
  public static function getAddressOrBlank($table, $tableId, $type) {
    $a = SchedDao::buildJAddress(fetch("SELECT address_id, table_code, table_id, type, addr1, addr2, addr3, city, state, zip, country, phone1, phone1_type, phone2, phone2_type, phone3, phone3_type, email1, email2, name FROM addresses WHERE table_code=" . quote($table) . " AND table_id=" . $tableId . " AND type=" . $type));
    if ($a == null) {
      return new JAddress(null, $table, $tableId, $type);  
    } else {
      return $a;
    }
  }
  
  public static function getAddressById($addressId) {
    if ($addressId == null) return null;
    return SchedDao::buildJAddress(fetch("SELECT address_id, table_code, table_id, type, addr1, addr2, addr3, city, state, zip, country, phone1, phone1_type, phone2, phone2_type, phone3, phone3_type, email1, email2, name FROM addresses WHERE address_id=" . $addressId));
  }
  
  private static function getJICards($clientId) {
    $res = query("SELECT " . JICard::SQL_FIELDS . " FROM client_icards WHERE client_id=" . $clientId . " ORDER BY seq");
    $cards = array();
    while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
      $cards[] = SchedDao::buildJICard($row);
    }
    return $cards;
  }

  // Deprecated
  public static function getJClientHistory($clientId) {
    $sessions = SchedDao::getJSessionStubsForClient($clientId);
    $appts = SchedDao::getJSchedStubs($clientId);
    $msgs = MsgDao::getThreadsForClient($clientId);
    //$msgs = MsgThread::fetchAllByClient($clientId);
    return new JClientHistory($sessions, $appts, $msgs);
  }
  
  // Deprecated: Get JClient with JClientEvents (history of Scheds and Sessions) 
  public static function getJClientWithEvents($clientId) {
    $client = SchedDao::getJClient($clientId);
    $client->events = SchedDao::getJClientEvents($clientId);
    return $client;
  }
  private static function getJClientEvents($clientId) {
    $sql = "SELECT s.client_id, s.date_service AS activity_date, s.date_updated, s.closed, s.date_closed, 'S' as type, session_id as id, t.template_id as tid, t.name, null as comment, s.title, s.standard FROM sessions s INNER JOIN templates t ON s.template_id=t.template_id where client_id=" . $clientId
        . " UNION SELECT client_id, timestamp AS activity_date, null as date_updated, '0' as closed, null as date_closed, 'K' AS type, sched_id as id, '' as tid, type as name, status as comment, null as title, null as standard FROM scheds where client_id=" . $clientId
        . " ORDER BY MID(activity_date, 1, 10) DESC, type, date_updated DESC";
    $res = query($sql);
    $events = array();
    while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
      $events[] = SchedDao::buildJClientEvent($row);
    }
    return $events;
  }
  
  // Returns associated array {"tid1":"tname1","tid2":"tname2",...}
  public static function getTemplateArray() {
    $res = query("SELECT template_id, name FROM templates");
    $a = array();
    while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
      $a[$row["template_id"]] = $row["name"];
    }
    return $a;
  }

  public static function getQuickJSessionStubs($where = "") {
    $templates = SchedDao::getTemplateArray();
    $sql = SchedDao::SQL_SELECT_QUICK_SESSION_STUB . $where;
    $res = query($sql);    
    $stubs = array();
    while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
      $stub = SchedDao::buildQuickJSessionStub($row, $templates[$row["template_id"]]);
      $stubs[] = $stub;
    }
    return $stubs;
  }
  
  // Get session stubs
  public static function getJSessionStubs($where = "", $includeSched = false, $assocById = false) {
    //$templates = SchedDao::getTemplateArray();
    $sql = SchedDao::SQL_SELECT_SESSION_STUB . $where;
    $res = query($sql);    
    $stubs = array();
    while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
      $stub = SchedDao::buildJSessionStub($row);
      if ($includeSched) {
        $stub->sched = SchedDao::getSched($stub->schedId);
      }
      if ($assocById) {
        $stubs[$stub->id] = $stub;
      } else {
        $stubs[] = $stub;
      }
    }
    return $stubs;
  }  
  /*
   * Returns [JSessionStub,..]
   */
  public static function getJSessionStubsForClient($clientId) {
    $where = " WHERE s.client_id=" . $clientId 
        . " ORDER BY date_service DESC, date_updated DESC";
    return SchedDao::getJSessionStubs($where, false, true);
  }
  public static function getJSessionStubsForSched($schedId) {
    LoginDao::authenticateSchedId($schedId);
    $where = " WHERE s.sched_id=" . $schedId 
        . " ORDER BY date_updated DESC";
    return SchedDao::getJSessionStubs($where);
  }
  
  public static function getSchedProfile($userId) {
    return LookupDao::getSchedProfile($userId);
  }
 
  // Add new schedule
  // Returns newly created schedId
  public static function addSched($sched) {
    global $login;
    //LoginDao::authenticateUserGroupId($sched->userGroupId);
    //$clientId = $sched->clientId;
    //if ($clientId) {
    //  LoginDao::authenticateClientId($sched->clientId);
    //}
    $eventId = null;
    $appt = ApptEdit::from($sched, $login->userGroupId);
    if (isset($sched->schedEvent)) {
      $event = SchedEvent::from($sched->schedEvent);
      $event->save();
      $eventId = $event->schedEventId;
      $sched->schedEvent->id = $eventId;
    }
    $sched->schedEventId = $eventId;
    $appt->schedEventId = $eventId;
    $appt->save();
    $sched->id = $appt->schedId;
    if (isSet($sched->schedEvent)) 
      $sched->schedEvent->maxRepeatDate = SchedDao::repeatSched($sched);
    return $sched;
  }
  
  private static function insertSchedSql($sched, $valuesOnly = false) {
    $d = dateToString($sched->date);
    $sql = ($valuesOnly) ? "" : "INSERT INTO scheds VALUES";
    $sql .= "(NULL";
    $sql .= ", " . $sched->userId;
    $sql .= ", " . $sched->userGroupId;
    $sql .= ", " . $sched->clientId;
    $sql .= ", " . quote($d);
    $sql .= ", " . $sched->timeStart;
    $sql .= ", " . $sched->duration;
    $sql .= ", " . toBoolInt(false);  // closed
    $sql .= ", " . quote($sched->status);
    $sql .= ", " . encr($sched->comment, true);
    $sql .= ", " . quote($sched->type);
    $sql .= ", " . quote($d . " " . SchedDao::div($sched->timeStart, 100) . ":" . ($sched->timeStart % 100));
    $sql .= ", " . quote($sched->schedEventId);
    $sql .= ")";
    return $sql;
  }
  
  // Returns last date repeated if max exceeded, else null
  private static function repeatSched($sched) {
    $sql = "DELETE FROM scheds WHERE sched_id<>" . $sched->id . " AND sched_event_id=" . $sched->schedEvent->id;
    query($sql);
    $max = 300;
    $dates = SchedDao::buildRepeatSchedDates($sched, $max);
    $maxLastDate = null;
    if (count($dates) == $max) {
      $maxLastDate = $dates[count($dates) - 1];
    }
    $values = array();
    for ($i = 0; $i < count($dates); $i++) {
      $sched->date = $dates[$i];
      $values[] = SchedDao::insertSchedSql($sched, true); 
    }
    $values = array_chunk($values, 20);  // chunk insert calls into batches
    for ($i = 0; $i < count($values); $i++) {
      $sql = "INSERT INTO scheds (sched_id,user_id,user_group_id,client_id,date,time_start,duration,closed,status,comment,type,timestamp,sched_event_id) VALUES" . implode(",", $values[$i]);
      query($sql);
      logit_r($sql, 'repeatsched'); 
    }
    return $maxLastDate;
  }

  private static function buildRepeatSchedDates($sched, $max = 100) {
    $dates = array();
    $type = $sched->schedEvent->type;
    if ($type != JSchedEvent::TYPE_NONE) {
      $date = strtotime($sched->date);
      $until = strtotime($sched->schedEvent->until);
      $dow = null;
      if ($type == JSchedEvent::TYPE_WEEK) {
        $sched->schedEvent->getOnDowArray();
      }
      for ($i = 0; $i < $max; $i++) {
        $date = SchedDao::nextRepeatDate($date, $sched->schedEvent);
        if ($date == null || $date > $until) {
          break;
        }
        $dates[] = date("d-M-Y", $date);
      }
    }
    return $dates;
  }
  private static function nextRepeatDate($dt, $event) {
    //logit("nextRepeatDate, dt=" . date("l, d-M-Y", $dt));
    switch ($event->type) {
      case JSchedEvent::TYPE_DAY:
        $dt = mktime(0, 0, 0, date("n", $dt), date("j", $dt) + $event->every, date("Y", $dt)); 
        //logit("dt0=" . date("l, d-M-Y", $dt));
        return $dt;
      case JSchedEvent::TYPE_WEEK:
        $dow = date("w", $dt);
        for ($i = 0; $i < 7; $i++) {
          $dt = mktime(0, 0, 0, date("n", $dt), date("j", $dt) + 1, date("Y", $dt));  // next day
//          logit("dt1=" . date("l, d-M-Y", $dt));
          $dow = ($dow + 1) % 7;
          if ($dow == JSchedEvent::ON_SUN) {
            $dt = mktime(0, 0, 0, date("n", $dt), date("j", $dt) + 7 * ($event->every - 1), date("Y", $dt));  // skip (every-1) weeks
//            logit("dt2=" . date("l, d-M-Y", $dt));
          }
          if ($event->dowArray[$dow]) {
            return $dt;
          }
        }
        return null;
      case JSchedEvent::TYPE_MONTH:
        if ($event->by == JSchedEvent::BY_DATE) {
          
          // By date ("15th")
          $nextm = (date("n", $dt) + $event->every - 1) % 12 + 1;  // next month expected  
          $dt = mktime(0, 0, 0, date("n", $dt) + $event->every, date("j", $dt), date("Y", $dt));  
//          logit("dt3=" . date("l, d-M-Y", $dt));
          if (date("n", $dt) > $nextm) {  
            $dt = mktime(0, 0, 0, date("n", $dt), 0, date("Y", $dt));  // went too far, use last day of month instead
//            logit("dt4=" . date("l, d-M-Y", $dt)); 
          }
          return $dt;
        } else {
          
          // By month day ("4th Wednesday")
          $week = ceil(date("j", $dt) / 7);
          $dow = date("w", $dt);
          if ($week == 5 || $event->by == JSchedEvent::BY_DAY_OF_LAST_WEEK) {
            $dt = mktime(0, 0, 0, date("n", $dt) + $event->every + 1, 0, date("Y", $dt));  // last day of next month
//            logit("dt5=" . date("l, d-M-Y", $dt));
            for ($i = 0; $i < 7; $i++) {
              if (date("w", $dt) == $dow) {
                return $dt;
              }
              $dt = mktime(0, 0, 0, date("n", $dt), date("j", $dt) - 1, date("Y", $dt));  // prev day
//              logit("dt6=" . date("l, d-M-Y", $dt));
            }
            return null;
          }
          $dt = mktime(0, 0, 0, date("n", $dt) + $event->every, 1, date("Y", $dt));  // next month
//          logit("dt6=" . date("l, d-M-Y", $dt));
          $dt = mktime(0, 0, 0, date("n", $dt), (8 + ($dow - date("w", $dt))) % 7, date("Y", $dt));  // first dow of month
//          logit("dt6a=" . date("l, d-M-Y", $dt));
//          logit("dt7=" . date("l, d-M-Y", $dt));
          for ($i = 0; $i <= 5; $i++) {
            if (ceil(date("j", $dt) / 7) == $week) {
              return $dt;
            }
            $dt = mktime(0, 0, 0, date("n", $dt), date("j", $dt) + 7, date("Y", $dt));  // skip week   
//            logit("dt8=" . date("l, d-M-Y", $dt));
          }
        }
        return null;
      case JSchedEvent::TYPE_YEAR:
        $dt = mktime(0, 0, 0, date("n", $dt), date("j", $dt), date("Y", $dt) + $event->every);
//        logit("dt9=" . date("l, d-M-Y", $dt));
        return $dt;
    }
  }
  
  private static function authenticateAddress($address) {
    if ($address->tableCode == Address0::TABLE_CLIENTS) {
      LoginDao::authenticateClientId($address->tableId);
    } else if ($address->tableCode == Address0::TABLE_USERS) {
      LoginDao::authenticateUserId($address->tableId);
    } else if ($address->tableCode == Address0::TABLE_USER_GROUPS) {
      LoginDao::authenticateUserGroupId($address->tableId);
    } else {
      throw new InvalidDataException("Unknown tableCode " . $address->tableCode ." in address record.");
    }
  }
  
  /**
   * Add client from API
   *
   * @param JClient $client
   * @return int (newly created clientId)
   */
  public static function addClient($client) {
    
    $sql = "INSERT INTO clients VALUES(NULL";
    $sql .= ", " . $client->userGroupId;
    $sql .= ", " . quote($client->uid);
    $sql .= ", " . quote($client->lastName, true);
    $sql .= ", " . quote($client->firstName, true);
    $sql .= ", " . quote($client->sex);
    $sql .= ", " . quoteDate($client->birth);
    $sql .= ", " . quote($client->img);
    $sql .= ", " . now();  // date_created
    $sql .= ", " . toBoolInt($client->active);
    $sql .= ", " . quote($client->cdata1, true);
    $sql .= ", " . quote($client->cdata2, true);
    $sql .= ", " . quote($client->cdata3, true);
    $sql .= ", " . quote($client->cdata4, true);
    $sql .= ", " . quote($client->cdata5, true);
    $sql .= ", " . quote($client->cdata6, true);
    $sql .= ", NULL";  
    $sql .= ", NULL";
    $sql .= ", " . quote($client->notes, true);
    $sql .= ", NULL";  // date_updated
    $sql .= ")";
    $id = insert($sql);
    
    return $id;
  }
  
  // Delete schedule
  public static function deleteSched_old($schedId, $includeRepeats = false) {
    $sched = SchedDao::getJSched($schedId, false);  // this call authenticates user group
    if ($sched->schedEvent) {
      $eid = $sched->schedEvent->id;
      if ($includeRepeats) {
        $event = SchedEvent::fetch($eid);
        SchedEvent::delete($event);
        query("DELETE FROM scheds WHERE sched_event_id=" . $eid);
      } else { 
        query("DELETE FROM scheds WHERE sched_id=" . $schedId);
      }
    } else {
      $appt = Appt::fetch($schedId);
      Appt::delete($appt);
      //query("DELETE FROM scheds WHERE sched_id=" . $schedId);
    }
    // AuditDao::log($sched->clientId, AuditDao::ENTITY_SCHED, $schedId, AuditDao::ACTION_DELETE);    
    // SchedDao::saveClientUpdate(new ClientUpdate($sched->clientId, ClientUpdate::TYPE_APPT_DELETED, null, null));
  }
  
    // Delete client
  public static function deleteClient($clientId) {
    LoginDao::authenticateClientId($clientId);
    return query("DELETE FROM clients WHERE client_id=" . $clientId);
  }
  
  // Returns JClient for supplied user group and uid
  public static function getJClientByUid($uid, $userGroupId) {
    LoginDao::authenticateUserGroupId($userGroupId);
    $sql = SchedDao::SQL_SELECT_CLIENT_ADDRESS . " WHERE uid=" . quote($uid) . " AND user_group_id=" . $userGroupId;
    return SchedDao::buildJClient(fetch($sql));
  }

  // Reassign schedule's client
  // Returns resource
  public static function reassignSched($schedId, $clientId) {
    LoginDao::authenticateSchedId($schedId);
    LoginDao::authenticateClientId($clientId);
    $sql = "UPDATE scheds SET ";
    $sql .= "client_id=" . quote($clientId);
    $sql .= " WHERE sched_id=" . $schedId;
    return query($sql);
  }

  // Update schedule time
  // Returns resource
  public static function updateSched($sched) {
    //LoginDao::authenticateSchedId($sched->id);
    /*
    $d = dateToString($sched->date);
    $sql = "UPDATE scheds SET ";
    $sql .= "date=" . quote($d);
    $sql .= ", time_start=" . quote($sched->timeStart);
    $sql .= ", timestamp=" . quote($d . " " . SchedDao::div($sched->timeStart, 100) . ":" . ($sched->timeStart % 100));
    $sql .= ", duration=" . quote($sched->duration);
    $sql .= ", status=" . quote($sched->status);
    $sql .= ", comment=" . quote($sched->comment, true);
    $sql .= ", type=" . quote($sched->type);
    $sql .= " WHERE sched_id=" . $sched->id;
    query($sql);
    */
    $appt = ApptEdit::from($sched);
    $appt->save();
    if (isset($sched->schedEvent)) {
      $event = SchedEvent::from($sched->schedEvent);
      /*
      $sql = "UPDATE sched_events SET ";
      $sql .= "rp_type=" . quote($sched->schedEvent->type);
      $sql .= ", rp_every=" . quote($sched->schedEvent->every);
      $sql .= ", rp_until=" . quoteDate($sched->schedEvent->until);
      $sql .= ", rp_on=" . quote($sched->schedEvent->on);
      $sql .= ", rp_by=" . quote($sched->schedEvent->by);
      $sql .= ", comment=" . quote($sched->schedEvent->comment);
      $sql .= " WHERE sched_event_id=" . $sched->schedEvent->id;
      query($sql);
      */
      $event->save();
    }
    if (isSet($sched->schedEvent)) {
      $sched->schedEvent->maxRepeatDate = SchedDao::repeatSched($sched);
    }
    /*
    if ($sched->clientId && $sched->clientId != 0) {
      AuditDao::log($sched->clientId, AuditDao::ENTITY_SCHED, $sched->id, AuditDao::ACTION_UPDATE, null, CommonCombos::getApptTypeDesc($sched->type));
      // SchedDao::saveClientUpdate(new ClientUpdate($sched->clientId, ClientUpdate::TYPE_APPT_UPDATED, $sched->id, CommonCombos::getApptTypeDesc($sched->type)));
    }
    */
    return $sched;
  }

  public static function div($x, $y) {
    return ($x - ($x % $y)) / $y;
  }
  
  // Close schedule
  public static function closeSched($schedId) {
    LoginDao::authenticateSchedId($schedId);
    return query("UPDATE scheds SET closed=1 WHERE sched_id=" . $schedId);
  }

  // Reactivate schedule
  public static function activateSched($schedId) {
    LoginDao::authenticateSchedId($schedId);
    return query("UPDATE scheds SET closed=0 WHERE sched_id=" . $schedId);
  }

  public static function saveClient($c) {
    if (get($c, "id") == null) {
      return SchedDao::addClientFromJson($c);  
    } else {
      return SchedDao::updateClientFromJson($c);
    }
  }
  
  // deprecated
  public static function updateClientFromJson($c) {
    LoginDao::authenticateClientId($c->clientId);
    $sql = "UPDATE clients SET ";
    $sql .= "uid=" . quote($c->uid);
    $sql .= ", last_name=" . quote($c->lastName, true);
    $sql .= ", first_name=" . quote($c->firstName, true);
    $sql .= ", middle_name=" . quote($c->middleName, true);
    $sql .= ", sex=" . quote($c->sex);
    $sql .= ", birth=" . quoteDate($c->birth);
    $sql .= ", cdata1=" . gquote($c, "cdata1", true);
    $sql .= ", cdata2=" . gquote($c, "cdata2", true);
    $sql .= ", cdata3=" . gquote($c, "cdata3", true);
    $sql .= ", cdata4=" . gquote($c, "cdata4", true);
    $sql .= ", cdata5=" . gquote($c, "cdata5", true);
    $sql .= ", cdata6=" . gquote($c, "cdata6", true);
    $sql .= ", notes=" . gquote($c, "notes", true);
    $sql .= ", date_updated=NULL";
    $sql .= " WHERE client_id=" . $c->clientId;
    query($sql);
   // AuditDao::log($c->clientId, AuditDao::ENTITY_CLIENT, $c->clientId, AuditDao::ACTION_UPDATE);
    // SchedDao::saveClientUpdate(new ClientUpdate($c->clientId, ClientUpdate::TYPE_CLIENT_UPDATED, null, null));
    return SchedDao::getJClient($c->clientId);
  }
  
  //deprecated
  public static function addClientFromJson($c) {
    global $login;
    
    // See if this client's UID already exists for this user group
    $jClient = SchedDao::getJClientByUid($c->uid, $login->userGroupId);
    if ($jClient != null) {
      throw new ClientUidExistsException($jClient->out());
    }
    $sql = "INSERT INTO clients VALUES(NULL";
    $sql .= ", " . $login->userGroupId;
    $sql .= ", " . quote($c->uid);
    $sql .= ", " . quote($c->lastName, true);
    $sql .= ", " . quote($c->firstName, true);
    $sql .= ", " . quote($c->sex);
    $sql .= ", " . quoteDate($c->birth);
    $sql .= ", NULL";  // img
    $sql .= ", " . now();  // date_created
    $sql .= ", 1";  // active
    $sql .= ", NULL";  // cdata1
    $sql .= ", NULL";  // cdata2
    $sql .= ", NULL";  // cdata3
    $sql .= ", NULL";  // cdata4
    $sql .= ", NULL";  // cdata5
    $sql .= ", NULL";  // cdata6
    $sql .= ", NULL";  // cdata7
    $sql .= ", " . quote($c->middleName, true);
    $sql .= ", NULL";  // notes
    $sql .= ", NULL";  // date_updated
    $sql .= ")";
    $id = insert($sql);
    //AuditDao::log($id, AuditDao::ENTITY_CLIENT, $id, AuditDao::ACTION_CREATE);
    // SchedDao::saveClientUpdate(new ClientUpdate($id, ClientUpdate::TYPE_CLIENT_CREATED, null, null));
    return SchedDao::getJClient($id);
  }
  
  /**
   * Update client from API
   * @param Client $client
   * @return JClient
   */
  public static function updateClient($client) {
    $sql = "UPDATE clients SET ";
    $sql .= "uid=" . quote($client->uid);
    $sql .= ", last_name=" . quote($client->lastName, true);
    $sql .= ", first_name=" . quote($client->firstName, true);
    $sql .= ", sex=" . quote($client->sex);
    $sql .= ", birth=" . quoteDate($client->birth);
    $sql .= ", img=" . quote($client->img);
    $sql .= ", active=" . toBoolInt($client->active);
    $sql .= ", cdata1=" . quote($client->cdata1, true);
    $sql .= ", cdata2=" . quote($client->cdata2, true);
    $sql .= ", cdata3=" . quote($client->cdata3, true);
    $sql .= ", cdata4=" . quote($client->cdata4, true);
    $sql .= ", cdata5=" . quote($client->cdata5, true);
    $sql .= ", cdata6=" . quote($client->cdata6, true);
    $sql .= ", middle_name=" . quote($client->middleName, true);
    $sql .= ", notes=" . quote($client->notes, true);
    $sql .= ", date_updated=NULL";
    $sql .= " WHERE client_id=" . $client->clientId;
    query($sql);
    return SchedDao::getJClient($client->clientId, true);
  }
  
  public static function saveClientICard($c) {
    $sql = "INSERT INTO client_icards (client_id, seq, plan_name, subscriber_name, name_on_card, group_no, subscriber_no, date_effective, active) VALUES("
        . $c->clientId . ","
        . $c->seq . ","
        . gquote($c, "planName"). ","
        . gquote($c, "subscriberName"). ","
        . gquote($c, "nameOnCard"). ","
        . gquote($c, "groupNo"). ","
        . gquote($c, "subscriberNo"). ","
        . quoteDate($c->dateEffective) . ","
        . gquote($c, "active")
        . ") ON DUPLICATE KEY UPDATE plan_name=VALUES(plan_name), subscriber_name=VALUES(subscriber_name), name_on_card=VALUES(name_on_card), group_no=VALUES(group_no), subscriber_no=VALUES(subscriber_no), date_effective=VALUES(date_effective), active=VALUES(active)";
    query($sql); 
  } 
  
  // Update client note
  public static function updateClientNotes($cid, $n) {
    LoginDao::authenticateClientId($cid);
    $sql = "UPDATE clients SET notes=" . quote($n) . " WHERE client_id=" . $cid;
    query($sql);
    //AuditDao::log($cid, AuditDao::ENTITY_CLIENT, $cid, AuditDao::ACTION_UPDATE, null, "Client Notes");
  }
  
  public static function saveAddress($a, $cid) {
    if (get($a, "id") == null) {
      SchedDao::addAddress($a, $cid);
    } else {
      SchedDao::updateAddress($a, $cid);
    }
  }

  // Add new address
  // Returns newly created addressId
  public static function addAddress($address, $clientId = null, $fromApi = false) {

    SchedDao::authenticateAddress($address);
    $sql = "INSERT INTO addresses VALUES(NULL";
    $sql .= ", " . quote($address->tableCode);
    $sql .= ", " . quote($address->tableId);
    $sql .= ", " . quote($address->type);
    $sql .= ", " . quote($address->addr1, true);
    $sql .= ", " . quote($address->addr2, true);
    $sql .= ", " . quote($address->addr3, true);
    $sql .= ", " . quote($address->city, true);
    $sql .= ", " . quote($address->state);
    $sql .= ", " . quote($address->zip);
    $sql .= ", " . gquote($address, "country");
    $sql .= ", " . quote($address->phone1, true);
    $sql .= ", " . quote($address->phone1Type);
    $sql .= ", " . quote($address->phone2, true);
    $sql .= ", " . quote($address->phone2Type);
    $sql .= ", " . quote($address->phone3, true);
    $sql .= ", " . quote($address->phone3Type);
    $sql .= ", " . quote($address->email1, true);
    $sql .= ", " . gquote($address, "email2", true);
    $sql .= ", " . quote($address->name, true);
    $sql .= ")";
    $id = insert($sql);
    if (! $fromApi && $clientId) {
      //AuditDao::log($clientId, AuditDao::ENTITY_ADDR_CLIENT, $id, AuditDao::ACTION_CREATE);
    }
    return $id;
  }
  
  // Update address
  public static function updateAddress($a, $clientId = null, $fromApi = false) {
    if (! $fromApi) SchedDao::authenticateAddress($a);
    $sql = "UPDATE addresses SET ";
    $sql .= "addr1=" . gquote($a, "addr1", true);
    $sql .= ", addr2=" . gquote($a, "addr2", true);
    $sql .= ", addr3=" . gquote($a, "addr3", true);
    $sql .= ", city=" . gquote($a, "city", true);
    $sql .= ", state=" . gquote($a, "state");
    $sql .= ", zip=" . gquote($a, "zip");
    $sql .= ", country=" . gquote($a, "country");
    $sql .= ", phone1=" . gquote($a, "phone1", true);
    $sql .= ", phone1_type=" . gquote($a, "phone1Type");
    $sql .= ", phone2=" . gquote($a, "phone2", true);
    $sql .= ", phone2_type=" . gquote($a, "phone2Type");
    $sql .= ", phone3=" . gquote($a, "phone3", true);
    $sql .= ", phone3_type=" . gquote($a, "phone3Type");
    $sql .= ", email1=" . gquote($a, "email1", true);
    $sql .= ", email2=" . gquote($a, "email2", true);
    $sql .= ", name=" . gquote($a, "name", true);
    $sql .= " WHERE address_id=" . $a->id;
    query($sql);
    if (! $fromApi && $clientId) {
      //AuditDao::log($clientId, AuditDao::ENTITY_ADDR_CLIENT, $a->id, AuditDao::ACTION_UPDATE);
    }
  }

  // Return start-of-week timestamp containing $date passed (or first week after, for $dates not part of the work-week)
  private static function getWeekStart($date, $profile) {
    $ts = strtotime($date);
    $dow = date("N", $ts);
    $ds = $profile->dowStart;
    $wl = $profile->weekLength;
    if ($dow < $ds) {
      $offset = $ds;
    } else if ($dow >= $ds + $wl) {
      $offset = ($ds + 7) - $dow;
    } else {
      $offset = $ds - $dow;
    }
    return strtotime($offset . " day", $ts);
  }
  
  private static function buildClient($row) {
    if (! $row) return null;
    $client = new Client0(
        $row["client_id"], 
        $row["user_group_id"], 
        $row["uid"], 
        $row["last_name"], 
        $row["first_name"], 
        $row["sex"], 
        formatDate($row["birth"]),
        $row["img"],
        $row["date_created"], 
        $row["active"], 
        $row["cdata1"], 
        $row["cdata2"], 
        $row["cdata3"], 
        $row["trial"], 
        $row["living_will"],
        $row["poa"],
        $row["gest_weeks"],
        $row["middle_name"],
        $row["notes"],
        $row['active'],
        $row['deceased']
        );
    return $client;
  }
  private static function buildJClient($row, $withAddress = true) {
    if (! $row) return null;
    $jClient = new JClient(
        $row["client_id"], 
        $row["user_group_id"], 
        $row["uid"], 
        $row["last_name"], 
        $row["first_name"], 
        $row["sex"], 
        formatDate($row["birth"]),
        $row["img"],
        $row["date_created"],
        $row["active"], 
        $row["cdata1"], 
        $row["cdata2"], 
        $row["cdata3"], 
        $row["trial"], 
        $row["living_will"],
        $row["poa"],
        $row["gest_weeks"],
        $row["middle_name"],
        $row["notes"],
        $row['active'],
        $row['deceased']
        );
    if ($withAddress) {
      $jClient->shipAddress = SchedDao::buildJAddress($row);
    }
    return $jClient;
  }
  private static function buildJSchedEvent($row) {
    if (! $row) return null;
    $comment = decr($row["comment"]);
    logit_r($comment,'comment1');
    logit_r(convert_line_breaks($comment),'comment2');
    return new JSchedEvent(
        $row["sched_event_id"],
        $row["rp_type"],
        $row["rp_every"],
        formatDate($row["rp_until"]),
        $row["rp_on"],
        $row["rp_by"],
        convert_line_breaks(decr($row["comment"]),'<br>')
        );
  }
  private static function buildAddress($row) {
    if (! $row) return null;
    return new Address0(
        $row["address_id"],
        $row["table_code"],
        $row["table_id"],
        $row["type"],
        $row["addr1"],
        $row["addr2"],
        $row["addr3"],
        $row["city"],
        $row["state"],
        $row["zip"],
        $row["country"],
        $row["phone1"],
        $row["phone1_type"],
        $row["phone2"],
        $row["phone2_type"],
        $row["phone3"],
        $row["phone3_type"],
        $row["email1"],
        $row["email2"],
        $row["name"]
        );
  }
  public static function buildJAddress($row) {
    if (! $row) return null;
    $a = new JAddress(
        $row["address_id"],
        $row["table_code"],
        $row["table_id"],
        $row["type"],
        decr($row["addr1"]),
        decr($row["addr2"]),
        decr($row["addr3"]),
        decr($row["city"]),
        $row["state"],
        decr($row["zip"]),
        $row["country"],
        decr($row["phone1"]),
        $row["phone1_type"],
        decr($row["phone2"]),
        $row["phone2_type"],
        decr($row["phone3"]),
        $row["phone3_type"],
        decr($row["email1"]),
        decr($row["email2"]),
        decr($row["name"])
        );
    $a->includeCsz();
    return $a;   
  }
  private static function buildSched($row) {
    if (! $row) return null;
    return new Sched(
        $row["sched_id"], 
        $row["user_id"], 
        $row["user_group_id"], 
        $row["client_id"], 
        formatDate(decr($row["date"])), 
        $row["time_start"], 
        $row["duration"], 
        $row["closed"], 
        $row["status"],
        decr($row["comment"]),
        $row["type"],
        $row["sched_event_id"]
        );
  }
  private static function buildJSched($row) {
    if (! $row) return null;
    return new JSched(
        $row["sched_id"], 
        $row["user_id"], 
        $row["user_group_id"], 
        $row["client_id"], 
        formatDate(decr($row["date"])), 
        $row["time_start"], 
        $row["duration"], 
        $row["closed"], 
        $row["status"],
        decr($row["comment"]),
        $row["type"],
        $row["sched_event_id"]
        );
  }
  private static function buildQuickJSessionStub($row, $templateName) {
    if (! $row) return null;
    return new JSessionStub(
        $row["session_id"], 
        $row["template_id"], 
        $templateName, 
        $row["client_id"], 
        decr($row["date_created"]), 
        $row["date_updated"],
        decr($row["date_service"]),
        $row["closed"],
        $row["closed_by"],  
        $row["date_closed"],
        null,
        null,
        decr($row["last_name"]),
        decr($row["first_name"]),
        $row["sex"],
        $row["send_to_name"],
        decr($row["title"]),
        $row["standard"]
        );
  }
  private static function buildJSessionStub($row) {
    if (! $row) return null;
    return new JSessionStub(
        $row["session_id"], 
        $row["template_id"], 
        null, 
        $row["client_id"], 
        decr($row["date_created"]), 
        $row["date_updated"],
        decr($row["date_service"]),
        $row["closed"],
        $row["closed_by"],  
        $row["date_closed"],
        $row["created_by_name"],
        $row["updated_by_name"],
        decr($row["last_name"]),
        decr($row["first_name"]),
        $row["sex"],
        $row["send_to_name"],
        decr($row["title"]),
        $row["standard"]
        );
  }
  public static function getTypeDesc($type) {
    if (! isBlank($type)) {
      $types = SchedDao::getApptTypes(); 
      if (isset($types[$type])) {
        return $types[$type];
      }
    }
    return "[No Appt Type]";
  }
  public static function getStatus($status) {
    if (! isBlank($status)) {
      $statuses = SchedDao::getSchedStatuses();
      if (isset($statuses[$status])) {
        return $statuses[$status];
      }
    }
    return null;
  }
  
  private static function getApptTypes() {
    static $apptTypes;
    if ($apptTypes == null) {
      $apptTypes = CommonCombos::apptTypes();
    }
    return $apptTypes;
  }
  private static function getSchedStatuses() {
    static $schedStatuses;
    if ($schedStatuses == null) { 
      $schedStatuses = LookupDao::getSchedStatus();
    }
    return $schedStatuses;
  }
  private static function buildJClientEvent($row) {
    if (! $row) return null;
    return new JClientEvent(
        SchedDao::getApptTypes(),
        SchedDao::getSchedStatuses(),
        $row["activity_date"], 
        $row["type"], 
        $row["id"], 
        $row["tid"],
        $row["name"],
        decr($row["comment"]),
        $row["closed"],
        $row["date_closed"],
        $row["date_updated"],
        $row["client_id"],
        decr($row["title"]),
        $row["standard"]
        );
  }
  private static function buildJClientEventFromUpdate($row) {
    if (! $row) return null;
    return new JClientEventFromUpdate(
        $row["date"],
        $row["type"],
        $row["id"],
        $row["descr"]
        );
  }
  private static function buildJICard($row) {
    if (! $row) return null;
    return new JICard(
        $row["client_id"],
        $row["seq"],
        $row["plan_name"], 
        $row["subscriber_name"], 
        $row["name_on_card"],
        $row["group_no"],
        $row["subscriber_no"], 
        $row["date_effective"],
        $row["active"]
        );
  }
}
//
require_once "php/dao/_util.php";
require_once "php/dao/SessionDao.php";
require_once "php/dao/AuditDao.php";
require_once "php/dao/UserDao.php";
//require_once "php/dao/MsgDao.php";
require_once "php/dao/UsageDao.php";
require_once "php/data/db/Sched.php";
require_once "php/data/db/Client.php";
//require_once "php/data/db/ClientUpdate.php";
require_once "php/data/ui/SchedPage.php";
require_once "php/data/json/JClients.php";
require_once "php/data/json/JClient.php";
require_once "php/data/json/JClientEvent.php";
require_once "php/data/json/JClientHistory.php";
require_once "php/data/json/JClientEventFromUpdate.php";
require_once "php/data/json/JSched.php";
require_once "php/data/json/JSchedStub.php";
require_once "php/data/json/JSchedEvent.php";
require_once "php/data/json/JAddress.php";
require_once "php/data/json/JSessionStub.php";
require_once "php/data/json/JICard.php";
require_once "php/forms/utils/Paging.php";
require_once "php/forms/utils/CommonCombos.php";
p_i('/SchedDao');
