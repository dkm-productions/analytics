<?
set_include_path('../');
require_once "php/data/LoginSession.php";
require_once "inc/uiFunctions.php";
require_once "php/forms/PatientsForm.php";
require_once 'php/data/rec/sql/Clients.php';
//
LoginSession::verify_forUser()->requires($login->Role->Patient->any());
$form = new PatientsForm("patients.php");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Strict//EN">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <?php renderHead("Patient Database") ?>
    <link rel="stylesheet" type="text/css" href="css/xb/_clicktate.css?<?=Version::getUrlSuffix() ?>" />
    <link rel="stylesheet" type="text/css" href="css/xb/Pop.css?<?=Version::getUrlSuffix() ?>" />
    <link rel="stylesheet" type="text/css" href="css/xb/template-pops.css?<?=Version::getUrlSuffix() ?>" />
    <link rel="stylesheet" type="text/css" href="css/data-tables.css?<?=Version::getUrlSuffix() ?>" />
    <link rel="stylesheet" type="text/css" href="css/xb/EntryForm.css?<?=Version::getUrlSuffix() ?>" />
    <link rel="stylesheet" type="text/css" href="css/xb/_hover.css?<?=Version::getUrlSuffix() ?>" media="screen" />
    <script language="JavaScript1.2" src="js/pages/Pop.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/yahoo-min.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/json.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/connection-min.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language='JavaScript1.2' src='js/pops/PatientSelector.js?<?=Version::getUrlSuffix() ?>'></script>
    <script language="JavaScript1.2" src="js/new-open.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/patients.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/components/ProfileLoader.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/libs/DateUi.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/libs/AddressUi.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language='JavaScript1.2' src='js/components/TableLoader.js?<?=Version::getUrlSuffix() ?>'></script>
    <script language='JavaScript1.2' src='js/components/TabBar.js?<?=Version::getUrlSuffix() ?>'></script>
    <script language='JavaScript1.2' src='js/components/CmdBar.js?<?=Version::getUrlSuffix() ?>'></script>
    <script language='JavaScript1.2' src='js/components/EntryForm.js?<?=Version::getUrlSuffix() ?>'></script>
    <script language='JavaScript1.2' src='js/components/DateInput.js?<?=Version::getUrlSuffix() ?>'></script>
    <style>
