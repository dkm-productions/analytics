<?php
p_i('DataDao');
require_once "php/data/rec/sql/_SqlRec.php";
require_once "php/c/template-entry/TemplateEntry_Hx.php";
/**
 * DataDao
 * Handles session in/out processing of DATA_XX tables   
 */
class DataDao {
   
  const COL_DELIM = ", ";
  
  /*
   * Define data tables for outgoing data (SESSION to DATA_XX) 
   * PK defs:
   *   $fields  - parsed by console
   *   @ix      - clone anchor index
   *   ?#       - ?1,?2,?3,... supplied by extra arg(s) of set method, e.g. set(results,7): pk '?1'=7
   * All PK defs must be strings
   */
  public static function getOutDataTables($asJson = false) {
    static $tables;
    static $json;
    if ($tables == null) {
      $tables = array(
        'allergies' => array(
          'table' => 'data_allergies',
          'pk' => array('$ugid', '$cid', '$sid', '$dos', '@ix'),
          'fieldlist' => null/*now handled via _DataOut*/),  
        'diagnoses' => array(
          'table' => 'data_diagnoses',
          'pk' => array('$ugid', '$cid', '$sid', '$dos', '$puid'),
          'fieldlist' => null/*now handled via _DataOut*/),  
        'hist-med' => array(
          'table' => 'data_hists',
          'pk' => array('$ugid', '$cid', '0', '$sid', '0', '?1'),   
          'fieldlist' => JDataHist::SQL_FIELDS),  
        'hist-surg' => array(
          'table' => 'data_hists',
          'pk' => array('$ugid', '$cid', '0', '$sid', '1', '?1'),   
          'fieldlist' => JDataHist::SQL_FIELDS),  
        'hm' => array(
          'table' => 'data_hm',
          'pk' => array('$ugid', '$cid', '$sid', '1', '?1'),   
          'fieldlist' => JDataHm::SQL_FIELDS),  
        'hm-all' => array(
          'table' => 'data_hm',
          'pk' => array('$ugid', '$cid', '$sid', '1'),   
          'fieldlist' => JDataHm::SQL_FIELDS),  
        'immun' => array(
          'table' => 'data_immuns',
          'pk' => array('$ugid', '$cid', '$sid', '$dos', '$qcix'),
          'fieldlist' => null/*now handled via _DataOut*/),  
        'meds' => array(
          'table' => 'data_meds',
          'pk' => array('$ugid', '$cid', '$sid', '$dos', '$quid', '$qcix'),
          'fieldlist' => null/*now handled via _DataOut*/),  
        'vitals' => array(
          'table' => 'data_vitals',
          'pk' => array('$ugid', '$cid', '$sid', '$dos'),
          'fieldlist' => null/*now handled via Vitals_DataOut*/),  
        'proc' => array(
          'table' => 'Proc_DataOut',
          'pk' => array('$ugid', '$cid', '?1'),   
          'fieldlist' => null/*now handled via Proc_DataOut*/),
        'result' => array(
          'table' => 'ProcResult_DataOut',
          'pk' => array('$ugid', '$cid', '?1', '?2'),   
          'fieldlist' => null/*now handled via ProcResult_DataOut*/)
      );
    }
    if (! $asJson) return $tables;
    if ($json == null) {
      $json = jsonencode($tables);       
    }
    return $json;
  }  
  /*
   * Define data tables for incoming data (DATA_XX to SESSION)
   */
  public static function getInDataTables() {
    return array(
      'allergies',
      'diagnoses',
      'hist-med',
      'hist-surg',
      'hm',
      'hm-all',
      'meds',
      'vitals',
      'vitals-most-recent');
  }
  /*
   * Append to supplied SESSION data any auto-par IN DATA actions  
   */
  //public static function appendInDataActions(&$sessionData, $noteDate, $tid, $cid, $dos, $suppressFsActions = true) {
  public static function appendInData(&$actions, $noteDate, $tid, $cid, $dos) {
    $date = dateToString($dos);
    //$actions = DataDao::arrayifySessionActions($sessionData, $noteDate, $tid, $suppressFsActions);
    $templateIn = DataDao::fetchInNoteAutoPars($tid, $noteDate);
    $clientFsData = DataDao::getInData($templateIn["tables"], $cid, $date);
    foreach ($templateIn["parRows"] as &$parRow) {
      $inData = $clientFsData[$parRow["in_data_table"]];
      $dataRecs = $inData["rows"];
      if (! empty($dataRecs)) {
        if (DataDao::shouldAutoInsertInPar($parRow, $dataRecs[0])) {
          $getPar = dquote("getFsPar(" . $parRow["par_id"] . ")");
          if (! in_array($getPar, $actions)) {
            array_unshift($actions, $getPar);
          }
          $inActions = DataDao::buildInActions($inData, $parRow["par_id"]);
          $actions = array_merge($actions, $inActions);
        }
      }
    }
    //$sessionData = (isEmpty($actions)) ? null : "[" . implode(",", $actions) . "]";
  }
  public static function actionsAsString($actions) {
    return (empty($actions)) ? null : '[' . implode(',', $actions) . ']';
  }
  /*
   * Get facesheet IN ACTIONS for a user-requested paragraph
   * $injectorPref: supply only if pid is cloneable injection, to qualify datasyncs
   * Returns "['action','action',...]"
   */
  public static function inActionsForGetPar($pid, $injectorPref, $cid) {
    logit("inActionsForGetPar(" . $pid . "," . $injectorPref . "," . $cid);
    $par = TemplateReaderDao::getPar($pid, false, false);
    $actions = array();
    if ($par->inType == Par::TYPE_ON_DEMAND) {  
      $inData = DataDao::fetchInDataForTable($par->inTable, $cid, null);
      if (! empty($inData["rows"])) {
        $actions = DataDao::buildInActions($inData, $pid);
      }
    }
    $dsyncQids = DataDao::fetchDataSyncQids($pid);
    if ($injectorPref) {  // prepend any +dsyncs with injector pref
      $qids = array();
      foreach ($dsyncQids as $dsync => $x) {
        if (substr($dsync, 0, 1) == '+') {
          $dsync = $injectorPref . $dsync;
        }
        $qids[$dsync] = $x;
      }
      $dsyncQids = $qids;
    }
    logit_r($dsyncQids, 'dsyncqids');
    if (! empty($dsyncQids)) {
      $inData = DataDao::fetchClientValuesForDataSyncs($cid, $dsyncQids);
      logit_r($inData, 'indata');
      DataDao::appendDataSyncActions($actions, $dsyncQids, $inData);
      //logit_r($actions, 'after appendDataSyncActions');
    }
    //return (isEmpty($actions)) ? null : "[" . implode(",", $actions) . "]";
    return $actions;
  }
  /*
   * Returns [quid=>JQuestion,..]
   */
  public static function fetchQuestionsForTable($tid, $noteDate, $tableName) {
    $jqs = array();
    $sql = "SELECT p.par_id, p.uid FROM template_pars p INNER JOIN template_sections s ON p.section_id=s.section_id"
        . " WHERE s.template_id=" . $tid . " AND in_data_table=" . quote($tableName)  
        . " AND p.date_effective<" . quote($noteDate) 
        . " ORDER BY p.par_id";
    $conn = open();
    $res = query($sql);
    while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
      $questions = TemplateReaderDao::getQuestions($row["par_id"]);
      foreach ($questions as &$q) {
        $quid = $row["uid"] . "." . $q->uid;
        $jq = JsonDao::buildJQuestion($q);
        $jq->outData = $q->outData;
        $jqs[$quid] = $jq;
      }
    }
    close($conn);
    return $jqs;
  }
  /*
   * Returns [dsync=>JQuestion,..] 
   *      or [field=>JQuestion,..] if $assocByField=true
   */
  public static function fetchQuestionsForPartialDataSync($tid, $dsync, $assocByField = false) {
    $jqs = array();
    //$innerJoin = "INNER JOIN template_pars p ON q.par_id=p.par_id INNER JOIN template_sections s ON p.section_id=s.section_id";
    //$where = "s.template_id=$tid AND p.current=1 AND q.dsync_id LIKE '$dsync%'";
    //$conn = open();
    //$questions = TemplateReaderDao::getQuestionsWhere($where, $innerJoin);
    require_once 'php/c/template-entry/TemplateEntry_Hx.php';
    global $login;
    $questions = TQuestion_Pmhx::fetchAll($dsync, $login->userId);
    foreach ($questions as &$q) {
      //$jq = JsonDao::buildJQuestion($q);
      $key = ($assocByField) ? DataDao::extractField($q->dsyncId) : $q->dsyncId;
      $jqs[$key] = $q; //$jq;
    }
    //close($conn);
    logit_r($jqs, 'jqs');
    return $jqs;
  }
  public static function fetchQuestionsForSocHx() {
    $dsync = 'sochx.';
    require_once 'php/c/template-entry/TemplateEntry_Hx.php';
    global $login;
    $questions = TQuestion_DsyncSoc::fetchAll($dsync, $login->userId);
    foreach ($questions as &$q) {
      $key = $q->dsyncId;
      $jqs[$key] = $q; 
    }
    return $jqs;
  }
  public static function fetchQuestionsForFamHx($dsync) {
    require_once 'php/c/template-entry/TemplateEntry_Hx.php';
    global $login;
    $questions = TQuestion_DsyncFam::fetchAll($dsync, $login->userId);
    foreach ($questions as &$q) {
      $key = DataDao::extractField($q->dsyncId);
      $jqs[$key] = $q; 
    }
    return $jqs;
  }
  private static function extractField($dsync) {
    $i = strrpos($dsync, ".");
    return substr($dsync, $i + 1);
  }
  /*
   * Assemble data for requested $tables
   * Returns [       
   *    tname=>[..],  // see return of fetchInDataForTable
   *    tname=>[..],..
   *   ]
   */
  public static function getInData($tables, $clientId, $date) {
    foreach ($tables as $tname => $value) {
      //logit_r('getInData ' . $tname . ',' . $value);
      $tables[$tname] = DataDao::fetchInDataForTable($tname, $clientId, $date);
    }
    return $tables;
  }
  /*
   * Fetch value for single $datasync
   */
  public static function fetchClientValueForDataSync($cid, $dsync, $asObject = true) {
    $sql = "SELECT value FROM data_syncs WHERE client_id=$cid AND active='1' AND dsync='$dsync'";
    $v = decr(fetchField($sql, 'value'));
    return ($asObject) ? jsondecode($v) : $v;
  }
  /*
   * Fetch data values for given datasync(s)
   * $dsyncs:[dsync=>x,..]  // x is ignored
   * Returns [
   *    dsync=>value,..
   *   ]
   */
  public static function fetchClientValuesForDataSyncs($cid, $dsyncs) {
    $ins = array();
    $pshx = null;
    foreach ($dsyncs as $dsync => $x) {
      if ($dsync == 'pshx')
        $pshx = $dsync;
      else
        $ins[] = q($dsync);
    }
    $array = array();
    if (count($ins) > 0) {
      $in = implode(",", $ins);
      $sql = "SELECT dsync_id, value FROM data_syncs WHERE client_id=$cid AND active='1' AND dsync IN ($in)";
      $array = fetchSimpleArray($sql, 'value', 'dsync_id', true);
    }
    if ($pshx) {
      global $login;
      $names = Proc_Surg::fetchAllNames($login->userGroupId, $cid);
      $array[$pshx] = jsonencode($names); 
    }
    return $array;
  }
  /*
   * Returns requested JDataSyncGroup for client
   */
  public static function fetchDataSyncGroup($groupName, $cid) {
    $values = DataDao::fetchClientValuesForPartialDataSync($cid, "$groupName.");
    return new JDataSyncGroup($groupName, $values);
  }
  /*
   * Returns requested JDataSyncProcGroup for client
   */
  public static function fetchDataSyncProcGroup($cat, $cid) {
    $procs = DataDao::fetchClientValueForDataSync($cid, $cat);
    $values = DataDao::fetchClientValuesForPartialDataSync($cid, "$cat.");
    return new JDataSyncProcGroup($cat, $procs, $values);
  }
  /*
   * Returns requested JDataSyncFamGroup for client
   */
  public static function fetchDataSyncFamGroup($suid, $cid, $includeDefs) {
    $sopts = DataDao::fetchClientValueForDataSync($cid, $suid);
    //logit_r($sopts, 'sopts');
    $values = DataDao::fetchClientValuesForPartialDataSync($cid, "$suid.");
    //logit_r($values, 'values');
    $fam = new JDataSyncFamGroup($suid, $sopts, $values, $includeDefs);
    //logit_r($fam, 'fam');
    if ($sopts != $fam->sopts) {  // sopts after construction may have changed from reordering or eliminating dupes
      DataDao::saveDataSync($cid, $suid, jsonencode($fam->sopts));  
    }
    return $fam;
  }
  /*
   * Fetch data values whose datasync begins with partial (e.g. "sochx.")
   * Returns [
   *    dsyncId=>value,..
   *   ]
   */
  private static function fetchClientValuesForPartialDataSync($cid, $dsync) {
    $sql = "SELECT dsync_id, value FROM data_syncs WHERE client_id=$cid AND active='1' AND dsync_id LIKE '$dsync%'";
    return fetchSimpleArray($sql, 'value', 'dsync_id', true);
  }
  /* 
   * Fetch data row(s) for given table
   * Returns [
   *    "keyed"=>b,  // true if multi-rows, false if single (or each row treated as cloned individuals, e.g. meds)   
   *    "rows"=>[
   *      keyValue=>[field=>value,field=>value,...],  // if not keyed, no keyValue is assigned
   *      keyValue=>[field=>value,field=>value,...],...
   *      ]
   *    ]
   */
  private static function fetchInDataForTable($tname, $clientId, $date = null) {  // $date only required for vitals
    global $login;
    $keyField = null;
    $sql = null;
    switch ($tname) {
      case "allergies":
        $sql = "SELECT " . JDataAllergy::SQL_FIELDS
            . " FROM data_allergies WHERE client_id=" . $clientId
            . " AND session_id IS NULL AND active=1 ORDER BY agent";            
        break;
      case "hist-med":
        $sql = "SELECT " . JDataHist::SQL_FIELDS
            . " FROM data_hists WHERE hcat=" . JDataHist::HCAT_MED . " AND client_relation=0 AND client_id=" . $clientId
            . " AND session_id IS NULL AND active=1 GROUP BY hproc";
        $keyField = "hproc_id";
        break;
      case "hist-surg":
        $sql = "SELECT " . JDataHist::SQL_FIELDS
            . " FROM data_hists WHERE hcat=" . JDataHist::HCAT_SURG . " AND client_relation=0 AND client_id=" . $clientId
            . " AND session_id IS NULL AND active=1 GROUP BY hproc";
        $keyField = "hproc_id";
        break;
      case "hm":
        $rows = Procedures::getInDataHm($clientId);
        $keyField = "proc_id";
//        $sql = "SELECT " . JDataHm::SQL_FIELDS
//            . " FROM data_hm WHERE client_id=" . $clientId
//            . " AND session_id IS NULL AND active=1 ORDER BY proc_id";  // most recent HM by proc_id
        break;
      case "hm-all":
        $rows = Procedures::getInDataHmAll($clientId);
        $keyField = null;
//        $sql = "SELECT " . JDataHm::SQL_FIELDS
//            . " FROM data_hm WHERE client_id=" . $clientId
//            . " AND session_id=0 AND active=1 ORDER BY proc, date_sort DESC";
      break;
        case "meds":
        $sql = "SELECT " . JDataMed::SQL_FIELDS
            . " FROM data_meds WHERE client_id=" . $clientId
            . " AND session_id IS NULL AND active=1 ORDER BY expires, name";
        break;
      case "vitals":
        $ugid = $login->userGroupId;
        $from = strtotime($date);
        $hdata = HData_VitalsDate::create();
        $hdata->setType($ugid);
        $to = $from + 86400;
        $sql = "SELECT " . JDataVital::SQL_FIELDS
            . " FROM data_vitals v"
            . " JOIN (hdata3 T4) ON T4.t='" . $hdata->t . "' AND t4.i=v.data_vitals_id AND t4.d>=$from AND t4.d<$to"
            . " WHERE client_id=$clientId AND active=1 AND session_id IS NULL ORDER BY T4.d desc LIMIT 1";
        break;
      case "vitals-most-recent":
        $sql = "SELECT " . JDataVital::SQL_FIELDS
            . " FROM data_vitals WHERE client_id=" . $clientId
            . " AND session_id IS NULL ORDER BY data_vitals_id DESC LIMIT 1";
        break;
    }
    if ($sql) 
      $rows = fetchArray($sql, $keyField);
    $return = array(
      "keyed" => ($keyField !== null),
      "rows" => $rows);
    logit_r($return, 'return fetchInDataForTable tname=' . $tname);
    return $return;
  }
  /*
   * Save OUT session data
   * $out{dtid:}       // "meds":
   *   records{pk:}    // "parsed|pk|values":
   *     fields{dcid:} // "column":"value"
   * $outDataSyncs:{
   *   "cid":cid,
   *   "sid":sid,
   *   "dos":dos,
   *   "dsyncs":{
   *      dsync:value,..
   *     }
   *   }
   */
  public static function saveOutputDataAndSyncs($out, $outDataSyncs) {
    if ($out == null && $outDataSyncs == null) {
      return;
    }
    $conn = batchOpen();
    if ($out != null) {
      DataDao::saveOutputData($out);
    }
    if ($outDataSyncs != null) {
      logit_r($outDataSyncs, 'outdatasyncs');
      $dsyncs = DataDao::fetchClientValuesForDataSyncs($outDataSyncs->cid, $outDataSyncs->dsyncs);
      logit_r($dsyncs, 'dsyncs');
      DataDao::saveOutputDataSyncs($dsyncs, $outDataSyncs);
    }
    batchClose($conn);
  }
  /*
   * Save OUT session data to DATA_XX
   */
  private static function saveOutputData($out) {
    $tables = DataDao::getOutDataTables();
    logit_r($out, 'saveOutputData');
    static::saveOutputProcs($out); 
    static::saveOutputVitals($out); 
    static::saveOutputAllergies($out); 
    static::saveOutputDiagnoses($out); 
    static::saveOutputMeds($out); 
    static::saveOutputImmuns($out); 
    foreach ($out as $dtid => $table) {
      $tname = $tables[$dtid]['table'];
      $fieldlist = $tables[$dtid]['fieldlist'];
      if ($fieldlist) {  
        $colnames = explode(DataDao::COL_DELIM, $fieldlist);
        foreach ($table->records as $pk => $record) {
          $colvals = array();
          $colvals[] = "NULL";  // first column = auto-increment PK
          // Add PKs specified in $out to value array  
          $pks = explode("|", $pk);
          for ($i = 0; $i < count($pks); $i++) {  
            $colvals[] = quote($pks[$i], true);          
          }
          //logit_r($pks, 'pks');
          // Fill in remaining columns
          $fields = get_object_vars($record->fields);
          //logit_r($fields, 'fields');
          for ($i = count($pks) + 1; $i < count($colnames); $i++) {
            $value = "NULL";
            $colname = strtolower($colnames[$i]);
            if (array_key_exists($colname, $fields)) {
              $value = quote($fields[$colname], true);
            }
            $colvals[] = $value;
          }
          $vals = implode(',', $colvals);
          $sql = "INSERT INTO $tname ($fieldlist) VALUES($vals)";
          insert($sql);
        }
      }
    }
  }
  private static function saveOutputVitals($out) {
    $out_vitals = get($out, 'vitals');
    if ($out_vitals) 
      Vitals_DataOut::save_from($out_vitals);
  }
  private static function saveOutputProcs($out) {
    $out_proc = get($out, 'proc');
    $out_result = get($out, 'result');
    if ($out_proc) {
      $procs = Proc_DataOut::saveAll_from($out_proc);
      logit_r($procs, 'procs from proc_dataout');
      if ($out_result)
        ProcResult_DataOut::saveAll_from($out_result, $procs);
    }
  }
  private static function saveOutputAllergies($out) {
    $outd = get($out, 'allergies');
    if ($outd) 
      Allergy_DataOut::saveAll_from($outd);
  }
  private static function saveOutputDiagnoses($out) {
    $outd = get($out, 'diagnoses');
    if ($outd) 
      Diagnosis_DataOut::saveAll_from($outd);
  }
  private static function saveOutputMeds($out) {
    $outd = get($out, 'meds');
    if ($outd) 
      Med_DataOut::saveAll_from($outd);
  }
  private static function saveOutputImmuns($out) {
    $outd = get($out, 'immun');
    if ($outd) 
      Immun_DataOut::saveAll_from($outd);
  }
  /*
   * Save session datasyncs values to DATA_SYNCS
   * $out:[dsyncId=>value,..] 
   * $currentValues:[dsyncId=>value,..]  // to only update values that have changed
   */
  private static function saveOutputDataSyncs($currentValues, $out) {
    global $login;
    $sql0 = "INSERT INTO data_syncs (data_sync_id, user_group_id, client_id, dsync_id, dsync, date_sort, session_id, value, active, date_updated)"
        . " VALUES(null, $login->userGroupId, $out->cid, ";  // dsync_id, dsync,
    $sql1 = ", '$out->dos', $out->sid, ";  // value,
    $sql2 = ", '1', null)"
        . " ON DUPLICATE KEY UPDATE dsync=VALUES(dsync), date_sort=VALUES(date_sort), session_id=VALUES(session_id), value=VALUES(value), active=VALUES(active)";
    foreach ($out->dsyncs as $dsyncId => $value) {
      $dsync = DataDao::decloneDsyncId($dsyncId);
      $currentValue = geta($currentValues, $dsyncId);
      if ($value != $currentValue) {
        $sql = $sql0 . quote($dsyncId) . "," . quote($dsync) . $sql1 . encr($value, true) . $sql2;
        query($sql);
        if ($dsyncId == 'famHx') {
          Proc_FamHxRecorded::record_FromDsync($out->cid, $value);
        }
      }
    }
  }
  /*
   * Save facesheet datasync values to DATA_SYNCS
   * $dsyncs:[dsyncId=>value,..]
   */
  public static function saveDataSyncs($cid, $dsyncs, $date = null, $sid = "NULL") {
    if ($date == null) {
      $date = nowNoQuotes();
    }
    logit_r($dsyncs, 'dsyncs to savedatasyncs');
    global $login;  
    $sql0 = "INSERT INTO data_syncs (data_sync_id, user_group_id, client_id, dsync_id, dsync, date_sort, session_id, value, active, date_updated)"
        . " VALUES(null, $login->userGroupId, $cid, ";  // dsync_id, dsync,
    $sql1 = ", '$date', $sid, ";  // value, active,
    $sql2 = ", null)"
        . " ON DUPLICATE KEY UPDATE date_sort=VALUES(date_sort), session_id=VALUES(session_id), value=VALUES(value), active=VALUES(active)";
    $conn = batchOpen();
    foreach ($dsyncs as $dsyncId => $value) {
      $dsync = DataDao::decloneDsyncId($dsyncId);
      $active = "1";
      if ($dsync != $dsyncId && $value == "[]") { 
        $active = "0";
      }
      $sql = $sql0 . quote($dsyncId) . "," . quote($dsync) . $sql1 . encr($value, true) . "," . quote($active) . $sql2;
      query($sql);
      if ($dsyncId == 'famHx') {
        Proc_FamHxRecorded::record_FromDsync($cid, $value);
      }
    }
    batchClose($conn);
  }
  /*
   * Save single datasync (dsync ID and serialized value)
   */
  public static function saveDataSync($cid, $dsync, $value) {
    DataDao::saveDataSyncs($cid, array($dsync => $value));
  }
  /*
   * Delete datasyncs beginning with prefix 
   */
  public static function deleteDataSyncs($cid, $prefix) {
    if (trim($cid) == "" || trim($prefix) == "") {
      return;
    }
    query("DELETE FROM data_syncs WHERE client_id=$cid AND dsync_id LIKE '$prefix%'");
  }
  /*
   * Change "old.*" datasyncs to "new.*" 
   */
  public static function reassignDataSyncPrefixes($cid, $old, $new) {
    if (trim($cid) == "" || trim($old) == "") {
      return;
    }
    $replace = "'$old','$new'";
    query("UPDATE data_syncs SET dsync_id=REPLACE(dsync_id,$replace), dsync=REPLACE(dsync,$replace) WHERE client_id=$cid AND dsync_id LIKE '$old%'");
  }
  /*
   * Removes $puid from client's JDataSyncFamGroup and reassigns $suid selected options
   */
  public static function removeDataSyncFamPuid($cid, $suid, $puid) {
    if ($cid && $suid && $puid) 
      static::deleteDataSyncs($cid, "$suid.$puid");
    $fam = DataDao::fetchDataSyncFamGroup($suid, $cid, false);
    $reassigns = $fam->removePuid($puid);
    if (! empty($reassigns)) {
      DataDao::deleteDataSyncs($cid, "$suid.$puid");  // remove existing records for puid
      foreach ($reassigns as $old => $new) {
        DataDao::reassignDataSyncPrefixes($cid, "$suid.$old", "$suid.$new");
      }
    }
    DataDao::saveDataSync($cid, $suid, jsonencode($fam->sopts));  // save reassigned selected options
  }
  /*
   * Convert out functions to "col_name=$tag,col_name=$tag" format
   * Returns [
   *    "out"=>s         // "col_name=$tag,col_name=$tag,..."
   *    "pk"=>[s,s,...]  // any PK values specified in first fn
   *   ] 
   */
  public static function parseOutFunctions($out, $asArray) {
    logit("parseOutFunctions(" . $out . "," . $asArray . ")");
    $as = explode(";", $out);
    $pks = null;
    foreach ($as as &$action) {
      $a = explode("(", $action);
      $fn = $a[0];
      $args = explode(",", substr($a[1], 0, -1));
      $colname = array_shift($args);
      //logit_r($args);
      logit("args");
      switch ($fn) {
        case "set":  
          $action = $colname . "=" . (($asArray) ? '$otexta' : '$otext');
          // TODO if field is date
          break;
        case "seta":  
          $action = $colname . '=$otexta';
          break;
        case "setText":  
          $action = $colname . '=$qtext';
          break;
        case "setById": 
          $action = $colname . "=" . (($asArray) ? '$ouida' : '$ouid');
          break;
        case "setExactDate":
          $action = $colname . '=$otextAsDate';
          break;
        case "setExactDateDos":
          $action = $colname . '=$dos';
          break;
        case 'setPar':
          $action = $colname . '=$ptext';
          break;
        case "setDate": 
          $colname2 = substr_replace($colname, "sort", -4);  // assumes columns are named xx_text and xx_sort 
          $action = $colname . '=$otext,' . $colname2 . '=$otextAsDate';
          break;
        case "setDateDos": 
          $colname2 = substr_replace($colname, "sort", -4);  // assumes columns are named xx_text and xx_sort 
          $action = $colname . '=$dosfull,' . $colname2 . '=$dos';
          break;
      }
      //logit("pk=" . $pks);
      //logit_r($args);
      if ($pks == null && count($args) > 0) {
        $pks = &$args;
      }
    }
    $return = array(
        "out" => implode(",", $as),
        "pk" => $pks
        );
    logit_r($return, 'return parseoutdatafunction');
    return $return;
  }
  /*
   * DEPRECATED: now managing colnames in DataDao:getOutDataTables method
   * 
   * Uses MySQL DESCRIBE to return array of column names for table
   * Returns ["col_name","col_name",...]
   */
  private static function getColNames($tname) {
    $colnames = array();
    $res = query("DESCRIBE " . $tname);
    while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
      $colnames[] = $row["Field"];
    }
    return $colnames;
  }
  /*
   * Convert session actions ("data" column value) to array, eliminating any deemed to be "facesheet driven" (e.g. meds)
   * Returns [
   *    action->action  // keyed by action to elim dupes
   *   ] 
   */
  public static function arrayifySessionActions($data, $noteDate, $tid, $suppressFsActions = true) {
    $actions = array();
    if ($data != null) {  
      //logit_r($data, 'arrayifySession data');
      $qids = DataDao::fetchInDataQids($tid, $noteDate);
      $aactions = explode("\",\"", substr($data, 2, -2));
      foreach ($aactions as &$action) {
        $a = split("[(,)]", $action);
        if ($suppressFsActions && (strpos($a[0], "addMed") !== false || strpos($a[0], "addAllergy") !== false || strpos($a[0], "setByValue") !== false )) {
          // don't include these actions
        } else if ($a[0] == "setFreeText" && strpos($a[1], "medq_") !== false) {
          // don't include RX comments  
        } else if ($suppressFsActions && in_array($a[1], $qids)) {
          // don't include if first arg is in the qid list
        } else {
          $action = dquote($action);
          $actions[$action] = $action;
        }
      }
    }
    return $actions;
  }
  /*
   * Append console-ready actions to bring in datasyncs
   */
  private static function appendDataSyncActions(&$actions, $dsyncQids, $inData) {
    logit_r($dsyncQids, "dsyncQids");
    logit_r($inData, "inData");
    foreach ($inData as $dsyncId => $value) {
      $dsync = DataDao::decloneDsyncId($dsyncId);
      $qid = $dsyncQids[$dsync];
      if (! isset($dsyncQids[$dsync])) {
        logit_r($dsyncQids, 'Dsync ' . $dsync . ' NOT FOUND!!!!');
      }
      $a = explode('+', $dsyncId);
      if (count($a) > 1) {  // cloning par dsync, e.g. "famHx.mother+female.age"
        $meth = 'setByValue';
        $args = array(quote($qid), quote(addslashes($value), true), quote($a[0]));  // include injector arg
      } else {
        if ($dsyncId != $dsync) {  // cloning question dsync, e.g. "sochx.drugs?1"
          $meth = 'addComboByValues';
          $args = array(quote($qid), quote(addslashes($value), true));
        } else {
          $meth = 'setByValue';
          $args = array(quote($qid), quote(addslashes($value), true));
        }
      }
      $action = dquote($meth . '(' . implode(',', $args) . ')');
      $actions[$action] = $action;
    }
  }
  /*
   * Returns non-instance portion if dsync was generated from a cloning question, e.g. "sochx.drugs" for "sochx.drugs?0"
   * Otherwise returns same
   */
  private static function decloneDsyncId($dsyncId) {
    $i = strpos($dsyncId, "?");
    if ($i !== false) {
      return substr($dsyncId, 0, $i);  
    }
//    $i = strpos($dsyncId, "+");
//    if ($i !== false) {
//      return substr($dsyncId, $i);
//    }
    return $dsyncId;
  }
  /*
   * Parse IN ACTIONS from a par's questions into console-ready actions using values contained in data record(s)
   * Returns ['action','action',...]
   */
  private static function buildInActions($inData, $pid) {
    $actions = array();
    $inActions = DataDao::parseInActions($pid);
    if ($inData["keyed"]) {
      foreach ($inActions as &$inAction) {
        DataDao::appendAsConsoleAction($actions, $inAction, $inData["rows"], true);
      }
    } else {   
      foreach ($inData["rows"] as &$dataRec) {
        foreach ($inActions as &$inAction) {
          DataDao::appendAsConsoleAction($actions, $inAction, $dataRec);
        }
      }
    }
    logit_r($actions, 'built inactions');
    return $actions;
  }
  /*
   * Supply data args to IN ACTION, convert to console action, and append to action array
   */
  private static function appendAsConsoleAction(&$actions, $inAction, $dataRecs, $isMulti = false) {
    $fn = $inAction["fn"];
    $args = $inAction["args"];
    $rowkey = $inAction["rowkey"];
    logit_r($inAction, 'appendAsConsoleAction');
    foreach ($args as &$arg) {
      if (substr($arg, 0, 1) != "'" && ! is_numeric($arg)) {  // exclude strings or integers (hardcoded args)
        $arg = DataDao::parseDataArg($arg, $dataRecs, $isMulti, $rowkey);
      }
    }
    $action = DataDao::buildConsoleAction($fn, $args);
    logit_r($action, 'action');
    if ($action != null) {
      $actions[$action] = $action;
    }
  }
  /*
   * Convert parsed IN ACTION to a console-ready action
   */
  private static function buildConsoleAction($fn, $args) {
    switch ($fn) {
      case "get":
        if ($args[1] == "null") {
          return null;  // prevents blank vitals fields from generating questions
        }
        $fn = "setByValue";
        break;
      case "setQuid": 
        $fn = "setByQuid";
        break;
      case "ifNullSetIndex":
        do {
          if ($args[0] != "null") {
            return; 
          }
          array_shift($args);
        } while (count($args) > 2);
        $fn = "setByIndex";
        break;
      case "ifNotNullSetIndex":
        do {
          if ($args[0] == "null") {
            return;
          }
          array_shift($args);
        } while (count($args) > 2);
        $fn = "setByIndex";
        break;
    }
    return dquote($fn . "(" . implode(",", $args) . ")");
  }
  /*
   * Replace data field argument with field's value from data rec
   */
  private static function parseDataArg($field, $dataRecs, $isMulti, $rowkey) {  // isMulti=false means $dataRecs holds one record
    logit("parseDataArg, field=" . $field . ",isMulti=" . $isMulti . ",rowkey=" . $rowkey);
    logit_r($dataRecs, 'field=' . $field); 
    if (! $isMulti) {
      return quote(addslashes(convert_line_breaks($dataRecs[$field], '<br>')), true);  // just return data field's value
    } else {
      if ($rowkey == null) {  // build array of field's value across each data rec
        $values = array();
        foreach ($dataRecs as &$dataRec) {
          $values[] = $dataRec[$field];
        }
        return quote(jsonencode($values), true);
      } else {  // rowkey provided, get requested rec if exists
        if (isset($dataRecs[$rowkey])) {
          return quote(addslashes($dataRecs[$rowkey][$field]), true);
        } else {
//          logit_r($dataRecs);
//          logit("it was null");
//          logit_r("rowkey=" . $rowkey);
          return quote(null);
        }
      }
    }
  }
  /*
   * Returns [
   *     ["fn"=>s,               // "setByValue"
   *      "args"=>[arg,arg,...]  // ["'qid'","data_column_name"]
   *     ]
   *   ]
   */
  private static function parseInActions($pid) {
    $inActionRows = DataDao::fetchInDataActions($pid);
    $inActions = array();
    foreach ($inActionRows as &$inActionRow) {
      DataDao::appendInActionsFromRow($inActionRow, $inActions);
    }
    return $inActions;
  }
  
  /*
   * Split out semi-delimited IN ACTIONS from row and append each to array 
   */
  private static function appendInActionsFromRow($row, &$array) {
    $qid = $row["question_id"];
    $inActions = explode(";", $row["in_data_actions"] . ";");
    foreach ($inActions as &$inAction) {
      $inAction = trim($inAction);
      if ($inAction != "") {
        $action = DataDao::parseInAction($inAction, $qid);
        if ($action != null) {
          $array[] = $action;
        } 
      }
    }
  }
  /*
   * Returns [
   *   "fn"=>s,                  // "setByValue"
   *   "args"=>[arg1,arg2,...],  // ["'1004'","data_column_name"]
   *   "multi"=>b,               // true if fn operates on all data rows (e.g. setByValuesFromRows)   
   *   "rowkey"=>s               // key to multi-table fetcharray (to reference single row)       
   *   ],
   */
  private static function parseInAction($action, $qid) {
    $a = explode("(", $action);
    $fn = $a[0];
    $args = explode(",", substr($a[1], 0, -1));
    $addQidArg = null;
    $multi = false;
    $rowkey = null;
    switch ($fn) {
      case "ifNullSetIndex":
      case "ifNotNullSetIndex":
        $addQidArg = count($args) - 1;  // should be 2nd to last arg  
        break;
      case "setQuid":
        $addQidArg = null;  // no QID for you  
        break;
      case "get":
        $addQidArg = 0;
        if (count($args) == 2) {
          $rowkey = $args[1];
          array_pop($args);
        }
        break;
      default:
        $addQidArg = 0;  // everyone else gets QID as first arg
    }
    if ($addQidArg !== null) {
      array_splice($args, $addQidArg, 0, squote($qid));  // add implicit QID arg
    }
    return array(
        "fn" => $fn,
        "args" => $args,
        "multi" => $multi,
        "rowkey" => $rowkey
        );
  }
  /*
   * Returns [parId,parId,...]
   */
  private static function fetchInNoteParsForTable($tid, $noteDate, $tableName) {
    $sql = "SELECT par_id FROM template_pars p INNER JOIN template_sections s ON p.section_id=s.section_id"
        . " WHERE s.template_id=" . $tid . " AND in_data_table=" . quote($tableName)  
        . " AND p.date_effective<" . quote($noteDate) 
        . " ORDER BY p.par_id";
    return fetchSimpleArray($sql, "par_id");
  }
  /*
   * Get auto-inserted "in data" pars (type 1 or 2) 
   * Returns [
   *   "parRows"=>[
   *      ["par_id"=>s,
   *       "in_data_table"=>s,
   *       "in_data_type"=>s,
   *       "in_data_cond"=>s]],
   *   "tables"=>[
   *     tname=>tname]
   *   ]
   */
  private static function fetchInNoteAutoPars($tid, $noteDate) {
    $sql = "SELECT par_id, in_data_table, in_data_type, in_data_cond FROM template_pars p INNER JOIN template_sections s ON p.section_id=s.section_id"
        . " WHERE s.template_id=$tid AND in_data_type IN (1,2) AND p.date_effective<'$noteDate'" 
        . " ORDER BY p.date_effective desc";
    $rows = fetchArray($sql);
    $tables = array();
    $trows = array();
    foreach ($rows as $row) {
      $name = $row['in_data_table'];
      $tables[$name] = $name;
      if (! isset($trows[$name])) 
        $trows[$name] = $row;
    }
    return array('parRows' => array_values($trows), 'tables' => $tables);
  }
  /*
   * Returns [
   *   "question_id"=>qid,
   *   "in_data_actions"=>s
   *   ]
   */
  private static function fetchInDataActions($pid) {
    $sql = "SELECT question_id, in_data_actions FROM template_questions " 
        . " WHERE par_id=$pid AND in_data_actions IS NOT NULL "
        . " ORDER BY sort_order";
    return fetchArray($sql, null);
  }
  /*
   * Returns [
   *    dsyncId=>qid,...
   *   ]
   */
  private static function fetchDataSyncQids($pid) {
    $sql = "SELECT question_id, dsync_id FROM template_questions "
        . " WHERE par_id=$pid AND dsync_id IS NOT NULL "
        . " ORDER BY sort_order";
    return fetchSimpleArray($sql, "question_id", "dsync_id");
  }
  /*
   * Returns qid of question containing requested data sync
   */
  private static function fetchDataSyncQid($dsync, $tid) {
    $sql = "SELECT question_id FROM template_questions q INNER JOIN template_pars p ON q.par_id=p.par_id INNER JOIN template_sections s ON p.section_id=s.section_id"
        . " WHERE s.template_id=$tid AND p.current=1 AND q.dsync_id='$dsync'"; 
    return fetchField($sql);
  }
  /*
   * Return JQuestion for question containing requested data sync
   */
  public static function fetchDataSyncQuestion($dsync, $tid) {
    $qid = DataDao::fetchDataSyncQid($dsync, $tid);
    return TemplateReaderDao::getJQuestion($qid);
  }  
  /*
   * Returns ["qid","qid","qid",...]
   */
  private static function fetchInDataQids($tid, $noteDate) {
    $sql = "SELECT question_id FROM template_questions q INNER JOIN template_pars p ON q.par_id=p.par_id INNER JOIN template_sections s ON p.section_id=s.section_id"
        . " WHERE s.template_id=$tid AND in_data_type>0 AND p.date_effective<'$noteDate'"; 
    return fetchSimpleArray($sql, "question_id");
  }
  /*
   * Returns true if conditions met to auto-insert par
   */
  private static function shouldAutoInsertInPar($parRow, $dataRec) {
    switch ($parRow['in_data_type']) {
      case Par::TYPE_AUTO_ADD:
        return true;
      case Par::TYPE_AUTO_ADD_IF_COND_FIELDS_NOT_NULL:
        $cols = explode(",", $parRow['in_data_cond']);
        for ($i = 0; $i < count($cols); $i++) {
          if ($dataRec[$cols[$i]] == null) {
            return false;  // found a null field for a cond field
          }
        }
        return true;
    }
    return false;
  }
}
class Proc_Surg extends SqlRec implements AutoEncrypt {
  //
  public $procId;
  public $userGroupId;
  public $clientId;
  public $date;
  public $ipc;
  public $Ipc;
  //
  public function getSqlTable() {
    return 'procedures';
  }
  public function getEncryptedFids() {
    return array('date','comments');
  }
  //
  static function fetchAll($ugid, $cid) {
    $c = static::asCriteria($ugid, $cid);
    return static::fetchAllBy($c);
  }
  static function fetchAllNames($ugid, $cid) {
    $recs = static::fetchAll($ugid, $cid);
    logit_r($recs,'f a procsurg');
    foreach ($recs as &$rec) 
      $rec = $rec->Ipc->name . ' (' . formatApproxDate($rec->date) . ')';
    return $recs;
  }
  static function asCriteria($ugid, $cid) {
    $c = new static();
    $c->clientId = $cid;
    $c->Ipc = Ipc::asRequiredJoin($ugid, Ipc::CAT_SURG);
    return $c;
  } 
}
//
require_once "php/data/rec/sql/Vitals.php";
class Vitals_DataOut extends Vital {
  //
  /**
   * @param object $out {'records':{'ugid|cid|ipc':{'fields':{fid:value,..}}}}
   * @return self 
   */
  static function save_from($out) { 
    $rec = static::fromOut($out);
    $rec->save();
    return $rec;
  }
  static function fromOut($out) {
    foreach ($out->records as $pk => $record) {
      $pk = Pk_Vitals::from($pk);
      $me = static::from($record->fields, $pk);
      return $me;
    }
  }
  static function from($o, $pk) {
    $me = new static();
    foreach ($o as $fid => $value) {
      $cfid = static::sqlToCamel($fid);
      $me->$cfid = $value;
    }
    $me->userGroupId = $pk->ugid;
    $me->clientId = $pk->cid;
    $me->sessionId = $pk->sid;
    $me->date = $pk->date;
    $me->active = null;
    logit_r($me, 'vitals_dataout from');
    return $me;
  }
}
class Pk_Vitals {
  //
  public $ugid;
  public $cid;
  public $sid;
  public $date;
  //
  static function from($string) {
    $pks = explode('|', $string);
    return static::fromArray($pks);
  }
  protected static function fromArray($pks) {
    $me = new static();
    $me->ugid = $pks[0];
    $me->cid = $pks[1];
    $me->sid = $pks[2];
    $me->date = $pks[3];
    return $me;
  }
}
require_once "php/data/rec/sql/AllergiesLegacy.php";
class Allergy_DataOut extends Allergy {
  //
  /**
   * @param object $out {'records':{'ugid|cid|ipc':{'fields':{fid:value,..}}}}
   * @return self 
   */
  static function saveAll_from($out) { 
    $recs = static::fromOut($out);
    static::saveAll($recs);
    return $recs;
  }
  static function fromOut($out) {
    $us = array();
    foreach ($out->records as $pk => $record) {
      $pk = Pk_Allergy::from($pk);
      $me = static::from($record->fields, $pk);
      $us[] = $me;
    }
    return $us;
  }
  static function from($o, $pk) {
    $me = new static();
    foreach ($o as $fid => $value) {
      $cfid = static::sqlToCamel($fid);
      $me->$cfid = $value;
    }
    $me->userGroupId = $pk->ugid;
    $me->clientId = $pk->cid;
    $me->sessionId = $pk->sid;
    $me->date = $pk->date;
    $me->index = $pk->ix;
    $me->active = true;
    return $me;
  }
}
class Pk_Allergy {
  //
  public $ugid;
  public $cid;
  public $sid;
  public $date;
  public $ix;
  //
  static function from($string) {
    $pks = explode('|', $string);
    return static::fromArray($pks);
  }
  protected static function fromArray($pks) {
    $me = new static();
    $me->ugid = $pks[0];
    $me->cid = $pks[1];
    $me->sid = $pks[2];
    $me->date = $pks[3];
    $me->ix = $pks[4];
    return $me;
  }
}
require_once "php/data/rec/sql/MedsLegacy.php";
class Med_DataOut extends Med {
  //
  /**
   * @param object $out {'records':{'ugid|cid|ipc':{'fields':{fid:value,..}}}}
   * @return self 
   */
  static function saveAll_from($out) { 
    $recs = static::fromOut($out);
    static::saveAll($recs);
    return $recs;
  }
  static function fromOut($out) {
    $us = array();
    foreach ($out->records as $pk => $record) {
      $pk = Pk_Med::from($pk);
      $me = static::from($record->fields, $pk);
      $us[] = $me;
    }
    return $us;
  }
  static function from($o, $pk) {
    $me = new static();
    foreach ($o as $fid => $value) {
      $cfid = static::sqlToCamel($fid);
      $me->$cfid = $value;
    }
    $me->userGroupId = $pk->ugid;
    $me->clientId = $pk->cid;
    $me->sessionId = $pk->sid;
    $me->date = $pk->date;
    $me->quid = $pk->quid;
    $me->index = $pk->ix;
    $me->active = true;
    return $me;
  }
}
class Pk_Med {
  //
  public $ugid;
  public $cid;
  public $sid;
  public $date;
  public $quid;
  public $ix;
  //
  static function from($string) {
    $pks = explode('|', $string);
    return static::fromArray($pks);
  }
  protected static function fromArray($pks) {
    $me = new static();
    $me->ugid = $pks[0];
    $me->cid = $pks[1];
    $me->sid = $pks[2];
    $me->date = $pks[3];
    $me->quid = $pks[4];
    $me->ix = $pks[5];
    return $me;
  }
}
require_once "php/data/rec/sql/Diagnoses.php";
class Diagnosis_DataOut extends Diagnosis {
  //
  /**
   * @param object $out {'records':{'ugid|cid|ipc':{'fields':{fid:value,..}}}}
   * @return self 
   */
  static function saveAll_from($out) { 
    $recs = static::fromOut($out);
    static::saveAll($recs);
    return $recs;
  }
  static function fromOut($out) {
    logit_r($out, 'Diag_DataOut');
    $us = array();
    foreach ($out->records as $pk => $record) {
      $pk = Pk_Diagnosis::from($pk);
      $me = static::from($record->fields, $pk);
      $us[] = $me;
    }
    return $us;
  }
  static function from($o, $pk) {
    $me = new static();
    foreach ($o as $fid => $value) {
      $cfid = static::sqlToCamel($fid);
      $me->$cfid = $value;
    }
    $me->userGroupId = $pk->ugid;
    $me->clientId = $pk->cid;
    $me->sessionId = $pk->sid;
    $me->date = $pk->date;
    $me->parUid = $pk->puid;
    $me->active = null;
    return $me;
  }
  public function isActiveStatus() {
    null;
  }
}
class Pk_Diagnosis {
  //
  public $ugid;
  public $cid;
  public $sid;
  public $date;
  public $puid;
  //
  static function from($string) {
    $pks = explode('|', $string);
    return static::fromArray($pks);
  }
  protected static function fromArray($pks) {
    $me = new static();
    $me->ugid = $pks[0];
    $me->cid = $pks[1];
    $me->sid = $pks[2];
    $me->date = $pks[3];
    $me->puid = $pks[4];
    return $me;
  }
}
require_once "php/data/rec/sql/Immuns.php";
class Immun_DataOut extends Immun {
  //
  /**
   * @param object $out {'records':{'ugid|cid|ipc':{'fields':{fid:value,..}}}}
   * @return self 
   */
  static function saveAll_from($out) { 
    $recs = static::fromOut($out);
    static::saveAll($recs);
    return $recs;
  }
  static function fromOut($out) {
    $us = array();
    foreach ($out->records as $pk => $record) {
      $pk = Pk_Immun::from($pk);
      $me = static::from($record->fields, $pk);
      $us[] = $me;
    }
    return $us;
  }
  static function from($o, $pk) {
    $me = new static();
    foreach ($o as $fid => $value) {
      $cfid = static::sqlToCamel($fid);
      $me->$cfid = $value;
    }
    $me->userGroupId = $pk->ugid;
    $me->clientId = $pk->cid;
    $me->sessionId = $pk->sid;
    $me->date = $pk->date;
    $me->active = true;
    return $me;
  }
}
class Pk_Immun {
  //
  public $ugid;
  public $cid;
  public $sid;
  public $date;
  public $ix;
  //
  static function from($string) {
    $pks = explode('|', $string);
    return static::fromArray($pks);
  }
  protected static function fromArray($pks) {
    $me = new static();
    $me->ugid = $pks[0];
    $me->cid = $pks[1];
    $me->sid = $pks[2];
    $me->date = $pks[3];
    $me->ix = $pks[4];
    return $me;
  }
}
require_once "php/data/rec/sql/Procedures.php";
class Proc_DataOut extends ProcRec {
  //
  public function save() {
    parent::save();
    Hdata_ProcDate::from($this)->save();
  }
  /**
   * @param object $out {'records':{'ugid|cid|ipc':{'fields':{fid:value,..}}}}
   * @return Proc[ipc] 
   */
  static function saveAll_from($out) { 
    $recs = static::fromOut($out);
    static::saveAll($recs);
    return $recs;
  }
  static function fromOut($out) {
    $us = array();
    foreach ($out->records as $pk => $record) {
      $pk = Pk_Proc::from($pk);
      $me = static::from($record->fields, $pk);
      $us[$me->ipc] = $me;
    }
    return $us;
  }
  static function from($o, $pk) {
    $me = new static($o);
    $me->userGroupId = $pk->ugid;
    $me->clientId = $pk->cid;
    $me->ipc = $pk->ipc;
    return $me;
  }
}
class ProcResult_DataOut extends ProcResultRec {
  //
  /**
   * @param object $out {'records':{'ugid|cid|ipc|ipcResult':{'fields':{fid:value,..}}}}
   * @param Proc[ipc] $procs
   */
  static function saveAll_from($out, $procs) {
    $recs = static::fromOut($out, $procs);
    logit_r($recs, 'procresult::fromout');
    static::saveAll($recs);
  }
  static function fromOut($out, $procs) {
    $us = array();
    $seq = 0;
    foreach ($out->records as $pk => $record) {
      $pk = Pk_Result::from($pk);
      $proc = $procs[$pk->ipc];
      $me = static::from($record->fields, $pk, $proc, $seq++);
      $us[] = $me;
    }
    return $us;
  }
  static function from($o, $pk, $proc, $seq) {
    $me = new static($o);
    $me->clientId = $pk->cid;
    $me->ipc = $pk->ipcResult;
    $me->procId = $proc->procId;
    //$me->date = $proc->date;
    $me->seq = $seq;
    return $me;
  }
}
class Pk_Proc {
  //
  public $ugid;
  public $cid;
  public $ipc;
  //
  static function from($string) {
    $pks = explode('|', $string);
    return static::fromArray($pks);
  }
  protected static function fromArray($pks) {
    $me = new static();
    $me->ugid = $pks[0];
    $me->cid = $pks[1];
    $me->ipc = $pks[2];
    return $me;
  }
}
class Pk_Result extends Pk_Proc {
  //
  public $ugid;
  public $cid;
  public $ipc;
  public $ipcResult;
  //
  protected static function fromArray($pks) {
    $me = parent::fromArray($pks);
    $me->ipcResult = $pks[3];
    return $me;
  }
}
//
require_once "php/dao/_util.php";
require_once "php/dao/JsonDao.php";
require_once "php/data/json/JDataMed.php";
require_once "php/data/json/JDataAllergy.php";
require_once "php/data/json/JDataVital.php";
require_once "php/data/json/JDataDiagnosis.php";
require_once "php/data/json/JDataHm.php";
require_once "php/data/json/JDataHist.php";
require_once "php/data/json/JDataImmun.php";
require_once "php/data/json/JDataSyncProcGroup.php";

