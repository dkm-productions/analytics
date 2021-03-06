<?
/**
 * HM Proc Picker 
 */
?>
<div id="pop-pp" class="pop" onmousedown="event.cancelBubble = true" style="width:620px">
  <div id="pop-pp-cap" class="pop-cap">
    <div>
      Clicktate - Test/Procedure Selection
    </div>
    <a href="javascript:Pop.close()" class="pop-close"></a>
  </div>
  <div class="pop-content">
    <div class="pop-frame">
      <div class="pop-frame-head">
        <h1>Select a Test/Procedure</h1>
        <a class="pencil custom" href="javascript:FaceHmProcPicker.fpCustomize()">Customize</a>
      </div>
      <div class="pop-frame-content">
        <div id="pp-div" class="fstab" style="height:360px">
          <table id="pp-tbl" class="fsgr grid">
            <thead>
              <tr class="fixed head">
                <th>Name</th>
                <th colspan="4">Auto Apply Criteria</th>
              </tr>
            </thead>
            <tbody id="pp-tbody">
            </tbody>
          </table>
        </div>
        <div class="pop-cmd">
          <a href="javascript:Pop.close()" class="cmd none">Cancel</a>
        </div>
      </div>
    </div>
  </div>
</div>
<?
/**
 * Facesheet Health Maintenance
 * Controller: FaceHm.js
 */
?>
<div id="fsp-hm" class="pop" onmousedown="event.cancelBubble = true" style="width:740px">
  <div id="fsp-hm-cap" class="pop-cap">
    <div id="fsp-hm-cap-text">
      Health Maintenance
    </div>
    <a href="javascript:FaceHm.fpClose()" class="pop-close"></a>
  </div>
  <div class="pop-content">
    <table class='w100'>
      <tr>
        <td style="width:170px; vertical-align:top">
          <ul id="hm-filter-ul" class="filter"></ul>
          <div class="pop-cmd" style="margin:5px 0 0 18px;padding:0;text-align:left;">
            <a href="javascript:" onclick="FaceHm.fpAddHm()" class="cmd new">Add...</a>
          </div>
        </td>
        <td style="padding-left:5px">
          <div id="hm-one">
            <div class="pop-frame-content" style="margin-top:5px;">
              <table class='w100'>
                <tr> 
                  <td>
                    <h4 id="hm-one-proc"></h4>
                    <span id="hm-one-proc-desc" style="color:#494949"></span>
                  </td>
                  <td style="vertical-align:top">
                    <div class="pop-cmd cmd-right" id="fsp-hm-deactivate" style="margin:0;padding:0">
                      <a href="javascript:" onclick="FaceHm.fpDeleteBlankHm()" class="cmd delete-red">Remove Test/Procedure</a>
                    </div>
                  </td>
                </tr>
              </table>
              <div id="hm-face-entry">
                <ul class="entry q">
                  <li>
                    <span id="hme-next-info" class="warn">
                    </span>
                  </li>
                </ul>
                <ul id="ul-hm-face-fields" class="entry q">
                </ul>
              </div>
            </div>
          </div>
          <div id="fsp-hma-1" class="push5">
            <div id="fsp-hma-div" class="fstab" style="height:360px;">
              <table id="fsp-hma-tbl" class="fsp single grid">
                <thead>
                  <tr id="fsp-hma-head" class="fixed head">
                    <th>Test/Procedure</th>
                    <th>Last&nbsp;Date</th>
                    <th style="width:50%">Last Results</th>
                    <th>Next&nbsp;Due</th>
                  </tr>
                </thead>
                <tbody id="fsp-hma-tbody">
                </tbody>
              </table>
            </div>
            <table class='w100'>
              <tr>
                <td>
                  <div class="pop-cmd cmd-right">
                    <a id="hma-cmd-add" href="javascript:" onclick="FaceHm.fpAddHm()" class="cmd new">Add a Test/Procedure...</a>
                    <span>&nbsp;</span>
                    <a href="javascript:FaceHm.fpClose()" class="cmd none">&nbsp;&nbsp;Exit&nbsp;&nbsp;</a>
                  </div>
                </td>
              </tr>
            </table>
          </div>
          <div id="fsp-hma-2" class="push5 pop-frame-content">
            <h2>History</h2>
            <div id="fsp-hm-div" class="fstab" style="height:200px;">
              <table id="fsp-hm-tbl" class="fsp single grid">
                <thead>
                  <tr id="fsp-hm-head" class="fixed head">
                    <th class="check">&nbsp;</th>
                    <th>&nbsp;</th>
                    <th>Date</th>
                    <th style="width:50%">Results</th>
                  </tr>
                </thead>
                <tbody id="fsp-hm-tbody">
                </tbody>
              </table>
            </div>
            <table class='w100'>
              <tr>
                <td nowrap="nowrap">
                  <div class="pop-cmd">
                    <span id="fsp-hm-delete">
                      <label>
                        With checked:
                      </label>
                      <a id="hm-cmd-toggle" href="javascript:" onclick="FaceHm.fpDeleteChecked()" class="cmd delete-red">Delete</a>
                    </span>
                  </div>
                </td>
                <td class='w100'>
                  <div class="pop-cmd cmd-right">
                    <a id="hm-cmd-add" href="javascript:" onclick="FaceHm.fpEditHm()" class="cmd note">Add History Item...</a>
                    <span>&nbsp;</span>
                    <a href="javascript:" onclick="FaceHm.fpReturn()" class="cmd none">&nbsp;Return&nbsp;</a>
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
<?
/**
 * HM Interval Entry  
 */