@media print {
  DIV.gridsheet {
    border:none;
  } 
  DIV.gridsheet TD {
    border:none;
    padding-top:0;
    padding-bottom:0;
  } 
}
    </style>
  </head>
  <body onfocus="pageFocus()">
    <div id="curtain"></div>
    <form id="frm" method="post" action="patients.php">
      <div id="bodyContainer">
        <?php include "inc/header.php" ?>
        <div id='bodyContent' class="content">
          <table class="h">
            <tr>
              <th>
                <h1>Patient Database</h1>
                <?php if ($form->isSearching()) { ?>
                  <div id="searching">
                    <?=$form->searchingText() ?>&nbsp;
                    <a href="patients.php" class="icon big view">Show <b>all patients</b></a>
                  </div>
                <?php } ?>
              </th>
              <td>
                <!-- <a href="javascript:refreshPage()" class="icon big refresh">Refresh page</a> -->
                <a id="sprt" href="javascript:window.print()" class="icon print">Print</a>
                &nbsp;
                <a href="javascript:PatientSelector.pop()" class="icon search">Search for patient</a>
              </td>
            </tr>
          </table>
          <?php renderBoxStart("wide small-pad") ?>
            <div class="nav">
              <table cellpadding="0" cellspacing="0">
                <tr>
                  <td class="prev">
                    <?=$form->prevAnchorHtml() ?>
                  </td>
                  <td class="nav">
                    <?=$form->recordNumbers() ?>
                  </td>
                  <td class="next">
                    <?=$form->nextAnchorHtml() ?>
                  </td>
                </tr>
              </table>
            </div>
            <div class="gridsheet">
              <table>
                <tr>
                  <?=$form->sortableHeader("last_name first_name", "Name") ?>
                  <?=$form->sortableHeader("uid*1", "Patient ID (Birth)") ?>
                  <?=$form->sortableHeader("date", "Most Recent Activity", 2) ?>
                </tr>
                <?php foreach ($form->rows as $row) { ?>
                  <tr class="<?=$row->trClass ?>">
                    <td width="30%" class="last"> 
                      <a href="face.php?id=<?=$row->client->clientId ?>" class="icon <?=echoIf($row->client->sex == Client0::MALE, "umale", "ufemale") ?>">
                        <?=$row->client->name ?>
                      </a>
                    </td>
                    <td width="20%" class="last"><b><?=$row->client->uid ?></b> (<?=substr($row->client->birth, -4) ?>)</td>
                    <td width="10%" class="last"><?=$row->event->futs ?></td>
                    <td width="40%" class="last"><?=$row->eventAnchor ?></td>
                  </tr>
                <?php } ?>
              </table>
            </div>
            <?php if ($login->Role->Patient->create) { ?>
              <div style="padding:10px 0 0 5px; text-align:center" class="noprt">
                <a href="javascript:newPatientPop()" class="cmd new">Create a New Patient...</a>
              </div>
            <?php } ?>
          <?php renderBoxEnd() ?>
        </div>
        <div id='bottom'><img src='img/brb.png' /></div>
      </div>      
      <?php include "inc/ajax-pops/new-open.php" ?>
      <div id="pop-p" class="pop">
        <div id="pop-p-cap" class="pop-cap">
          <div id="pop-p-cap-text">
          </div>
          <a href="javascript:closePPop()" class="pop-close"></a>
        </div>
        <div class="pop-content">
          <label class="section">Patient</label>
          <div id="pop-client" class="ro">
            <div class="ro-title">
              <div>
                <span id="pop-client-name"></span>
              </div>
              <?php if ($login->Role->Patient->demo) { ?>
                <a class="pencil" href="javascript:editPatient()">Edit</a>
              <?php } else { ?>
                <a>&nbsp;</a>
              <?php } ?>
            </div>
            <label class="first">ID:</label><span id="pop-client-id"></span>&nbsp;
            <label>DOB:</label><span id="pop-client-dob"></span><br/>
            <label class="first">Address:</label><span id="pop-client-address"></span><br/>
            <label class="first">Phone:</label><span id="pop-client-phone"></span><br/>
          </div>
          <div id="pop-notes">
            <label class="section">History</label>
            <div id="pss" class="gridsheet small scrollable" style="height:180px; width:500px">
              <table class="small" style="width:482px">
                <tbody id="pss-tbody">
                </tbody>
              </table>
            </div>
          </div>
          <div id="pop-p-cmd" class="pop-cmd">
            <a href="javascript:newNotePop()" class="cmd note">New Note...</a>
            <span>&nbsp;</span>
            <a id="pop-p-exit" href="javascript:" onclick="closePPop(); return false" class="cmd none">&nbsp;Exit&nbsp;</a>
          </div>
          <div id="working-p" class="working" style="display:none">
            <div id="working-msg-p" class="working-msg">
            </div>
          </div>
          <div id="pop-info-p" class="pop-info" style="display:none">
          </div>
          <div id="pop-error-p" class="pop-error" style="display:none">
          </div>
        </div>
      </div>
    <?php include "inc/footer.php" ?>
    </form>
  </body>
<script type="text/javascript">
var C_Client = <?=Client::getStaticJson()?>;
Page.setEvents();
var me = <?=UserDao::getMyUserAsJson() ?>;
<?php timeoutCallbackJs() ?>
var curl = "<?=$form->getCurrentUrl() ?>";
<?php if ($form->popNew) { ?>
newPatientPop();
<?php } else if ($form->popId != null) { ?>
showClient(<?=$form->popId ?>);
<?php } ?>
</script>        
</html>
