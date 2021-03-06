<?
// New Note dialog
// showNewNote(clientId, clientName, schedId=null, dos=today)
// newNoteCallback(session)
?>
<div id="pop-nn" class="pop" onmousedown="event.cancelBubble = true">
  <div id="pop-nn-cap" class="pop-cap">
    <div id="pop-nn-cap-text">
      Create New Document
    </div>
    <a href="javascript:Pop.close()" class="pop-close"></a>
  </div>
  <div id="pop-nn-content" class="pop-content">
    <div class="ro">
      <div class="ro-title" style="margin:0">
        <div>
          <span id="nn-client-name"></span>
        </div>
        <a style='visibility:hidden' class="pencil patient" href="javascript:iChooseAnother(0)">Choose another</a>
      </div>
    </div>
    <ul class="entry">
      <li>
        <label class="first2">Date of Service</label>
        <?php renderCalendar("nn-dos") ?>
      </li>
      <li>
        <label class="first2">Send To</label>
        <select id="pop-nn-sendtos">
        </select>
        <a class="act" href="javascript:setSendToDefault('pop-nn-sendtos')">Set as default</a>
      </li>
      <li style="padding-top:1px">
        <label class="first2">Template</label>
        <select id="pop-nn-templates" onchange="newNoteTemplateChange()">
        </select>
        <a class="act" href="javascript:setTemplateDefault()">Set as default</a>
      </li>
    </ul>
    <div class="ro" style="padding:8px">
      <ul class="entry" style="margin:0;">
        <li style="padding:0">
          <a id="pop-nn-start-empty" href="javascript:createEmptySession()" class="cmd cbig empty-note">
          </a>
        </li>
        <li style="padding:7px 0 0 0">
          <a id="pop-nn-replicate" href="javascript:createStandardSession()" class="cmd cbig copy-note" style="line-height:12pt">
            <div id="pop-nn-replicate-text">Replicate from Patient History</div>
            <span id="pop-nn-replicate-span" style="font-size:9pt; font-weight:normal;">&nbsp;</span>
          </a><br/>
        </li>
        <li style="padding:7px 0 0 0">
          <a id="pop-nn-start-custom" href="javascript:createPrefilledSession()" class="cmd cbig template-note">
            Apply Custom Template:<br/>
            <select id="pop-nn-presets">
            </select>
          </a>
        </li>
      </ul>
    </div>
    <div class="pop-cmd cmd-fixed" style="margin:0; padding:0">
      <a href="javascript:Pop.close()" class="cmd none">Cancel</a>
    </div>
  </div>
</div>
<?
// Replicate Note dialog
// showReplicate(clientId, sessionId)
?>
<div id="pop-rn" class="pop" onmousedown="event.cancelBubble = true">
  <div id="pop-rn-cap" class="pop-cap">
    <div id="pop-rn-cap-text">
      Replicate Document
    </div>
    <a href="javascript:Pop.close()" class="pop-close"></a>
  </div>
  <div id="pop-rn-content" class="pop-content">
    <ul class="entry">
      <li>
        <label class="first6">Date of Service</label>
        <?php renderCalendar("rn-dos") ?>
      </li>
      <li>
        <label class="first6">Send To</label>
        <select id="pop-rn-sendtos">
        </select>
        <a class="act" href="javascript:setSendToDefault('pop-rn-sendtos')">Set as default</a>
      </li>
      <li>
        <label class="first6">Override Facesheet?</label>
        <?php renderLabelCheck("rn-ovfs", "Yes (e.g. include meds/allergies from note)") ?>
      </li>
    </ul>
    <div class="pop-cmd" style="">
      <a href="javascript:rnCreate()" class="cmd copy-note">Create Replicate</a>
      <span>&nbsp;</span>
      <a href="javascript:Pop.close()" class="cmd none">Cancel</a>
    </div>
  </div>
</div>
<?
// Save note dialog
// Does not actually save, just replaces session property values (title, dos, standard)if changed
// showSaveDialog(focusId)
// saveDialogCallback()
?>
<div id="pop-sv" class="pop" onmousedown="event.cancelBubble = true">
  <div id="pop-sv-cap" class="pop-cap">
    <div id="pop-sv-cap-text">
      Save Note As
    </div>
    <a href="javascript:Pop.close()" class="pop-close"></a>
  </div>
  <div id="pop-sv-content" class="pop-content" onkeypress="return ifCrClick('pop-sv-save')">
    <div class="ro">
      <div class="ro-title" style="margin:0; text-align:left">
        <span id="sv-client-name"></span>
      </div>
    </div>
    <ul class="entry">
      <li>
        <label class="first2">Label</label>
        <input id="sv-title" type="text" size="50" />
      </li>
      <li>
        <label class="first2">Date of Service</label>
        <?php renderCalendar("sv-dos") ?>
      </li>
      <li>
        <label class="first2"></label>
        <?php renderLabelCheck("sv-standard", "Use as patient's standard note", false, null,  null, "sv-use") ?>
      </li>
    </ul>
    <div class="pop-cmd">
      <a id="pop-sv-save" href="javascript:" onclick="saveDialogSave();return false" class="cmd save">Save Note</a>
      <span>&nbsp;</span>
      <a href="javascript:Pop.close()" class="cmd none">Cancel</a>
    </div>
  </div>
