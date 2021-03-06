<?
ob_start('ob_gzhandler');
require_once "php/data/LoginSession.php";
require_once "inc/uiFunctions.php";
require_once "php/forms/SchedForm.php";
//
LoginSession::verify_forUser()->requires($login->Role->Patient->sched);
//
$forms = SchedForms::create();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Strict//EN">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <?php renderHead($forms->form->title->text) ?>
    <link rel="stylesheet" type="text/css" href="css/xb/_clicktate.css?<?=Version::getUrlSuffix() ?>" />
    <link rel="stylesheet" type="text/css" href="css/xb/schedule.css?<?=Version::getUrlSuffix() ?>" />
    <link rel="stylesheet" type="text/css" href="css/xb/EntryForm.css?<?=Version::getUrlSuffix() ?>" />
    <link rel="stylesheet" type="text/css" href="css/xb/pop.css?<?=Version::getUrlSuffix() ?>" />
    <link rel="stylesheet" type="text/css" href="css/data-tables.css?<?=Version::getUrlSuffix() ?>" />
    <link rel="stylesheet" type="text/css" href="css/template-pops.css?<?=Version::getUrlSuffix() ?>" />
    <link rel="stylesheet" type="text/css" href="css/xb/_hover.css?<?=Version::getUrlSuffix() ?>" media="screen" />
    <script type='text/javascript' src='js/_lcd_core.js?<?=Version::getUrlSuffix() ?>'></script>
    <script language='JavaScript1.2' src='js/_lcd_html.js?<?=Version::getUrlSuffix() ?>'></script>
    <script type='text/javascript' src='js/components/TableLoader.js?<?=Version::getUrlSuffix() ?>'></script>
    <script language='JavaScript1.2' src='js/libs/DateUi.js?<?=Version::getUrlSuffix() ?>'></script>
    <script language="JavaScript1.2" src="js/libs/AddressUi.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/Pages/Pop.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language='JavaScript1.2' src='js/_ui/PatientSelector.js?<?=Version::getUrlSuffix() ?>'></script>
    <script language='JavaScript1.2' src='js/pops/PatientEditor.js?<?=Version::getUrlSuffix() ?>'></script>
    <script language="JavaScript1.2" src="js/old-ajax.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/yahoo-min.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/json.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/connection-min.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/ui.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language='JavaScript1.2' src='js/components/EntryForm.js?<?=Version::getUrlSuffix() ?>'></script>
    <script language='JavaScript1.2' src='js/components/CmdBar.js?<?=Version::getUrlSuffix() ?>'></script>
    <script language="JavaScript1.2" src="js/new-open.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/custom-schedule.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/schedule.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/components/ProfileLoader.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language='JavaScript1.2' src='js/components/DateInput.js?<?=Version::getUrlSuffix() ?>'></script>
    <style>
@media screen {
  SPAN#printDoc {
    display:none;
  }
}
    </style>
  </head>
  <body>
    <div id="curtain"></div>
    <form id="frm" method="post" action="schedule.php">
      <div id="bodyContainer">
        <?php include "inc/header.php" ?>
        <div id='bodyContent' class="content">
          <table id="shead" border="0" cellpadding="0" cellspacing="0" style="width:100%">
            <tr>
              <td>
                <table border="0" cellpadding="0" cellspacing="0">
                  <tr>
                    <td>
                      <h1>Scheduling</h1>
                    </td>
                    <td width="5"></td>
                    <td>
                      <?php if (count($forms->docs) > 1) {?>
                        <?php renderCombo("doc", $forms->docs, $forms->userId, "onchange='docChange()'") ?>
                      <?php } ?>
                    </td>
                  </tr>
                </table>
              </td>
              <td style="text-align:right; vertical-align:bottom; padding-right:2px">
                <a id="cust1" href="javascript:showCustomProfile()" class="icon big custom">Customize</a>
                &nbsp;
                <a id="sprt" href="javascript:window.print()" class="icon big print">Print</a>
                      <?
//                &nbsp;
//                <a href="javascript:" class="icon big view">Search</a>
?>
              </td>
            </tr>
          </table>
          <?php renderBoxStart("wide small-pad") ?>
            <div style="padding-bottom:5px">
              <table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                  <td width="200">
                    <!-- <span id="printDoc"><?php //=$form->docs[$form->userId] ?></span> -->
                    <?=$forms->form->anchorPrev->html("nav-prev") ?>
                  </td>
                  <td width="*" align="center">
                    <a id="caltitle" href="javascript:Pop.Calendar.show('<?=$forms->form->date ?>', calTitleCallback)" class="<?=$forms->form->title->class ?>"><?=$forms->form->title->text ?></a>
                  </td>
                  <td width="200" align="right">
                    <?=$forms->form->anchorNext->html("nav-next") ?>
                  </td>
                </tr>
              </table>
            </div>
