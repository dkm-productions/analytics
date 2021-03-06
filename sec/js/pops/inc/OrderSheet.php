<?
/**
 * Order Sheet
 */
?>
<div id='pop-os' class='pop' onmousedown='event.cancelBubble=true' style='width:800px'>
  <div id='pop-os-cap' class='pop-cap'>
    <div>
      Clicktate - Generate Orders
    </div>
    <a href='javascript:OrderSheet.pClose()' class='pop-close'></a>
  </div>
  <div class='pop-content'>
    <div id='os-div' class='fstab' style='height:500px'>
      <table id='os-tbl' class='fsb single grid'>
        <thead>
          <tr class='head'>
            <th></th>
            <th>Category</th>
            <th>Item</th>
            <th>Priority</th>
            <th>Notes</th>
          </tr>
        </thead>
        <tbody id='os-tbody'>
        </tbody>
      </table>
    </div>
    <div class='pop-cmd'>
      <a href='javascript:' onclick='OrderSheet.pSave()' class='cmd save'>Save to Tracking Sheet</a>
      <span>&nbsp;</span>
      <a href='javascript:' onclick='OrderSheet.pClose()' class='cmd none'>Cancel</a>
    </div>
  </div>
</div>