</div>
<?
// Edit Header (DOS and Send-To) dialog
// showEditHeader(sessionId, clientName, dos, sendToId)
// editHeaderCallback(session)
?>
<div id="pop-eh" class="pop" onmousedown="event.cancelBubble = true">
  <div id="pop-eh-cap" class="pop-cap">
    <div id="pop-eh-cap-text">
      Edit Send To
    </div>
    <a href="javascript:Pop.close()" class="pop-close"></a>
  </div>
  <div id="pop-eh-content" class="pop-content">
    <div class="ro" style="display:none">
      <div class="ro-title" style="margin:0; text-align:left">
        <span id="eh-client-name"></span>
      </div>
    </div>
    <ul class="entry">
      <li style="display:none">
        <label class="first2">Date of Service</label>
        <?php renderCalendar("eh-dos") ?>
      </li>
      <li>
        <label class="nopad">Send To</label>
        <select id="pop-eh-sendtos">
        </select>
      </li>
    </ul>
    <div class="pop-cmd">
      <a href="javascript:saveHeader()" class="cmd save">Save Changes</a>
      <span>&nbsp;</span>
      <a href="javascript:Pop.close()" class="cmd none">Cancel</a>
    </div>
  </div>
</div>
<?
// Patient History dialog
// showOpenNote(clientId)
// openNoteCallback(session)
?>
<div id="pop-on" class="pop" onmousedown="event.cancelBubble = true">
  <div id="pop-on-cap" class="pop-cap">
    <div id="pop-on-cap-text">
      Patient History
    </div>
    <a href="javascript:Pop.close()" class="pop-close"></a>
  </div>
  <div id="pop-on-content" class="pop-content">
    <div class="ro">
      <div class="ro-title" style="margin:0">
        <div>
          <span id="on-client-name"></span>
        </div>
        <a class="pencil patient" href="javascript:iChooseAnother(1)">Choose another</a>
      </div>
    </div>
    <div id="on-ss" class="gridsheet small scrollable" style="height:180px; width:510px">
      <table class="small" style="width:493px">
        <tbody id="on-ss-tbody" class="grid">
        </tbody>
      </table>
    </div>
    <div class="pop-cmd">
      <?php if ($login->Role->Artifact->noteCreate) { ?>
        <a href="javascript:newNoteFromOpen()" class="cmd note">New Document...</a>
        <span>&nbsp;</span>
      <?php } ?>
      <a href="javascript:Pop.close()" class="cmd none">Cancel</a>
    </div>
  </div>
</div>
<?
// New Custom Template dialog
// showNewCustomTemplate()
// newCustomTemplateCallback(preset)
?>
<div id="pop-nct" class="pop" onmousedown="event.cancelBubble = true">
  <div id="pop-nct-cap" class="pop-cap">
    <div id="pop-nct-cap-text">
      New Custom Template
    </div>
    <a href="javascript:closeNewCustomTemplate()" class="pop-close"></a>
  </div>
  <div id="pop-nct-content" class="pop-content">
    <ul class="entry" style="margin-bottom:0">
      <li>
        <label class="first">Based On</label>
        <select id="pop-nct-templates">
        </select>
      </li>
      <li style="padding-top:2px;">
        <label class="first">&nbsp;</label>
        <a href="javascript:createNewCustomTemplate()" class="cmd note">Start Custom Template ></a>
        <span>&nbsp;</span>
        <a href="javascript:closeNewCustomTemplate()" class="cmd none">Cancel</a>
      </li>
    </ul>
  </div>
</div>
<?
// Open Custom Template dialog
// showOpenCustomTemplate(caption, tid)  // tid=null for all
// openCustomTemplateCallback(preset, caption)
?>
<div id="pop-oct" class="pop" onmousedown="event.cancelBubble = true" style="width:500px">
  <div id="pop-oct-cap" class="pop-cap">
    <div id="pop-oct-cap-text">
      Open Custom Template
    </div>
    <a href="javascript:closeOpenCustomTemplate()" class="pop-close"></a>
  </div>
  <div id="pop-oct-content" class="pop-content">
    <div id="oct-ss" class="spreadsheet" style="display:none">
     <table id="oct-ss-table" class="data small" width="96%">
        <tbody id="oct-ss-tbody">
          <tr>
            <th>Name</th>
            <th>Based On</th>
            <th>Last Updated</th>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="pop-cmd">
      <a href="javascript:closeOpenCustomTemplate()" class="cmd none">Cancel</a>
    </div>
  </div>
</div>
