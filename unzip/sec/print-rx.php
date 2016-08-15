<? 
require_once "inc/requireLogin.php";
require_once "php/dao/LookupDao.php";
require_once "php/data/rec/sql/MedsLegacy.php";
require_once "inc/uiFunctions.php";

// Gather RX fields from post
if (! isset($_POST["name"])) exit;
$names = $_POST["name"];
$disps = $_POST["disp"];
$refills = $_POST["refill"];
$sigs = $_POST["sig"];
$dnss = getPostedChecks("dns", $names);
$daws = getPostedChecks("daw", $names);
$date = $_POST["date"];
$client = $_POST["client"];
$dob = $_POST["dob"];
$pageLayoutIx = $_POST["pp"];  // T_RX_PAGE_LAYOUT index (0=4-per-page, 1=1-per-page)
$docType = $_POST["doctype"];  // 0=rx, 1=med list, 2=both

// Get header and layout info
$head = new stdClass();
$head->docName = $_POST["rxdocName"];
$head->docLic = $_POST["licLine"];
$head->pracName = $_POST["prac"];
$head->pracAddr = $_POST["addrLine"];
$head->pracPhone = $_POST["phone"];
$layout = LookupDao::getSelRxPageLayout($pageLayoutIx);

// Generate RX pages array
$rxs = array();
foreach ($names as $i => $name) {
  $rxs[] = buildRx($date, $client, $dob, $i, $names, $disps, $sigs, $refills, $dnss, $daws);
}
$pages = buildPages($rxs, $layout->perPage);
function buildRx($date, $client, $dob, $ix, $names, $disps, $sigs, $refills, $dnss, $daws) {
  $rx = new stdClass();
  $rx->date = $date;
  $rx->client = $client;
  $rx->dob = $dob;
  $rx->name = $names[$ix];
  $rx->disp = $disps[$ix];
  $rx->sig = $sigs[$ix];
  $rx->fsig = friendlySig($rx->sig);
  $rx->refill = $refills[$ix];
  $dns = array();
  if ($dnss[$ix]) $dns[] = "Do Not Substitute";
  if ($daws[$ix]) $dns[] = "Dispense As Written"; 
  $rx->dns = implode(" / ", $dns);
  return $rx;
}
function friendlySig($sig) {
  return Med::friendlySig($sig);
}
function buildPages($rxs, $perPage) {
  $pagedRxs = array_chunk($rxs, $perPage);
  $colCt = ($perPage == 1) ? 1 : 2;
  $rowCt = ($perPage == 1) ? 1 : idiv($perPage + 1, 2); 
  $pages = array();
  for ($i = 0; $i < count($pagedRxs); $i++) {
    $page = new stdClass();
    $page->last = ($i == count($pagedRxs) - 1);
    $rows = array_pad(array_chunk($pagedRxs[$i], $colCt), $rowCt, array());
    for ($j = 0; $j < count($rows); $j++) {
      $rows[$j] = array_pad($rows[$j], $colCt, null);
    }
    $page->rows = $rows;
    $pages[] = $page; 
  }
  return $pages;
}
?>
<html>
  <!-- Copyright (c)2006-2009 by LCD Solutions, Inc.  All rights reserved. -->
  <!-- http://www.clicktate.com -->
  <head>
    <title>Clicktate - Medication Print</title>
    <style>
