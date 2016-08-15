<?
require_once "inc/requireLogin.php";
require_once 'img/charts/ChartIndex.php';
require_once 'php/data/Version.php';
require_once 'php/dao/FacesheetDao.php';
require_once 'php/data/rec/sql/Clients.php';
//
$cid = $_GET['cid'];
$id = $_GET['id'];
$client = Clients::get($cid);
$vitals = FacesheetDao::getGraphingVitals($client);
?>
<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.0 Strict//EN'>
<xml:namespace ns="urn:schemas-microsoft-com:vml" prefix="v"/>
<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
<script language='JavaScript1.2' src='js/ui.js'></script>
<script language='JavaScript1.2' src='js/mod-julian.js'></script>
<script language='JavaScript1.2' src='js/components/Canvas.js'></script>
<script language='JavaScript1.2' src='js/components/Canvas_SVG.js'></script>
<script language='JavaScript1.2' src='js/pages/Ajax.js'></script>
<script language="JavaScript1.2" src="js/yui/yahoo-min.js?<?=Version::getUrlSuffix() ?>"></script>
<script language="JavaScript1.2" src="js/yui/event-min.js?<?=Version::getUrlSuffix() ?>"></script>
<script language="JavaScript1.2" src="js/yui/connection-min.js?<?=Version::getUrlSuffix() ?>"></script>
<script language="JavaScript1.2" src="js/pages/Page.js?<?=Version::getUrlSuffix() ?>"></script>
<link rel='stylesheet' type='text/css' href='css/Canvas.css' />
<style>
BODY {
  margin:0;
  padding:0;
}
DIV#canv {
  margin:0;
  padding:0;
}
DIV#title {
  text-align:center;
  font-family:Arial;
  font-weight:bold;
  font-size:14pt;
}
DIV#client {
  font-family:Arial;
  font-size:11pt;
  font-weight:bold;
  background-color:white;
  padding:10px;
}
v\:line { behavior: url(#default#VML);display:inline-block }
v\:polyline { behavior: url(#default#VML);display:inline-block }
</style>
</head>
<body>
<div id='title'>
</div>
<div id='canv'>
</div>
</body>
</html>
<script>
var bull = ' \u2022 ';
var vitals = <?=jsonencode($vitals) ?>;
var client = <?=jsonencode($client) ?>;
var chart = <?=ChartIndex::getChartJson($id, $vitals, $client->clientId) ?>;
document.title = client.name + bull + chart.title;
var canvas;
if (supportsSvg())
  canvas = new Canvas_SVG($('canv'), true);
else
  canvas = new Canvas($('canv'), true);
canvas.defineGraph(chart.graph.x, chart.graph.y, chart.graph.dim);
document.getElementById('title').style.width = px(chart.graph.dim.width + chart.graph.dim.left * 2);
if (chart.type == 1) {
  var every = parseInt((chart.graph.x.values[1] - chart.graph.x.values[0]) / 8 + 0.5, 10);
  var y = (chart.id == 'vitals-temp') ? {'lineEvery':0.5,'labelEvery':1,'labelSkip':1} : {'lineEvery':1,'labelEvery':10,'labelSkip':5};
  canvas.drawGraph({'lineEvery':every,'labelEvery':every * 2,'labelSkip':every,'labelFn':dateFromJd}, y, {'color':'#003C74','size':2});
  var div = $('title');
  setDiv($('title'), chart.title, '10px');
}
if (chart.id == 'vitals-bp' || chart.id == 'lhcfa-preterm-22t50gw') {
  var spts = [];
  var dpts = [];
  for (var i = 0; i < chart.pts.length; i = i + 2) {
    if (chart.pts[i][1])
      spts.push(chart.pts[i]);
    if (chart.pts[i + 1][1])
      dpts.push(chart.pts[i + 1]);
  }
  canvas.plotLines(spts, {color:'blue',size:2}, {color:'black', size:2});
  canvas.plotLines(dpts, {color:'#FF571D',size:2}, {color:'black', size:2});
} else {
  var color = (chart.girls || chart.type == 3) ? 'blue' : '#FF571D'; 
  var pcolor = 'black';
  canvas.plotLines(chart.pts, {'color':color,'size':2}, {'color':pcolor, 'size':3});
}
var cinfo = '<big><b>' + client.name + '</b></big><br>DOB: ' + client.birth + ' (' + client.age + ')';
if (chart.type == 5) {
  canvas.legend({'height':35,'width':250,'margin':20,'quadrant':Canvas.QUAD_UL}, cinfo);
} else {
  canvas.legend({'height':35,'width':250,'margin':10}, cinfo);
}
function setDiv(div, text, pad) {
  div.innerText = text;
  div.style.width = chart.graph.dim.width;
  div.style.paddingLeft = chart.graph.dim.left;
  div.style.paddingBottom = pad;
}
function supportsSvg() {
  return document.implementation.hasFeature("http://www.w3.org/TR/SVG11/feature#BasicStructure", "1.1");
}
</script>
