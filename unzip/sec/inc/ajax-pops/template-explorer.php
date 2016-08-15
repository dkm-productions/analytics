<div id="pop-tx" class="pop" onmousedown="event.cancelBubble = true">
  <div id="pop-tx-cap" class="pop-cap">
    <div id="pop-tx-cap-text">
      Template Explorer
    </div>
    <a href="javascript:closeTxPop()" class="pop-close"></a>
  </div>
  <div class="pop-content small-pad">
    <table border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td>
          <div id="pop-tx-search">
            <div id="txsearch-box">
              <input id="txsearch" type="text" size="12" onfocus="txSearchFocus()" onkeyup="ifCrClick('txsearch-a')" />
              <a id="txsearch-a" href="javascript:" onclick="txSearch()"></a>
            </div>      
          </div>
        </td>
        <td style="vertical-align:bottom;">
          <div id="txNav">
          </div>
        </td>
      </tr>
      <tr>
        <td style="vertical-align:top">
          <div id="pop-tx-pars">
          </div>
        </td>
        <td style="vertical-align:top; padding-left:3px">
          <div id="pop-tx-preview" class="par-preview">
          </div>
          <div class="pop-cmd" style="margin:5px 0 5px 0">
            <a href="javascript:txAdd()" class="cmd check">Insert into Document</a>
            <span>&nbsp;</span>
            <a href="javascript:closeTxPop()" class="cmd none">Cancel</a>
          </div>
        </td>
      </tr>
    </table>
  </div>
</div>
<script>
var txTid;
var txSuid;
var txNoteDate;
var txPars;
var txPar;
var txA;

function showTemplateExplorer(text, tid, noteDate, suid) {
  txTid = tid;
  txSuid = suid;
  txNoteDate = noteDate;
  setValue("txsearch", text);
  Pop.show("pop-tx"); 
  txSearch(); 
}
function txSearchFocus() {
  $("txsearch").select();
}
function txSearch() {
  txA = null;
  setHtml("pop-tx-pars", "").className = "working-circle";
  setHtml("pop-tx-preview", "").className = "par-preview";
  setText("txNav", "");
  $("txsearch-a").focus();
  var text = value("txsearch");
  if (text == "") {
    tSearchCallback({});
  } else {
    postRequest(6, "act=tSearch&t=" + txTid + " &s=" + txSuid + "&tx=" + value("txsearch"));
  }
}
function tSearchCallback(suids) {
  txLoadPars(suids);
  $("txsearch").focus();
}
function txLoadPars(suids) {
  txPars = {};
  txPar = null;
  var previewed = false;
  var ul = createList(setClass("pop-tx-pars", ""));
  for (var suid in suids) {
    var pars = suids[suid];
    var li = addListItem(ul);
    var a = createAnchor(null, null, "folder", suid);
    li.appendChild(a); 
    for (var i = 0; i < pars.length; i++) {
      li = addListItem(ul);
      var par = pars[i];
      var fn = buildFn("txPreview", [par.id]);
      var desc = par.desc;
      a = createAnchor(null, null, null, desc, null, fn);
      li.appendChild(a);
      par.a = a;
      txPars[par.id] = par;
      if (txPar == null) {
        txPreview(par.id);
      }
    }   
  }
  if (txPar == null) {
    $("txsearch").focus();
  }
}
function closeTxPop() {
  Pop.close();
}
function txPreview(id) {
  blur("txsearch");
  txPar = txPars[id];
  txPar.a.className = "sel";
  if (txA) {
    txA.className = "";
  }
  txA = txPar.a;
  setText("txNav", txPar.suid + ": " + txPar.desc);
  if (txPar.json) {
    previewCallback(txPar.json);
    return;
  }
  if (txTid == 1 && txPar.suid == "IMPR") {
    txPreviewImpr();
    return;
  }
  setHtml("pop-tx-preview", "").className = "working-circle par-preview";
  sendRequest(2, "action=preview&id=" + txPar.id + "&tid=" + txTid + "&nd=" + txNoteDate);
}
function txPreviewImpr() {
  var j = {
      id:txPar.id,
      html:""};
  previewCallback(j);
}
function previewCallback(j) {
  if (j.id != txPar.id) {
    txPars[j.id].json = j;
    return;
  }
  txPar.json = j;
  var html = [];
  html.push(j.html);
  if (txPar.iodescs) {
    html.push("<p><u><b>Impression</b></u></p><p>");
    html.push(txPar.iodescs);
    html.push("</p>");
  }  
  setHtml("pop-tx-preview", html.join("")).className = "par-preview";
}
function buildPpHtml() {
  var html = [];
  for (var pid in ppPids) {
    html.push(ppPids[pid]); 
  }
  if (html.length == 0) {
    closePpPop();
  }
  setHtml("pop-tx-content", html.join("")).className = "";
}
function txAdd() {
  Pop.close();
  requestPar(txPar.id);
}
</script>