BODY {
  font-family:Arial;
  font-size:10pt;
  margin:0;
}
TD {
  font-size:10pt;
  margin:0;
  padding:0;
}
DIV#body {
  margin-top:<?=$layout->pgTmar ?>in;
  margin-left:<?=$layout->pgLmar ?>in;
}
DIV.brk {
  page-break-after:always;
}
DIV#rx {
  width:350px;
  text-align:left;
  border:1px solid #a0a0a0;
  padding:10px 5px;
  margin-top:<?=$layout->rxTmar ?>in;
  margin-left:<?=$layout->rxLmar ?>in;
}
TD#td1, TD#td3 {
  padding-right:<?=$layout->colSep ?>in;
}
TD#td1, TD#td2 {
  padding-bottom:<?=$layout->rowSep ?>in;
}
DIV#head {
  text-align:center;
}
DIV#medlist {
  text-align:center;
}
DIV#medlist TABLE {
  border:1px solid black;
}
DIV#medlist TH {
  text-align:left;
  padding:6px 10px 6px 6px;
}
DIV#medlist TD {
  border-top:1px solid #c0c0c0;
  padding:6px 10px 6px 6px;
}
DIV#doc {
  margin-bottom:0.2em;
  font-family:Georgia;
  font-size:10pt;
  font-weight:bold;
}
DIV#medlisth {
  margin-top:2em;
  font-family:Georgia;
  font-size:12pt;
  text-decoration:underline;
  font-weight:bold;
}
DIV#medlisthd {
  margin-top:0.5em;
  margin-bottom:1.5em;
  font-family:Arial;
  font-size:8pt;
  font-weight:bold;
}
DIV#practice {
  margin-bottom:2em;
  font-size:8pt;
  line-height:1.4em;
}
UL {
  list-style:none;
  margin-left:20px;
}
LI {
  line-height:1.4em;
}
LI SPAN {
  font-weight:bold;
}
LABEL {
  width:50px;
  font-size:9pt;
}
LABEL#date {
  width:40px;
}
LI#med-name {
  margin-bottom:1.4em;
  margin-left:54px;
}
LI#dns {
  margin:1.4em 0;
}
TD {
  vertical-align:top;
}
SPAN#name {
  width:240px;
}
HR {
  height:1px;
  border:1px dashed #707070;
}
IMG#symbol {
  position:absolute;
  margin-left:5px;
  margin-top:6px;
}
SPAN#symbol {
  font-family:Arial;
  position:absolute;
  margin-top:-10px;
  font-size:55pt;
  color:#797979;
}
DIV#med DIV {
  margin-left:80px;
}
DIV#sig {
  margin:3em 0.5em 1em 0.5em;
}
DIV#sig LABEL {
  width:auto;
}
DIV#sig SPAN {
  width:255px;
  border-bottom:1px solid #707070;
}
    </style>
  </head>
  <body>
    <? if ($docType == 0 || $docType == 2) { ?>
      <? foreach ($pages as $page) { ?>
        <div id="body" class="<?=($page->last && $docType == 0) ? "" : "brk" ?>">
          <table border="0" cellpadding="0" cellspacing="0">
            <? $ix = 1 ?>
            <? foreach ($page->rows as $row) { ?>
              <tr>
                <? foreach ($row as $rx) { ?>
                  <td id="td<?=$ix++ ?>">
                    <? renderRx($head, $rx) ?>
                  </td>
                <? } ?>
              </tr>
            <? } ?>
          </table>
        </div>
      <? } ?>
    <? } ?>
    <? if ($docType >= 1) { ?>
      <div id="body">
        <? renderMedListHead($head, $client) ?>
        <div id="medlist">
          <table border="0" cellpadding="0" cellspacing="0">
            <thead>
              <tr>
                <th>MEDICATION</th>
                <th style="width:60%">DIRECTIONS</th>
              </tr>
            </thead>
            <tbody>
              <? foreach ($rxs as $rx) { ?>
                <? renderMedListRow($rx) ?>
              <? } ?>
            </tbody>
          </table>
        </div>
      </div>
    <? } ?>
  </body>
</html>
<?
function renderMedListHead($head, $client) {
  $today = formatNowInformal();
  echo <<<END
<div id="medlist">
  <div id="doc">
    $head->pracName<br/>
  </div>  
  <div id="practice">
    $head->docName<br/>
    $head->pracAddr<br/>
    $head->pracPhone
  </div>
  <div id="medlisth"> 
    Medication List for $client
  </div>
  <div id="medlisthd">Printed $today</div>
</div>
END;
}
function renderMedListRow($rx) {
  echo <<<END
<tr>
  <td>$rx->name</td>
  <td>$rx->fsig &nbsp;</td>
</tr>
END;
}
function renderRx($head, $rx) {
  if ($rx == null) {
    return;
  }
  echo <<<END
<div id="rx">
  <div id="head">
    <div id="doc">
      $head->docName
    </div>
    <div id="practice">
      $head->pracName<br/>
      $head->pracAddr<br/>
      $head->pracPhone<br/>
      $head->docLic
    </div>
  </div>
  <div id="patient">
    <ul>
      <li>
        <label>Date:</label>
        <span>$rx->date</span>
      </li>
      <li style="padding-top:10px">
        <label>Name:</label>
        <span>$rx->client</span>
      </li>
      <li>
        <label>DOB:</label>
        <span>$rx->dob</span>
      </li>
    </ul>
  </div>
  <hr />
  <div id="med">
    <img id="symbol" src='img/icons/rx.png' />
    <ul>
      <li id="med-name">
        <span>$rx->name</span>
      </li>
      <li>
        <label>Sig:</label>
        <span>$rx->sig</span>
      </li>
      <li>
        <label>Disp:</label>
        <span>$rx->disp</span>
      </li>
      <li>
        <label>Refills:</label>
        <span>$rx->refill</span>
      </li>
      <li id="dns">
        <label>&nbsp;</label>
        <span>$rx->dns</span>
      </li>
    </ul>
  </div>
  <div id="sig">
    <label>Signature</label>
    <span></span>
  </div>
</div>
END;
}
?>
<script>
window.print();
</script>
