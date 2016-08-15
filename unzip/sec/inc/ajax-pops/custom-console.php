<?
// Customize Template Map dialog
// Interacts with console's map object
// showCustomTemplateMap()
// customTemplateMapCallback()
?>
<div id="pop-cmap" class="pop" onmousedown="event.cancelBubble = true">
  <div id="pop-cmap-cap" class="pop-cap">
    <div id="pop-cmap-cap-text">
      Customize Template Map
    </div>
    <a href="javascript:Pop.close()" class="pop-close"></a>
  </div>
  <div id="pop-cmap-content" class="pop-content">
    <ul class="entry">
      <li>
        <label class="nopad"><b>Section</b></label>
        <select id="cmap-sections" onchange="cmapSectionChange()">
        </select>
        <? renderLabelCheck("cmap-start", "Start up with this section", false, null, "cmapStartClick()") ?>
      </li>
    </ul>
    <div id="cmap-ss" class="gridsheet small scrollable" style="height:440px; width:520px">
      <table class="small" style="width:100%">
        <tbody id="cmap-tbody" class="grid" onclick="cmapTableClick()">
          <tr class="fixed">
            <th width="98%">Paragraph</th>
            <th colspan="2">&nbsp;</th>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="pop-cmd">
      <a href="javascript:cmapSave()" class="cmd save">Save Changes</a>
      <span>&nbsp;</span>
      <a href="javascript:cmapReset()" class="cmd none">Reset to Default</a>
      <span>&nbsp;</span>
      <a href="javascript:Pop.close()" class="cmd none">Cancel</a>
    </div>
    <div class="pop-information" style="margin-top:10px;">
      <div><b style="color:#800000">Main?</b> Check the paragraphs you want at the top "Main" section<br/> of the Template Map.</div> 
      <div style="margin-top:0.5em"><b style="color:#008000">Auto-include?</b> Check any paragraphs you want automatically inserted<br/> 
      into future documents for this template.</div> 
    </div>
  </div>
</div>