<?
foreach ($forms->forms as $form) {
  echo "<div class='cj'><h2>" . $forms->docs[$form->userId] . "</h2></div>";
?>            
             
            <table class="sched" border="0" cellpadding="0" cellspacing="0">
              <?php if (sizeof($form->columnHeads) == 1) { ?>
                <?php $head = $form->columnHeads[0] ?>
                <tr>
                  <th colspan="6" class="<?=$head->class ?>">
                    <?=$head->anchor->html() ?>
                  </th>
                </tr>
              <?php } else { ?>
                <tr>
                  <td colspan="2"></td>
                  <?php foreach ($form->columnHeads as $head) { ?>
                    <th class="<?=$head->class ?>">
                      <?=$head->anchor->html() ?>
                    </th>
                  <?php } ?>
                </tr>
              <?php } ?>
              <?php foreach ($form->rows as $row) { ?>
                <tr class="<?=$row->trClass ?>">
                  <?php $slot = $row->slot ?>
                  <?php if ($slot->rowSpan > 0) { ?>
                    <th rowspan="<?=$slot->rowSpan ?>" class="hour"><?=$slot->hour ?></th>
                  <?php } ?>
                  <th class="min"><?=$slot->min ?></th>
                  <?php $i = 0 ?>
                  <?php foreach ($row->columns as $column) { ?>
                    <?php if ($column->slot != null) { ?>
                      <?php $slot = $column->slot ?>
                      <?php if ($slot->rowSpan > 0) { ?>
                        <th rowspan="<?=$slot->rowSpan ?>" class="hour"><?=$slot->hour ?><?php if ($slot->amPm == 'PM') echo 'p' ?></th>
                      <?php } ?>
                      <th class="min"><?=$slot->min ?></th>
                    <?php } ?>
                    <?php if ($i == 0 || sizeof($form->columnHeads) > 1) { ?>
                      <?php $head = $form->columnHeads[$i++] ?>
                    <?php } ?>
                    <td class="slot" style="<?=$column->style ?>">
                      <?php if (sizeof($column->appts) > 0) { ?>
                        <?php foreach ($column->appts as $appt) { ?>
                          <a href="javascript:showAppt(<?=$appt->sched->schedId ?>)" class="<?=$appt->aClass ?>" style="<?=$appt->getAStyle() ?>">
                            <?=$appt->aText ?>
                          </a>
                        <?php } ?>
                        &nbsp;
                      <?php } ?>
                        <a href="javascript:addSchedPop('<?=$head->userId ?>', '<?=$head->date ?>', '<?=$slot->formatted ?>')" class="slotAction">
                          <span>Add <?=$slot->hour ?>:<?=$slot->min ?> appt</span>
                        </a>
                    </td>
                  <?php } ?>
                </tr>
              <?php } ?>
            </table>
<?
}
?>            
          </div>
          <?php renderBoxEnd() ?>
        </div>
        <div id='bottom'><img src='img/brb.png' /></div>
      </div>
      <?php include "inc/ajax-pops/new-open.php" ?>
      <?php include "inc/ajax-pops/custom-schedule.php" ?>
      <div id="pop-ue" class="pop">
        <div id="pop-ue-cap" class="pop-cap">
          <div id="pop-ue-cap-text">
            Edit Unavailability
          </div>
          <a href="javascript:Pop.close()" class="pop-close"></a>
        </div>
        <div class="pop-content">
          <div class="pop-frame">
            <h1>Unavailability Event</h1>
            <div class="pop-frame-content">
              <ul class="entry">
                <li>
                  <label class="first">Title</label>
                  <input id="ue-title" type="text" size="60" /> 
                </li>
              </ul>
              <ul class="entry" style="margin-top:10px">
                <li>
                  <label class="first">Date</label>
                  <?php renderCalendar("ue-date") ?>
                  <label>Time</label>
                  <?php renderClock("ue-time") ?>
                </li>
                <li style="padding-top:1px">
                  <label class="first">Duration</label>
                  <?php renderCombo("ue-duration-hr", $forms->form->slotLengthHrs, "", "onchange='resetMin(1)'") ?>
                  <label class="nopad">and</label>
                  <?php renderCombo("ue-duration-min", $forms->form->slotLengthMins) ?>&nbsp;
                  <a class="act" href="javascript:setAllDay()">All day</a>
                </li>
              </ul>
              <ul class="entry" style="margin-top:10px;">
                <li>
                  <label class="first">Repeats</label>
                  <select id="ue-rp-type" onchange="showRepeat(this)">
                    <option value="<?=JSchedEvent::TYPE_NONE ?>">(none)</option>
                    <option value="<?=JSchedEvent::TYPE_DAY ?>">daily</option>
                    <option value="<?=JSchedEvent::TYPE_WEEK ?>">weekly</option>
                    <option value="<?=JSchedEvent::TYPE_MONTH ?>">monthly</option>
                    <option value="<?=JSchedEvent::TYPE_YEAR ?>">annually</option>
                  </select>
                </li>
              </ul>
              <ul id="ue-ul-repeat" class="entry" style="display:none">
                <li>
                  <label class="first">Every</label>
                  <select id="ue-rp-every">
                    <option value="1">single</option>
                    <option value="2">other</option>
                    <option value="3">3rd</option>
                    <option value="4">4th</option>
                    <option value="5">5th</option>
                    <option value="6">6th</option>
                    <option value="7">7th</option>
                    <option value="8">8th</option>
                    <option value="9">9th</option>
                    <option value="10">10th</option>
                    <option value="11">11th</option>
                    <option value="12">12th</option>
                    <option value="13">13th</option>
                    <option value="14">14th</option>
                  </select>
                  <label id="ue-rp-every-label" class="nopad">day(s)</label>
                  <span id="ue-rp-on-span" style="display:none">
                    <label class="nopad">on</label>
                    <?php renderLabelCheck("ue-rp-on-dow", "Sun") ?>
                    <?php renderLabelCheck("ue-rp-on-dow", "Mon") ?>
                    <?php renderLabelCheck("ue-rp-on-dow", "Tue") ?>
                    <?php renderLabelCheck("ue-rp-on-dow", "Wed") ?>
                    <?php renderLabelCheck("ue-rp-on-dow", "Thu") ?>
                    <?php renderLabelCheck("ue-rp-on-dow", "Fri") ?>
                    <?php renderLabelCheck("ue-rp-on-dow", "Sat") ?>
                  </span>
                  <span id="ue-rp-by-span" style="display:none">
                    <label class="nopad">by</label>
                    <select id="ue-rp-by">
                      <option value="<?=JSchedEvent::BY_DAY ?>">day (e.g. every 3rd Wed)</option>
                      <option value="<?=JSchedEvent::BY_DATE ?>">date (e.g. every 15th)</option>
                    </select>
                  </span>
                </li>
                <li>
                  <label class="first">Until</label>
                  <?php renderCalendar("ue-rp-until"); ?>
                </li>
              </ul>
              <ul class="entry" style="margin-top:10px">
                <li>
                  <label class="first">Comments</label>
                  <textarea id="ue-comment" rows="4" cols="90"></textarea>
                </li>
              </ul>
            </div>
          </div>
          <div id="pop-ue-cmd" class="pop-cmd" display="none">
            <a href="javascript:saveSchedEvent()" class="cmd save">Save and Exit</a>
            <span>&nbsp;</span>
            <a href="javascript:deleteSchedEvent()" class="cmd delete">Delete</a>
            <span>&nbsp;</span>
            <a href="javascript:Pop.close()" class="cmd none">&nbsp;Cancel&nbsp;</a>
          </div>
          <div id="working-ue" class="working" style="display:none">
            <div id="working-msg-ue" class="working-msg">
            </div>
          </div>
          <div id="pop-error-ue" class="pop-error" style="display:none">
          </div>
        </div>      
      </div>
      <div id="pop-sc" class="pop">
        <div id="pop-sc-cap" class="pop-cap">
          <div id="pop-sc-cap-text">
          </div>
          <a href="javascript:closeScPop()" class="pop-close"></a>
        </div>
        <div class="pop-content" onkeyup="testScCr()" style="width:520px">
          <label class="section">Patient</label>
          <div id="pop-client" class="ro">
            <div class="ro-title">
              <div>
                <div id="pop-client-name" class="bold"></div>
              </div>
              <?php if ($login->Role->Patient->demo) { ?>
                <a class="pencil" href="javascript:editPatient()">Edit</a>
              <?php } else { ?>
                <a>&nbsp;</a>
              <?php } ?>
            </div>
            <label class="first">ID:</label><span id="pop-client-id"></span>&nbsp;
            <label class='pl10'>DOB:</label><span id="pop-client-dob"></span><br/>
            <label class="first">Address:</label><span id="pop-client-address"></span><br/>
            <label class="first">Phone:</label><span id="pop-client-phone"></span><br/>
          </div>
          <div style="text-align:center; padding-bottom:1em">
            <a href="javascript:goFs()" class="pencil patient">Go to patient's facesheet</a>
            <a id="aChooseAnother" class="pencil patient" href="javascript:schedAnother()">Choose another...</a>
          </div>
          <label class="section">Appointment</label>
          <div id="pop-sched-edit" style="clear:both">
            <ul class="entry">
              <li>
                <label class="first">Date</label>
                <?php renderCalendar("appt-date") ?>
                <label>Time</label>
                <?php renderClock("appt-time") ?>
              </li>
              <li>
                <label class="first">Type</label>
                <?php renderCombo("appt-type", $forms->form->types, "", "onchange='setDuration(this)'") ?>
                <a class="act" href="javascript:showCustomApptTypes()">Customize</a>
              </li>
              <li style="padding-top:1px">
                <label class="first">Duration</label>
                <?php renderCombo("appt-duration-hr", $forms->form->slotLengthHrs, "", "onchange='resetMin()'") ?>
                <label class="nopad">and</label>
                <?php renderCombo("appt-duration-min", $forms->form->slotLengthMins) ?>
              </li>
              <ul class="entry">
                <li>
                  <label class="first">Repeats</label>
                  <select id="ue-rp-type2" onchange="showRepeat(this)">
                    <option value="<?=JSchedEvent::TYPE_NONE ?>">(none)</option>
                    <option value="<?=JSchedEvent::TYPE_DAY ?>">daily</option>
                    <option value="<?=JSchedEvent::TYPE_WEEK ?>">weekly</option>
                    <option value="<?=JSchedEvent::TYPE_MONTH ?>">monthly</option>
                    <option value="<?=JSchedEvent::TYPE_YEAR ?>">annually</option>
                  </select>
                </li>
              </ul>
              <ul id="ue-ul-repeat2" class="entry" style="display:none">
                <li>
                  <label class="first">Every</label>
                  <select id="ue-rp-every2">
                    <option value="1">single</option>
                    <option value="2">other</option>
                    <option value="3">3rd</option>
                    <option value="4">4th</option>
                    <option value="5">5th</option>
                    <option value="6">6th</option>
                    <option value="7">7th</option>
                    <option value="8">8th</option>
                    <option value="9">9th</option>
                    <option value="10">10th</option>
                    <option value="11">11th</option>
                    <option value="12">12th</option>
                    <option value="13">13th</option>
                    <option value="14">14th</option>
                  </select>
                  <label id="ue-rp-every-label2" class="nopad">day(s)</label>
                  <span id="ue-rp-on-span2" style="display:none">
                    <label class="nopad">on</label>
                    <?php renderLabelCheck("ue-rp-on-dow2", "Sun") ?>
                    <?php renderLabelCheck("ue-rp-on-dow2", "Mon") ?>
                    <?php renderLabelCheck("ue-rp-on-dow2", "Tue") ?>
                    <?php renderLabelCheck("ue-rp-on-dow2", "Wed") ?>
                    <?php renderLabelCheck("ue-rp-on-dow2", "Thu") ?>
                    <?php renderLabelCheck("ue-rp-on-dow2", "Fri") ?>
                    <?php renderLabelCheck("ue-rp-on-dow2", "Sat") ?>
                  </span>
                  <span id="ue-rp-by-span2" style="display:none">
                    <label class="nopad">by</label>
                    <select id="ue-rp-by2">
                      <option value="<?=JSchedEvent::BY_DAY ?>">day (e.g. every 3rd Wed)</option>
                      <option value="<?=JSchedEvent::BY_DATE ?>">date (e.g. every 15th)</option>
                    </select>
                  </span>
                </li>
                <li>
                  <label class="first">Until</label>
                  <?php renderCalendar("ue-rp-until2"); ?>
                </li>
              </ul>
            </ul>
            <ul class="entry" style="margin-top:10px;">
              <li>
                <label class="first">Status</label>
                <?php renderCombo("appt-status", $forms->form->statuses) ?>
                <a class="act" href="javascript:showCustomSchedStatus()">Customize</a>
              </li>
              <li>
                <label class="first">Comments</label>
                <textarea id="appt-comment" rows="4" cols="80"></textarea>
              </li>
            </ul>
            <div id="pop-sc-cmd-new" class="pop-cmd" display="none">
              <a href="javascript:saveSched()" class="cmd save">Save and Continue ></a>
              <span>&nbsp;</span>
              <?
              // <a href="javascript:saveSchedAndNote()" class="cmd note">Save and Create Note...</a>
              // <span>&nbsp;</span>
              ?>
              <a href="javascript:closeScPop()" class="cmd none">Cancel</a>
            </div>
            <div id="pop-sc-cmd" class="pop-cmd" display="none">
              <a href="javascript:saveSched()" class="cmd save">Save and Exit</a>
              <span>&nbsp;</span>
              <a href="javascript:deleteSched()" class="cmd delete">Delete</a>
              <span>&nbsp;</span>
              <a href="javascript:cancelSaveSched()" class="cmd none">&nbsp;Cancel&nbsp;</a>
            </div>
          </div>
          <div id="pop-sched-ro" class="ro">
            <div class="ro-title">
              <div class="bold">
                <span id="pop-sched-appt-date"></span>&nbsp;@&nbsp;<span id="pop-sched-appt-time"></span>&nbsp;(<span id="pop-sched-appt-duration"></span>)
              </div>
              <a class="pencil" href="javascript:schedEdit()">Edit</a>
            </div>
            <label class="first">Type:</label><a id="pop-sched-appt-type" href="javascript:schedEdit('appt-type')" class="bold"></a><br/>
            <label class="first">Status:</label><a id="pop-sched-appt-status" href="javascript:schedEdit('appt-status')" class="bold"></a><br/>
            <label class="first">Repeats:</label><span id="pop-sched-appt-repeats"></span><br/>
            <label class="first">Comments:</label><span id="pop-sched-appt-comment"></span>
            <!-- 
            <div style='text-align:right;'>
            <small><label>Created:</label><span id="pop-sched-appt-by" style='font-weight:normal'></span></small>
            </div>
            -->
          </div>
          <div id="pop-notes">
            <!-- <div class="float"> -->
              <div>
                <label class="section">Scheduled/History</label>
              </div>
              <span>
                <?php // renderLabelCheck("htoggle", "Show notes", false, "padding-right:2px", "toggleHistory()") ?>
              </span>
            <!-- </div> -->
            <div id="pss" class="fstab" style="height:180px">
              <table id="pss-table" class="fsw">
              </table>
            </div>
          </div>
          <div id="pop-sc-cmd-ro" class="pop-cmd">
            <a href="javascript:newNotePop()" class="cmd note">New Note...&nbsp;</a>
            <span>&nbsp;</span>
            <a href="javascript:closeScPop()" class="cmd none">&nbsp;Exit&nbsp;</a>
          </div>
          <div id="working-sc" class="working" style="display:none">
            <div id="working-msg-sc" class="working-msg">
            </div>
          </div>
          <div id="pop-info-sc" class="pop-info" style="display:none">
          </div>
          <div id="pop-error-sc" class="pop-error" style="display:none">
          </div>
        </div>
      </div>
      <?php include "inc/footer.php" ?>
    </form>
  </body>