?>
<div id="fsp-hmcint" class="pop" onmousedown="event.cancelBubble = true">
  <div id="fsp-hmcint-cap" class="pop-cap">
    <div id="fsp-hmcint-cap-text">
      Set Interval
    </div>
    <a href="javascript:Pop.close()" class="pop-close"></a>
  </div>
  <div class="pop-content">
    <ul class="entry">
      <li>
        <label>Every</label>
        <input id="hmcint-every" type="text" size="1" />
        <select id="hmcint-int">
        </select>
      </li>
    </ul>
    <div class="pop-cmd" style="margin-top:20px">
      <a href="javascript:" onclick="FaceHmIntEntry.fpOk()" class="cmd ok">OK</a>
      <span>&nbsp;</span>
      <a class="cmd delete" href="javascript:" onclick="FaceHmIntEntry.fpClear()">Clear (Use Default)</a>
      <span>&nbsp;</span>
      <a href="javascript:Pop.close()" class="cmd none">Cancel</a>
    </div>
  </div>
</div>
<?
/**
 * HM Entry  
 */
?>
<div id="pop-hme" class="pop" onmousedown="event.cancelBubble = true" style="width:670px">
  <div id="pop-hme-cap" class="pop-cap">
    <div id="pop-hme-cap-text">
      Clicktate - Health Maintenance Entry
    </div>
    <a href="javascript:Pop.close()" class="pop-close"></a>
  </div>
  <div class="pop-content">
    <h4 id="hme-proc"></h4>
    <ul id="ul-hme-fields" class="entry">
    </ul>
    <div class="pop-cmd push">
      <a href="javascript:" onclick="FaceHmEntry.fpSave()" class="cmd save">Save Changes</a>
      <span id="hme-delete-span">
        <span>&nbsp;</span>
        <a href="javascript:" onclick="FaceHmEntry.fpDelete()" class="cmd delete-red">Delete</a>
      </span>
      <span>&nbsp;</span>
      <a href="javascript:Pop.close()" class="cmd none">Cancel</a>
    </div>
  </div>
</div>
<?
/**
 * HM Customization  
 */
?> 
<div id="fsp-hmcp" class="pop" onmousedown="event.cancelBubble = true" style="width:760px">
  <div id="fsp-hmcp-cap" class="pop-cap">
    <div id="fsp-hmcp-cap-text">
      Tests/Procedure Customization
    </div>
    <a href="javascript:Pop.close()" class="pop-close"></a>
  </div>
  <div class="pop-content">
    <div id="hmcp-div" class="fstab" style="height:410px">
      <table id="hmcp-tbl" class="fsgr grid smallpad vmid">
        <thead>
          <tr class="fixed head bottom">
            <th>Active</th>
            <th>Name [CPT]</th>
            <th class="center">Auto<br/>Apply</th>
            <th class="center">Gender</th>
            <th class="center">Age<br/>Start</th>
            <th class="center">Age<br/>Up To</th>
            <th class="center">Frequency</th>
            <th class="center">ICD</th>
          </tr>
        </thead>
        <tbody id="hmcp-tbody" onclick="FaceHmCustomProc.fpClick()">
        </tbody>
      </table>
    </div>
    <div class="pop-cmd">
      <table class='h'>
        <tr>
          <th>
            <a href="javascript:" onclick="FaceHmCustomProc.fpAdd()" class="cmd new">Add Test/Procedure...</a>
          </th>
          <td>
            <a href="javascript:" onclick="FaceHmCustomProc.fpSave()" class="cmd save">Save Changes</a>
            <span>&nbsp;</span>
            <a href="javascript:Pop.close()" class="cmd none">Cancel</a>
          </td>
        </tr>
      </table>
    </div>
  </div>
</div>
<div id="fsp-hmcpa" class="pop" onmousedown="event.cancelBubble = true" style="width:700px">
  <div id="fsp-hmcpa-cap" class="pop-cap">
    <div id="fsp-hmcpa-cap-text">
      Clicktate - New Test/Procedure
    </div>
    <a href="javascript:Pop.close()" class="pop-close"></a>
  </div>
  <div class="pop-content">
    <div class="pop-frame">
      <h1>New Test/Procedure</h1>
      <div class="pop-frame-content">
        <ul class="entry">
          <li>
            <label class="first">Name</label>
            <input id="proc-name" type="text" size="80" />
            <a href="javascript:" onclick="FaceHmCustomProc.fpaLookupCpt()" class="find">Lookup...</a>
          </li>
          <li>
            <label class="first">CPT</label>
            <input id="proc-cpt" type="text" size="5" />
          </li>
        </ul>
      </div>
    </div>
    <div class="pop-cmd">
      <a href="javascript:" onclick="FaceHmCustomProc.fpaSave()" class="cmd save">Save Changes</a>
      <span>&nbsp;</span>
      <a href="javascript:Pop.close()" class="cmd none">Cancel</a>
    </div>
  </div>
</div>
