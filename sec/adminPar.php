<?php
require_once "php/data/LoginSession.php";
require_once "inc/noCache.php";
require_once "php/dao/TemplateAdminDao.php";
require_once "php/dao/JsonDao.php";
require_once "php/forms/ParForm.php";
require_once "inc/uiFunctions.php";
//
LoginSession::verify_forUser()->requires($login->admin);
$form = new ParForm();
$jscript = "";
$getId = isset($_GET["id"]) ? $_GET["id"] : null;
if (isset($getId)) {

	// Page entry from link
	if ($getId != "") {
		try {
			$par = TemplateAdminDao::getPar($getId, true);
			$form->setFromDatabase($par);
			$form->templateId = $_GET["tid"];
		} catch (SecurityException $e) {
			die($e->getMessage());
		}
	} else {
		$form->templateId = $_GET["tid"];
		$form->sectionId = $_GET["sid"];
	}
  if (isset($_GET["m"])) {
    $msg = $_GET["m"];
  }
} else {

	// Page submitted from action
	$form->templateId = $_POST["templateId"];
	$form->sectionId = $_POST["sectionId"];
	$id = isset($_POST["id"]) ? $_POST["id"] : null;
	try {
		$action = isset($_POST["action"]) ? $_POST["action"] : null;
		if ($action == " Exit ") {
		  $url = "Location: adminSection.php?id=" . $_POST["sectionId"];
		  if ($id) {
		    $url .= "&fid=" . $id;
		  }
			header($url);
			exit;
		}
		if ($action == "Add New") {
			header("Location: adminQuestion.php?id=&pid=" . $_POST["id"] . "&sid=" . $_POST["sectionId"] . "&tid=" . $_POST["templateId"]);
			exit;
		}
		if ($action == "Delete Paragraph") {
			TemplateAdminDao::deletePar($_POST["id"]);
			$form->sectionId = $_POST["sectionId"];
			$msg = "Paragraph deleted.";
		} else if ($action == "Copy New Version") {
		  $pid = TemplateAdminDao::newVersionPar($_POST["id"]);
		  $msg = "Copy successful. You are now editing new version.";
      header("Location: adminPar.php?id=" . $pid . "&sid=" . $_POST["sectionId"] . "&tid=" . $_POST["templateId"] . "&m=" . $msg);
		} else {
			$form->setFromPost();
			$form->validate();
			$par = $form->buildPar();
			if ($par->id == "") {
				$form->id = TemplateAdminDao::addPar($par);
				$msg = "Paragraph added.";
			} else {
				TemplateAdminDao::deleteParJson($par->id);
				TemplateAdminDao::updatePar($par);
				$msg = "Paragraph updated.";
			}
			if ($action == "Publish") {
				//JsonDao::buildJParInfos($par->id);
				//$msg = "Paragraph published.";
			}
			if (isset($_POST["copyValues"]) && ! isNull($_POST["copyValues"])) {
				$count = TemplateAdminDao::copyQuestions($par, $_POST["copyValues"]);
				$msg = "Selected questions copied to paragraph: " . $count . " total.";
				$jscript = "list.scrollTop = list.scrollHeight";
			}
			if ($action == "Delete Checked") {
				$count = count($_POST["del"]);
				if ($count > 0) {
					TemplateAdminDao::deleteQuestions($_POST["del"]);
				}
				$msg = "Selected questions deleted: " . count($_POST["del"]) . " total.";
			}
			$par = TemplateAdminDao::getPar($form->id, true);
			$form->setFromDatabase($par);
		}
	} catch (ValidationException $e) {
		$errors = $e->getErrors();
	} catch (DuplicateInsertException $e) {
		$msg = null;
		$errors = singleError(null, "A question cannot be copied; question ID already exists in paragraph.");
		$par = TemplateAdminDao::getPar($form->id, true);
		$form->setFromDatabase($par);
	} catch (SecurityException $e) {
		die($e->getMessage());
	}
}
$update = (! isBlank($form->id));
$form->breadcrumb = TemplateAdminDao::buildParBreadcrumb($form->sectionId);
$form->sortOrders = TemplateAdminDao::buildParSortCombo($form->sectionId, $form->id);
$form->sortOrder = TemplateAdminDao::denullifySortOrder($form->sortOrders, $form->sortOrder);
$focus = "uid";
?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <!-- Copyright (c)2006 by LCD Solutions, Inc.  All rights reserved. -->
  <!-- http://www.clicktate.com -->
  <head>
    <title>clicktate : Paragraph</title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <meta http-equiv="Content-Script-Type" content="text/javascript" />
    <meta http-equiv="Content-Language" content="en-us" />
    <link rel="stylesheet" type="text/css" href="css/med.css?1" media="screen" />
    <script language="JavaScript1.2" src="js/admin.js"></script>
  </head>
  <body onunload="closePopCopyQ()">
    <?php include "inc/banner.php" ?>
    <form method="post" action="adminPar.php">
      <input type="hidden" name="id" value="<?=$form->id ?>">
      <input type="hidden" name="templateId" value="<?=$form->templateId ?>">
      <input type="hidden" name="sectionId" value="<?=$form->sectionId ?>">
      <input type="hidden" name="sortOrder" value="<?=$form->sortOrder ?>">
      <?php require_once "inc/errors.php" ?>
      <div id="breadcrumb">
        <?=$form->breadcrumb ?><br>
        <h1><?=($update) ? "" : "New " ?>Paragraph <?=$form->uid ?></h1>
      </div>
      <div class="action">
        <input type="submit" value=" Save ">
        <?php if ($update) { ?>
          <?php // <input type="submit" name="action" value="Publish"> ?>
          <?php // <span></span> ?>
          <input type="submit" name="action" value="Copy New Version" onclick="if (versionCancelled()) return false">
          <?php if (isset($form->questions) && sizeof($form->questions) == 0) { ?>
            <span></span>
            <input type="submit" name="action" value="Delete Paragraph" onclick="if (deleteCancelled()) return false">
          <?php } ?>
        <?php } ?>
        <span></span>
        <input type="submit" name="action" value=" Exit ">
        <?php if ($update) { ?>
          <span></span>
          &nbsp;&nbsp;&nbsp;
          <a style="font-size:9pt;font-weight:bold" href="javascript:showPopAdmin('testActionSearch','<?=$form->id ?>')">Referenced by?</a>
          &nbsp;|&nbsp;
          <a style="font-size:9pt;font-weight:bold" href="javascript:showPopCinfo('<?=$form->id ?>','<?=$form->desc?>')">Clinical Info</a>
        <?php } ?>
      </div>
      <div class="roundBox">
        <div class="roundTop"><img src="img/tl.gif"></div>
        <div class="roundContent">
          <table border=0 cellpadding=0 cellspacing=0>
            <tr>
              <td class="label first">Paragraph ID</td>
              <td width=5></td>
              <td><input type="text" size="15" name="uid" value="<?=$form->uid ?>"></td>
              <td width=15></td>
              <td class="label">Done?</td>
              <td width=5></td>
              <td><input type="checkbox" name="noBreak" value="Y" <?=checkedIf($form->noBreak) ?>></td>
              <td width=15></td>
              <td class="label">Major?</td>
              <td width=5></td>
              <td><input type="checkbox" name="major" value="Y" <?=checkedIf($form->major) ?>></td>
              <td width=15></td>
              <td class="label">Hidden?</td>
              <td width=5></td>
              <td><input type="checkbox" name="injectOnly" value="Y" <?=checkedIf($form->injectOnly) ?>></td>
              <td width=15></td>
              <td class="label">In DEV?</td>
              <td width=5></td>
              <td><input type="checkbox" name="" value="Y" <?=checkedIf($form->dev) ?>></td>
              <td width=15></td>
              <td class="label">Sort Position</td>
              <td width=5></td>
              <td>
                <?php renderCombo("sortOrder", $form->sortOrders, $form->sortOrder) ?>
              </td>
            </tr>
          </table>
          <table border=0 cellpadding=0 cellspacing=0 width="95%">
            <tr>
              <td class="label first" valign="top" style="padding-top:5px">Description</td>
              <td width=5></td>
              <td><textarea name="desc"><?=$form->desc ?></textarea></td>
            </tr>
          </table>
          <table border=0 cellpadding=0 cellspacing=0>
            <tr>
              <td class="label first" nowrap style="vertical-align:top;padding-top:5px">In Data</td>
              <td width=5 nowrap></td>
              <td style="vertical-align:top;padding-top:1px">
                <?php renderCombo("inTable", $form->dataTables, $form->inTable, "onchange=\"makeDirty()\"") ?>
              </td>
              <td width=5 nowrap></td>
              <td style="vertical-align:top;padding-top:1px">
                <?php renderCombo("inType", $form->inTypes, $form->inType, "onchange=\"makeDirty()\"") ?>
              </td>
            </tr>
          </table>
          <table border=0 cellpadding=0 cellspacing=0 width="95%">
            <tr>
              <td class="label first" nowrap style="vertical-align:top;padding-top:5px">In Data Cond</td>
              <td width=5 nowrap></td>
              <td width=100%><textarea name="inCond" onchange="makeDirty()"><?=$form->inCond ?></textarea></td>
            </tr>
          </table>
        </div>
        <div class="roundBottom"><img src="img/bl.gif"></div>
      </div>
      <?php if (isset($form->questions)) { ?>
        <div class="roundBox">
         <div class="roundTitle">
            Questions of This Paragraph
          </div>
          <div class="roundContent">
            <div id=list class="scrollTable" style="height:expression(document.body.offsetHeight - breadcrumb.offsetTop - 273)">
              <table border=0 cellpadding=0 cellspacing=0>
                <tr class="fixed">
                  <th>&nbsp;</th>
                  <th>ID</th>
                  <th>Sync</th>
                  <th>Data Sync</th>
                  <th>Out Data</th>
                  <th>Popup Question</th>
                  <th class="noDivider" style="width:50%">Before / After Text</th>
                </tr>
                <?php $altColor = true ?>
                <?php foreach ($form->questions as $k => $question) { ?>
                  <?php $altColor = ! $altColor ?>
                  <tr <?php if ($altColor) { ?>class="gray"<?php } ?>>
                    <td class="control" rowspan=2>
                      <input type="checkbox" name="del[]" value="<?=$question->id ?>" title="Select for deletion">
                      <a href="adminQuestion.php?id=<?=$question->id ?>&sid=<?=$form->sectionId ?>&tid=<?=$form->templateId ?><?=rnd() ?>" title="Edit this record"><img src="img/pencil.gif"></a>
                    </td>
                    <td><?=$question->uid ?></td>
                    <td><?=$question->sync ?></td>
                    <td><?=$question->dsync ?></td>
                    <td><?=$question->outData ?></td>
                    <td><?=$question->desc ?></td>
                    <td><?=htmlentities($form->formatText($question)) ?></td>
                  </tr>
                  <tr <?php if ($altColor) { ?>class="gray"<?php } ?>>
                    <td colspan=5 class="details">
                      <?php if (! isBlank($question->test)) { ?>
                        <span>�</span><b>Show if:</b> <?=$question->test ?><br>
                      <?php } ?>
                      <?php if (! isBlank($question->actions)) { ?>
                        <span>�</span><b>Actions:</b> <?=$question->actions ?>
                      <?php } ?>
                    </td>
                  </tr>
                <?php } ?>
              </table>
            </div>
            <div class="action nopad">
              <input type="submit" name="action" value="Add New">
              <input type="button" name="action" value="Copy From..." onclick="showPopCopyQ(<?=$form->templateId ?>,<?=$form->sectionId ?>)">
              <input type="hidden" name="copyValues"">
              <span></span>
              <input type="submit" name="action" value="Delete Checked" onclick="if (deleteCancelled()) return false">
              <span></span>
              <input type="button" name="action" value="Reorder" onclick="alert('Not yet...')">
              <span></span>
              <input type="button" value="RO View" onclick="roview(<?=$form->templateId ?>,<?=$form->id ?>)">
              <input type="button" value="Preview" onclick="preview(<?=$form->templateId ?>,<?=$form->id ?>)">
            </div>
          </div>
          <div class="roundBottom"><img src="img/bl.gif"></div>
        </div>
      <?php } ?>
    </form>
  </body>
</html>
<?php require_once "inc/focus.php" ?>
<script>
function roview(templateId, parId) {
  if (dirty) {
    alert("Warning: Unsaved changes to this page will not be visible on the preview.");
  }
  window.open("par-text.php?tid=" + templateId + "&id=" + parId, "roview", 'toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,dependent=no,width=700,height=500,top=100,left=120');
}
function versionCancelled() {
  return (! confirm("Are you sure you want to make a new version of this paragraph?"));
}
function submitCopy() {
  document.forms[0].submit();
}
function showPopCinfo(pid, name) {
  window.open("adminCinfo.php?pid=" + pid + "&name=" + name, "popCinfo", 'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=yes,dependent=no,width=800,height=700');
}
<?=$jscript ?>
</script>