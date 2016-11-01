<?php 
require_once "inc/requireLogin.php";
require_once "php/delegates/JsonDelegate.php";
require_once "php/data/Version.php";
require_once 'php/data/rec/sql/OrderEntry.php';
//
$startNewTemplate = false;
try {
  if (isset($_GET['create'])) {
    $sid = SessionDao::addSession(null, $_GET['tid'], $_GET['cid'], $_GET['kid'], $_GET['dos'], $_GET['tpid'], $_GET['st'], $_GET['sid'], $_GET['ovfs']);
    header("Location: new-console.php?sid=$sid");
    exit;
  }
  if (isset($_GET["sid"])) {
    $session = JsonDao::buildJSession($_GET["sid"], true)->out();
    $tpid = "null";
    $tid = "null";
    $tname = "null";
  } else if (isset($_GET["tpid"])) {
    $session = "null";
    $tpid = $_GET["tpid"];
    $tid = "null";
    $tname = "null";
  } else {
    $session = "null";
    $tpid = "null";
    $tid = $_GET["tid"];
    $tname = "'" . $_GET["tname"] . "'";
  }
} catch (SecurityException $e) {
  echo $e->getMessage();
  die;
}
$strict = (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') == false);
if ($strict)
  echo "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.0 Strict//EN'>";
?>
<html>
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
    <title>Clicktate</title>
    <link rel="stylesheet" type="text/css" href="css/xb/_clicktate.css?<?=Version::getUrlSuffix() ?>" />
    <link rel="stylesheet" type="text/css" href="css/xb/console.css?<?=Version::getUrlSuffix() ?>" media="screen" />
    <link rel="stylesheet" type="text/css" href="css/xb/pop.css?<?=Version::getUrlSuffix() ?>" />
    <link rel="stylesheet" type="text/css" href="css/xb/EntryForm.css?<?=Version::getUrlSuffix() ?>" />
    <link rel="stylesheet" type="text/css" href="css/xb/template-pops.css?<?=Version::getUrlSuffix() ?>" media="screen" />
    <link rel="stylesheet" type="text/css" href="css/consolePopIcd.css?<?=Version::getUrlSuffix() ?>" media="screen" />
    <link rel="stylesheet" type="text/css" href="js/_ui/Console.css?<?=Version::getUrlSuffix() ?>" media="screen" />
    <link rel="stylesheet" type="text/css" href="js/_ui/QuestionPop.css?<?=Version::getUrlSuffix() ?>" media="screen" />
    <link rel='stylesheet' type='text/css' href='js/_ui/DocPreview.css?<?=Version::getUrlSuffix() ?>' />
    <link rel='stylesheet' type='text/css' href='js/_ui/ProcResultHistory.css?<?=Version::getUrlSuffix() ?>' />
    <link rel='stylesheet' type='text/css' href='js/_ui/LabMessagePop.css?<?=Version::getUrlSuffix() ?>' />
    <?php if (! get($login->ui, 'tablet')) { ?>
      <link rel="stylesheet" type="text/css" href="css/xb/_hover.css?<?=Version::getUrlSuffix() ?>" />
      <link rel="stylesheet" type="text/css" href="css/xb/console_hover.css?<?=Version::getUrlSuffix() ?>" media="screen" />
    <?php } ?>    
    <script language='JavaScript1.2' src='js/_lcd_core.js?<?=Version::getUrlSuffix() ?>'></script>
    <script language='JavaScript1.2' src='js/_lcd_html.js?<?=Version::getUrlSuffix() ?>'></script>
    <script language="JavaScript1.2" src="js/_ui/Console.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/_ui/QuestionPop.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/_rec/Templates.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/_rec/TemplateMap.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/ui.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/components/TableLoader.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/components/CmdBar.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/pages/Page.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/pages/Includer.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/pages/Pop.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/pages/Ajax.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/pages/NewCrop.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/old-ajax.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/json.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/menu.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/new-open.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/template-pops.js?1<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/icd-pop.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/new-console.js?2<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/engine.js?4<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/engine-download.js?<?=Version::getUrlSuffix() ?>2"></script>
    <script language="JavaScript1.2" src="js/consolePopMed.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/consolePopFree.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/consolePopAllergy.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/consolePopIcd.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/consolePopCombo.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/custom-console.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/pops/OrderSheet.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/yui/yahoo-min.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/yui/event-min.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/yui/connection-min.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/classes/DocFormatter.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/_rec/HtmlPdfDoc.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/libs/DateUi.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/_ui/CcdDownloader.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/_ui/VisitSummaryPop.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/_rec/VisitSummary.js?<?=Version::getUrlSuffix() ?>"></script>
    <script language="JavaScript1.2" src="js/components/EntryForm.js?<?=Version::getUrlSuffix() ?>"></script>
    <script type='text/javascript' src='js/_rec/Facesheet.js?<?=Version::getUrlSuffix() ?>'></script>
    <script type='text/javascript' src='js/_ui/DocPreview.js?<?=Version::getUrlSuffix() ?>'></script>
    <script type='text/javascript' src='js/_ui/ProcResultHistory.js?<?=Version::getUrlSuffix() ?>'></script>
    <script type='text/javascript' src='js/_ui/LabMessagePop.js?<?=Version::getUrlSuffix() ?>'></script>
    <script type='text/javascript' src='js/_ui/DocHistory.js?<?=Version::getUrlSuffix() ?>'></script>
    <script type='text/javascript' src='js/_ui/FacePops.js?<?=Version::getUrlSuffix() ?>'></script>
    <script type='text/javascript' src='js/_ui/Scanning.js?<?=Version::getUrlSuffix() ?>'></script>
    <script type='text/javascript' src='js/_rec/Procedures.js?<?=Version::getUrlSuffix() ?>'></script>
    <script type="text/javascript" src="../tiny_mce/tiny_mce.js"></script>
    <?php if ($login->admin) { ?>
    <style>
DIV.h2 {
  display:block;
  background-color:#c0c0c0;
}
    </style>
    <?php } ?>
  </head>
  <body id="console-body" onscroll="bodyScroll()" onmousedown="bodyMouseDown()" ontouchend="bodyMouseDown()" onorientationchange="resize()" onresize="resize()">
    <div id="curtain" style='visibility:hidden'></div>
    <div id="working"></div>
    <div id="menutool">
      <div id="menubar" onmousedown="menuMouseDown()" onmouseover="menuMouseOver()" onmouseout="menuMouseOut()" onclick="menuClick()">
        <div id="dropmenu-file" class="dropmenu" style="width:180px">
          <ul>
            <li><a href="javascript:" id="actionNew" hidefocus="hidefocus">New Note...</a></li>
            <li><a href="javascript:" id="actionNewCopy" hidefocus="hidefocus">New Replicate</a></li>
            <li><a href="javascript:" id="actionOpen" hidefocus="hidefocus">Open Note...</a></li>
            <li class="break"></li>
            <li><a href="javascript:" id="actionSave" hidefocus="hidefocus">Save</a></li>
            <li><a href="javascript:" id="actionSign" hidefocus="hidefocus" >Sign and Lock</a></li>
            <li><a href="javascript:" id="actionAdd" hidefocus="hidefocus" >New Addendum...</a></li>
            <li class="break"></li>
            <li><a href="javascript:" id="actionOrder" hidefocus="hidefocus" >Generate Orders...</a></li>
            <!-- <li><a href="javascript:" id="actionVisitSum" hidefocus="hidefocus" >Generate Visit Summary...</a></li> -->
            <li><a href="javascript:" id="actionSyndrome" hidefocus="hidefocus" >Syndromic Surveillance...</a></li>
            <li class="break"></li>
            <li><a href="javascript:" id="actionDownloadPdf" hidefocus="hidefocus">Download as PDF</a></li>
            <li><a href="javascript:" id="actionDownload" hidefocus="hidefocus">Download as Word</a></li>
            <li><a href="javascript:" id="actionCopy" hidefocus="hidefocus">Copy to Clipboard</a></li>
            <li class="break"></li>
            <li><a href="javascript:" id="actionDelete" hidefocus="hidefocus">Delete</a></li>
            <li class="break"></li>
            <li><a href="javascript:" id="actionApply" hidefocus="hidefocus">Apply Custom Template...</a></li>
            <li>
              <div id="droprmenu-custom" class="droprmenu" style="left:180px; width:150px">
                <ul>
                  <li><a href="javascript:" id="actionNewTemplate" hidefocus="hidefocus">New Template</a></li>
                  <li><a href="javascript:" id="actionOpenTemplate" hidefocus="hidefocus">Open Template...</a></li>
                  <li><a href="javascript:" id="actionSaveAsTemplate" hidefocus="hidefocus">Save As Template...</a></li>
                </ul>
              </div><a href="javascript:" id="rmenu-custom" class="rmenu" hidefocus="hidefocus">Manage Custom Templates</a>
            </li>
            <li class="break"></li>
            <li><a href="javascript:" id="actionExit" hidefocus="hidefocus">Exit Console</a></li>
          </ul>
        </div><a id="menu-file" href="javascript:" hidefocus="hidefocus">File</a><div id="dropmenu-edit" class="dropmenu">
          <ul>
            <li><a href="javascript:" id="actionClear" hidefocus="hidefocus">Clear</a></li>
            <li class="break"></li>
            <li><a href="javascript:" id="actionEditHeader" hidefocus="hidefocus">Send To...</a></li>
            <li class="break"></li>
            <li><a href="javascript:" id="actionUndo" hidefocus="hidefocus">Undo Last Action</a></li>
            <li><a href="javascript:" id="actionDel" hidefocus="hidefocus">Delete Selected</a></li>
            <li><a href="javascript:" id="actionFree" class="check-off" hidefocus="hidefocus">Freetext Mode</a></li>
            <li class="break"></li>
            <li>
              <div id="droprmenu-view" class="droprmenu">
                <ul>
                  <li><a href="javascript:" id="actionViewMap" class="check-on" hidefocus="hidefocus">Template Map</a></li>
                  <li><a href="javascript:" id="actionViewHeader" class="check-off" hidefocus="hidefocus">Page Header</a></li>
                  <li><a href="javascript:" id="actionViewHistory" class="check-off" hidefocus="hidefocus">Document History</a></li>
                </ul>
              </div><a href="javascript:" id="rmenu-view" class="rmenu" hidefocus="hidefocus">View</a>
            </li>
            <li class="break"></li>
            <li><a href="javascript:" id="actionOptions" hidefocus="hidefocus">Options...</a></li>
          </ul>
        </div><a id="menu-edit" href="javascript:" hidefocus="hidefocus">Edit</a><div id="dropmenu-help" class="dropmenu">
          <ul>
            <li><a href="javascript:" id="actionManual" hidefocus="hidefocus">Download User Manual</a></li>
          </ul>
        </div><a id="menu-help" href="javascript:" hidefocus="hidefocus">Help</a><?php if ($login->admin) { ?><div id="dropmenu-admin" class="dropmenu">
          <ul>
            <li><a href="javascript:" id="actionViewTags" class="check-off" hidefocus="hidefocus">Show ID Tags</a></li>
            <li class="break"></li>
            <li><a href="javascript:" id="actionDebug" hidefocus="hidefocus">Debug Now</a></li>
            <li class="break"></li>
            <li><a href="javascript:" id="actionActions" hidefocus="hidefocus">View Actions</a></li>
            <li><a href="javascript:" id="actionHtml" hidefocus="hidefocus">View HTML</a></li>
            <li><a href="javascript:" id="actionTimer" hidefocus="hidefocus">View Timer Log</a></li>
            <li class="break"></li>
            <li><a href="javascript:" id="actionTabletResize" hidefocus="hidefocus">Resize as Tablet</a></li>
          </ul>
        </div><a id="menu-admin" href="javascript:" hidefocus="hidefocus">Admin</a><?php } ?>
      </div>
      <div id="toolbar" onmousedown="toolPush()" onmouseup="toolRelease()" onmouseout="toolRelease()" onclick="toolClick()">
      <!-- 
        <span class='sep'>
        <a href="javascript:" id="actionNew" hidefocus="hidefocus" title="Create a new note">New</a><a href="javascript:" id="actionOpen" hidefocus="hidefocus" title="Open another note">Open</a>
        </span>
      -->
        <span class='sep'>
        <a href="javascript:" id="actionSave" hidefocus="hidefocus" title="Save note to server">Save As</a><a href="javascript:" id="actionSign" hidefocus="hidefocus" title="Sign and lock note from further changes">Sign/Lock</a><a href="javascript:" id="actionAdd" hidefocus="hidefocus" style="display:none" title="Add addendum to signed note">Addendum</a>
        </span>
        <span class='sep'>
        <a href="javascript:" id="actionOrder" hidefocus="hidefocus" title="Generate order sheet">Orders</a><!-- <a href="javascript:" id="actionVisitSum" hidefocus="hidefocus" title="Generate patient visit summary">Visit Summary</a> -->
        </span>
        <span class='sep'>
        <a href="javascript:" id="actionDownloadPdf" hidefocus="hidefocus" title="Download note as PDF document">Download</a><?php if (1==2) {?><a href="javascript:" id="actionDownload" hidefocus="hidefocus" title="Download note as Word document">Download</a><?php } ?><a href="javascript:" id="actionCopy" hidefocus="hidefocus" title="Copy note to clipboard">Clipboard</a>
        </span>
        <span class='sep'>
        <a href="javascript:" id="actionClear" hidefocus="hidefocus" title="Clear document">Clear</a>
        </span>
        <span class='sep'>
        <a href="javascript:" id="actionUndo" hidefocus="hidefocus" title="Undo last action">Undo</a><a href="javascript:" id="actionDel" hidefocus="hidefocus" title="Delete selected text">Delete</a><a href="javascript:" id="actionFree" hidefocus="hidefocus" title="Toggle freetext tags on/off">Freetext</a>
        <?php if (1==2 && $login->admin) { ?>
          <span id="admin">
            <span class="sep"></span>
            <a href="javascript:" id="actionViewTags" class="check-off" class="on" hidefocus="hidefocus">Tags</a><a href="javascript:" id="actionActions" hidefocus="hidefocus">Actions</a><a href="javascript:" id="actionHtml" hidefocus="hidefocus">HTML</a><a href="javascript:" id="actionTimer" hidefocus="hidefocus">Log</a>
          </span>
        <?php } ?>
        </span>
        <span>
        <a href="javascript:" id="actionExit" hidefocus="hidefocus" title="Exit console">Exit Console</a>
        </span>
      </div>
    </div>
    <div id="bodyCont">
      <div id="body">
        <table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
          <tr>
            <td style="vertical-align:top">
              <div id="templatemap">
                <div id="templatemap-shrunk" style="display:none">
                  <div class="captionbar">
                    <a href="javascript:expandMap()" class="ctl expand"></a>                
                  </div>
                </div>
                <div id="templatemap-expanded">
                  <div class="captionbar">
                    <a href="javascript:shrinkMap()" class="ctl shrink"></a>                
                    Template Map <a id="ctCustom" href="javascript:showCustomTemplateMap()" class="acth customize">Customize</a>
                  </div>
                  <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td style="width:55px; vertical-align:top">
                        <div id="sectionlist">
                          <a style="display:none" id="search-map" href="javascript:" title="Search for a diagnosis..."></a>
                          <ul id="sectionsul">
                          </ul>  
                        </div>
                      </td>
                      <td style="vertical-align:top">
                        <div id="parlistc">
                          <div id="tsearch-div">
                            <div id="tsearch-box">
                              <table cellpadding=0 cellspacing=0>
                                <tr>
                                  <td>
                                    <input id="tsearch" type="text" size="1" onfocus="focusSearch()" onblur="resetSearch()" onkeyup="ifCrClick('tsearch-a')" />
                                  </td>
                                  <td style='text-align:right;'>
                                    <a id="tsearch-a" href="javascript:" onclick="searchTemplate()"></a>
                                  </td>
                                </tr>
                              </table>
                            </div>
                          </div>
                          <div id="partitle" style="border-bottom:1px solid #808080;" class="captionbar par">
                            &nbsp;
                          </div>
                          <table border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                              <td style="vertical-align:top">
                                <div id="parindex">
                                </div>
                              </td>
                              <td style="vertical-align:top; width:100%;">
                                <div id="parlist" onmouseover="javascript:parlist_onmouseover()">
                                </div>
                                <div id="usedlist">
                                  <ul id="usedlistul" class="parlistul">
                                    <li class="section">In Note</li>
                                    <li id='usedli'>
                                    </li>
                                  </ul>
                                </div>
                              </td>
                            </tr>
                          </table>
                        </div>
                      </td>
                    </tr>
                  </table>
                </div>
              </div>
            </td>
            <td style="width:100%; vertical-align:top;">
              <div id="docc">
                <div id="doccap" class="captionbar">
                </div>
                <div id="doccaph" class="doccapsm">
                  &nbsp; 
                </div>
                <div id="docw">
                  <div id="doc"></div>
                </div>
              </div>
            </td>
          </tr>
        </table>
      </div>
    </div>
    <div id="sb" class="doccapsm">
    </div>
    <div id="pop-add" class="pop" onmousedown="event.cancelBubble = true">
      <div id="pop-add-cap" class="pop-cap">  
        <div id="pop-add-cap-text">
          Addendum
        </div>
        <a href="javascript:Pop.close()" class="pop-close"></a>
      </div>
      <div class="pop-content" style="padding:10px 10px 8px 10px">
        <textarea id="add-text" rows="14"></textarea>
        <div class="pop-cmd">
          <a class="cmd save" href="javascript:" onclick='addendumSave()'>Save Addendum</a>
          <span>&nbsp;</span>
          <a class="cmd none" href="javascript:" onclick='Pop.close()'>Cancel</a>
        </div>
      </div>
    </div>
    <div id="pop-aerr" class="pop" onmousedown="event.cancelBubble = true">
      <div id="pop-aerr-cap" class="pop-cap">  
        <div>
          Document Restore Errors
        </div>
        <a href="javascript:Pop.close()" class="pop-close"></a>
      </div>
      <div class="pop-content important" style="width:500px">
        <b>The console was unable to perform the following actions to this document:</b> 
        <div id="pop-aerr-errors" class="scrollform" style="height:150px; margin:5px 0;padding:5px">
        </div>
        You can try reloading the document to see if that corrects the problem, or ignore these to open the document now.
        <div class="pop-cmd">
          <a class="cmd none" href="javascript:" onclick='aerrReload()'>Reload Document</a>
          <span>&nbsp;</span>
          <a class="cmd none" href="javascript:" onclick='Pop.close()'>Ignore and Open</a>
        </div>
      </div>
    </div>
    <div id="pop-hm" class="pop" onmousedown="event.cancelBubble = true">
      <div id="pop-hm-cap" class="pop-cap">  
        <div>
          Database Selector
        </div>
        <a href="javascript:Pop.close()" class="pop-close"></a>
      </div>
      <div class="pop-content" style="width:750px">
        <div id="hm-div" class="fstab" style="height:400px"> 
          <table class="fsg">
            <tbody id="hm-tbody">
            </tbody>
          </table>
        </div>
        <div class="pop-cmd">
          <a class="cmd ok" style='width:auto' href="javascript:" onclick='hmInsert()'>Insert Checked</a>
          <span>&nbsp;</span>
          <a class="cmd none" href="javascript:" onclick='Pop.close()'>Cancel</a>
        </div>
      </div>
    </div>
    <?php include "inc/ajax-pops/template-pops.php" ?>
    <?php include "inc/ajax-pops/icd-pop.php" ?>
    <?php include "inc/ajax-pops/new-open.php" ?>
    <?php include "inc/ajax-pops/custom-console.php" ?>
    <?php include "inc/ajax-pops/template-explorer.php" ?>
    <?php include "js/pops/inc/OrderSheet.php" ?>
    <form style="margin:0" id="docForm" method="post" action="serverDoc<?php if ($login->userGroupId == 24) { ?>_Lidagoster<?php } ?>.php">
      <input id="docDos" type="hidden" name="docDos" />
      <input id="docText" type="hidden" name="doc" />
      <input id="docHead" type="hidden" name="head" />
      <input id="docName" type="hidden" name="cn" />
      <input id="docFmt" type="hidden" name="fmt" />
      <input id="docImg" type="hidden" name="img" />
      <input id="docImg2" type="hidden" name="img2" />
      <input id="docSigImg" type="hidden" name="sigimg" />
      <input id="docSigName" type="hidden" name="signame" />
      <input id="docNoTag" type="hidden" name="noTag" />
      <input id="docLeftHead" type="hidden" name="lefthead" />
    </form>
    <button id="but" style="display:none"></button>
    <?php if ($login->admin) { ?>
      <?php include "inc/ajax-pops/debug.php" ?>
    <?php } ?>
<div id='pop-ss' class='pop' onmousedown='event.cancelBubble=true' style='width:300px'>
  <div id='pop-ss-cap' class='pop-cap'>
    <div>
      Clicktate - Generate Syndromic Surveillance
    </div>
    <a href='javascript:SyndromePop.close()' class='pop-close'></a>
  </div>
  <div class='pop-content'>
    <ul class='entry'>
      <li>
        <label class='first'>Message Type</label>
        <select id='ss-type'>
          <option value='A04'>Patient Registration</option>
          <option value='A03'>Patient Discharge</option>
        </select>
      </li>
    </ul>
    <div class='pop-cmd'>
      <a href="javascript:" onclick="SyndromePop.download()" class="cmd save">Generate...</a>
      <span>&nbsp;</span>
      <a href='javascript:' onclick='SyndromePop.close()' class='cmd none'>Cancel</a>
    </div>
  </div>
</div>    
    <div id="page-includes"></div>
  </body>
</html>
<?php CONSTANTS('Templates') ?>
<?php JsonConstants::writeGlobals('Address','Client','PortalUser','DocStub','ScanIndex','MsgInbox','MsgPost','MsgThread') ?>
<script>
//window.moveTo(0,0);
//window.resizeTo(screen.width,screen.height);
var C_TrackItem = <?=TrackItem::getStaticJson() ?>;
var today = "<?=date("m/d/Y", strtotimeAdjusted(nowTimestamp())) ?>";
var me = <?=$login->asJson() ?>;
me.isErx = function() {return me.User.UserGroup.usageLevel == 2};
resize();
document.onmousedown = mouseDown;
$("doc").onmousedown = docMouseDown;
$("doc").onmouseup = docMouseUp;
$("doc").onmousemove = docMouseMove;
focus("menu-file");
var docs = <?=UserDao::getDocsOfGroupAsJson() ?>;
var dm = {down:0, selecting:0};
var dsel = {
    down:false,      // true if mousedown
    x:0,y:0,         // mouse coords of mousedown start
    selecting:false, // true if mousedown and mousemove past threshhold
    p:null,          // <par> of selection
    dqDrag:null,     // dq (qid) that initiated drag
    dqOn:null,       // dq (qid) currently moused over
    start:null,      // start <span> of selection  
    end:null};       // end <span> of selection
document.onselectstart = testSelect;
//document.onkeyup = onKeyUp;
function onKeyUp() {
  switch (event.keyCode) {
    case 46:
      actionDel();
      event.cancelBubble = true;
      return false;
  }
}
function mouseDown() {
  dm.selecting = false;
  if (dsel.p) {
    selectDels();
  }
}
function docMouseDown() {
  dm.x = event.screenX;
  dm.y = event.screenY;
  dm.down = true;
  dm.selecting = false;
}
function docMouseUp() {
  dm.selecting = false;
  dm.down = false;
}
function dselClear() {
  if (dsel.p) {
    dsel.p.className = "";
  }
  dsel.p = null;
  disable("actionDel");
}
function docMouseMove() {
  if (! dm.down) return;
  var button = event.which || event.button;
  if (button == 0) {
    docMouseUp();
    return;
  }
  if (! dm.selecting) {
    //if (Math.abs(dm.x - event.screenX) > 3 || Math.abs(dm.y - event.screenY) > 3) { 
      docStartSelect();
    //}
    return;
  }
  var e = event.srcElement;
  var del = getMouseOverDel(e);
  if (del) {
    if (del.dq != dsel.dqOn) {
      selectDels(del);
      dsel.dqOn = del.dq;
    }
  } else {
    if (e.dqDrag != dsel.dqDrag) {
      dsel.p.className = "dsel";
      selectDels(dsel.p);
    } else {
      dsel.p.className = "";
    }
  }
}
function docStartSelect() {  // currently not checking for a mousemove threshhold, i.e. distance from (dm.x,dm.y)
  var del = getMouseOverDel(event.srcElement);
  if (del) {
    enable("actionDel");
    dm.selecting = true;
    dsel.p = del.parentElement;
    dsel.p.dqDrag = del.dq;
    dsel.dqDrag = del.dq;
    dsel.dqOn = del.dq;
    selectDels(del);
  }
}
function selectDels(e) {  // pass <span> to select range, pass null to deselect all, <p> to select all
  var selecting = false;
  var all = e && e.tagName == "P";
  dsel.start = null;
  dsel.end = null;
  if (dsel.p) {
    for (var i = 0; i < dsel.p.children.length; i++) {
      var del = dsel.p.children[i];
      del.dq = del.getAttribute('dq');
      if (del.dq && del.className != "del") {
        var match = (all) ? true : (e) ? del.dq == dsel.dqDrag || del.dq == e.dq : false;
        if (match || selecting) { 
          del.className = "dsel";
          dsel.end = del;
          if (dsel.start == null) {
            dsel.start = del;
          }
        } else {
          del.className = "dunsel";
        }
        if (e && match && dsel.dqDrag != e.dq) {
          selecting = ! selecting;
        } 
      }
    }
  }
  if (! e) {
    dselClear();
  }
}
function getMouseOverDel(e) {
  while (e) {
    if (e.getAttribute('dq')) {
      e.dq = e.getAttribute('dq');
      return e;
    } else {
      e = e.parentElement;
      if (! e || e.id == "doc") {
        return null;
      }
    }
  }
}
function actionDel() {
  if (dsel.p) {
    docDel(dsel.start.id, dsel.end.id);
    dselClear();
  }
}

// Ajax.setWorkingCallback(workingCallback); 
setWorkingCallback(workingCallback);
var lu_custom = <?=LookupDao::getPrintCustomAsJson() ?>;
var lu_console = <?=LookupDao::getConsoleCustomAsJson() ?>;  
var lu_tcustoms = <?=LookupDao::getAllTemplateCustomsAsJson() ?>;
var lu_dtabs = <?=DataDao::getOutDataTables(true) ?>;
var lu_rx = <?=LookupDao::getConsoleRxAsJson() ?>;
var perm = {
    "ugid":<?=$login->userGroupId ?>,
    "on":<?=toString($login->Role->Artifact->noteCreate) ?>,
    "mn":<?=toString($login->Role->Artifact->noteRead) ?>,
    "sn":<?=toString($login->Role->Artifact->noteSign) ?>,
    "t":<?=toString($login->Role->Artifact->templates) ?>,
    "yk":<?=toString($login->admin) ?>,
    "er":<?=toString($login->isErx()) ?>,
    "pap":<?=toString($login->isPapyrus()) ?>,
    "ro":<?=toString(! $login->Role->Artifact->noteCreate) ?>};
var toolUndo = menu$$("actionUndo")[1];
<?php if (! $login->User->isDoctor()) { ?>
disable("actionSign");
disable("actionDelete");
<?php } ?>
resetSearch();
//Html.Window.scrollable = function() {}
doWork("startup()", "Initializing", null, true);
function startup() {
  var session = <?=$session ?>;
  var tpid = <?=$tpid ?>;
  var tid = <?=$tid ?>;
  var tname = <?=$tname ?>;
  initConsole(session, tpid, tid, tname);
}
//setInterval("autosave()", 1000); 
function bodyScroll() {
  scroll(0, 0);
}
<?php if ($login->admin) { ?>
document.styleSheets[document.styleSheets.length - 1].disabled = true;
function actionHtml() { 
  setText("pop-debug-content", $("doc").innerHTML);
  Pop.show("pop-debug", null, null, null, true, true);
}
function actionTest() {
  //clearDebug();
  //setText("pop-debug-content", DocFormatter.consoleToHtml());
  //Pop.show("pop-debug", null, null, null, true, true);
}
function actionActions() {
  clearDebug();
  for (var i = 0; i < actions.stack.length; i++) {
    addDebug("[" + (" " + i).slice(-2) + "]: " + actions.stack[i]);
  }
  addDebug("");
  addDebug(toJSONString(actions.stack));
  addDebug("");
  addDebug('undos: ' + toJSONString(actions.undos));
  addDebug('undoStack:' + toJSONString(actions.undoStack));
  Pop.show("pop-debug", null, null, null, true, true);
}
function clearDebug() {
  setText("pop-debug-content", "");
}
function addDebug(line) {
  var h = $("pop-debug-content").innerHTML;
  setHtml("pop-debug-content", h + line + "<br/>");
}
function actionTabletResize() {
  window.resizeTo(768,1024);
  hide("admin");
  resize();
}
function actionViewTags() {
  var checked = menuToggleCheck(); 
  lu_console.showAdminTags = checked;
  saveConsoleCustom();
  doHourglass("showHideTags()");
}
function showHideTags(show) {  // show optional
  if (show == null) {
    show = isMenuChecked$("actionViewTags")
  } else {
    menuSetCheck$("actionViewTags", show);
  }
  document.styleSheets[document.styleSheets.length - 1].disabled = ! show;
  showAllIf(show, "adminp"); 
  showAllIf(show, "adminq");
  closeHourglass(); 
}
function showAllIf(cond, id) {
  var es = $$(id);
  var s = (cond) ? "inline" : "none";
  for (var i = 0; i < es.length; i++) {
    es[i].style.display = s;
  }
  return es;
}
var timer;
var tcum;
var tmsgs = [];
resetTimer();
function actionTimer() {
  var html = "<table>" + tmsgs.join("") + "</table>";
  $("pop-timer-content").innerHTML = html;
  Pop.show("pop-timer", null, null, null, true, true);
}
function resetTimer() {
  timer = new Date();
  tcum = 0;
  setHtml("pop-timer-content", "");
  tlog("=== STARTING TIMER @ " + timer + " ===", true);
}
function cloga(method, args, comment) {
  var s = "<b>" + method + "</b>" + logJoin(args);
  if (comment) {
    s += " <em>// " + comment + "</em>";
  } 
  clog(s, 1);
}
function clog(msg, sp) {
  if (sp == null) {
    msg = "&nbsp;&nbsp;&nbsp;" + msg;
  } else if (sp == 2) {
    msg = "<span style='color:green'>" + msg + "</span>";
  } else if (sp == 3) {
    msg = "<span style='color:blue'>" + msg + "</span>";
  } else if (sp == 4) {
    msg = "<span style='color:red'>" + msg + "</span>";
  }
  var tr = "<tr><td class='ctime'>" + ctimer() + "</td><td>" + msg + "</td></tr>";
  tmsgs.push(tr);
}
function ctimer() {
  var e = (new Date() - timer) / 1000;
  var ec = e - tcum;
  var s = (ec > 2) ? "<span style='background-color:yellow;color:red'>" : "<span>";
  tcum = e;
  return s + ("000" + e.toFixed(2)).slice(-6) + "+" + ("      " + ec.toFixed(2)).slice(-7) + "</span>: ";  
}
function tlog(msg, startProc) {
  return;
  var e = (new Date() - timer) / 1000;
  var ec = e - tcum;
  tcum = e;
  if (startProc) {
    msg = "<b>" + msg + "</b>";
  } else {
    msg = "---" + msg;
  }
  tmsgs += ("000" + e.toFixed(2)).slice(-6) + " +" + ("   " + ec.toFixed(2)).slice(-6) + "): " + msg + "<br/>";
}
<?php } else { ?>
function cloga() {}
function clog() {}
function tlog() {}
<?php } ?>
function parlist_onmouseover() {
  var e = event.srcElement;
  if (e && e.tagName == 'A')
    e = e.parentElement;
  if (e && e.getAttribute('suid')) {
    var par = map.getPar(e.getAttribute('suid'), e.getAttribute('puid'));
    var pos = getPosA(e);
    ParPreviewTrigger.on(pos, par);
  } else {
    ParPreviewTrigger.off();
  }
}
function getPosA(a) {
  var pos = getDimensions(a);
  pos.top = pos.top - _$('parlist').scrollTop;
  pos.left = pos.left + _$('parlist').getWidth() - 40;
  if (me.tab)
    pos.left = pos.left + 20;
  //pos.left = pos.left + 175;
  return pos;
}
/**
 * Track Item
 */
TrackItem = Object.Rec.extend({
})
//
TrackItems = Object.RecArray.of(TrackItem, {
})

</script>