</html>
<script type="text/javascript">
C_Address = <?=Address::getStaticJson()?>;
C_Client = <?=Client::getStaticJson()?>;
C_Docs = <?=UserGroups::getDocsJsonList()?>;
C_Users = <?=UserGroups::getActiveUsersJsonList()?>;
Page.setEvents();
<?php if ($forms->form->popId != null) { ?>
showAppt(<?=$forms->form->popId ?>, <?=$forms->form->popAsEdit ?>);
<?php } ?>
<?php if ($forms->form->popCal) { ?>
Pop.Calendar.show('<?=$forms->form->date ?>', calTitleCallback);
<?php } ?>
<?php if ($forms->form->sid) {  // preload the search results with supplied patient ?>
Ajax.Facesheet.Patients.get(<?=$forms->form->sid ?>, Html.Window, function(client) {
  nnClients = [client];
})
<?php } ?>
<?php timeoutCallbackJs() ?>

var curl = "<?=$forms->form->formatCurrentUrl() ?>";
var curl2 = "<?=$forms->form->formatCurrentUrl(false) ?>";
var curl3 = "<?=$forms->form->formatCurrentUrl(true, false) ?>";
var ugid = <?=$login->userGroupId ?>;
function addSchedPop(u, d, t) {<?php permContinue($login->Role->Patient->sched, "addSchedPop2(u, d, t);") ?>}
function showAppt(i, j) {<?php permContinue($login->Role->Patient->sched, "showAppt2(i,j);") ?>}
var idows = $$("ue-rp-on-dow", "INPUT");
var idows2 = $$("ue-rp-on-dow2", "INPUT");
</script>
