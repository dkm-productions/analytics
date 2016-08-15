/*
 * Download to Word. Required variables:
 * - session
 * - lu_customs
 * - lu_tcustoms
 */
function download(s, sigName, noSubmit) {
  var pc = getPrintCustoms();
  var d = "";
  var head = "";
  for (var si = 0; si < s.length; si++) {
    var secs = s[si].childNodes;
    var addendum = si > 0;
    if (secs) {
      for (var i = 0; i < secs.length; i++) {
        var sec = secs[i];
        var pars = sec.childNodes;
        var sig = (sec.id == "sig" || sec.id == "\\'sig\\'");
        if (addendum || sig) {
          if (sig) d += "\\f1\\fs18 ";  // arial on
          for (var j = 0; j < pars.length; j++) {
            var text = spanText(pars[j]);
            d += text;
          }
          if (d.substring(d.length - 4, d.length) != "\\par") d += "\\par ";
          if (sig) d += "\\f0\\fs22 ";  // arial off
        } else {
          var suidchar1 = sec.getAttribute('uid').substring(0, 1);
          if (sec.className == "v" || suidchar1 == "@") {
            if (sec.getAttribute('uid') == "@header") {
              head = parsText(pars, suidchar1);
              head = head.substring(0, head.length - 3);
            } else {
              var text = parsText(pars, suidchar1);
              d += text;
            }
          }
        }
      }
    }
  }
  d = d.replace(/<b>/g, "\\b ");
  d = d.replace(/<\/b>/g, "\\b0 ");
  d = d.replace(/<u>/g, "\\ul ");
  d = d.replace(/<\/u>/g, "\\ulnone ");
    
  // Copy generated documented to form and submit
  docForm.docText.value = d;
  docForm.docHead.value = head;
  docForm.docName.value = session.cname + "_" + session.id;
  docForm.docFmt.value = lu_custom.fmtAsRtf ? "rtf" : "doc";
  docForm.docImg.value = denull(pc.logofile);
  docForm.docImg2.value = denull(pc.logofile2);
  docForm.docSigImg.value = denull(pc.sigfile);
  docForm.docNoTag.value = lu_custom.noTag ? "1" : "0";
  docForm.docLeftHead.value = pc.leftHead ? "1" : "0";
  docForm.docSigName.value = sigName;
  if (pc.sigDos) {
    docForm.docSigName.value += "<br>" + session.dos;    
  }
  docForm.docDos.value = session.dos;
  if (! noSubmit) {
    docForm.submit();
  }
}
function parsText(pars, suidchar1) {
  var d = "";
  if (pars.length == 0) return "";
  var j = 0;
  if (pars[0].className == "pTitle") {
    j = 1;
    var sTitle = getInnerText(pars[0]);
    if (lu_custom.singleSpace) {
      d += "\\ul\\b " + sTitle + "\\b0\\ulnone\\par ";
    } else {
      d += "\\ul\\b " + sTitle + "\\b0\\ulnone\\par\\par ";
    }
  }
  for (var i = j, k = pars.length; i < k; i++) {
    var last = (i == k - 1);
    var par = pars[i];
    var x = "";
    if (par.className == "v" || suidchar1 == "@") {
      x = parText(par);
      d += x;
    }
    if (x != "") {
      if (! terse || last) {
        if (d.substring(d.length - 9, d.length) != "\\par \\par") {
          if (d.substring(d.length - 4, d.length) == "\\par") {
            d += "\\par ";
          } else {
            d += "\\par\\par ";
          }
        } else {
          d = trim(d) + " ";
        }
      } else {
        if (d.substring(d.length - 9, d.length) != "\\par \\par") {
          if (d.substring(d.length - 4, d.length) == "\\par") {
          } else {
            d += "\\par ";
          }
        } else {
          d = trim(d) + " ";
        }
      }
    }
  }
  return d;
}
function parText(par) {
  var d = "";
  var pTags = par.childNodes;
  for (var k = 0; k < pTags.length; k++) {
    var pTag = pTags[k];
    if (pTag.className != "h") {
      var spans = parSpans(pTag);
      if (spans) {
        tableInserted = false;
        for (var m = 0; m < spans.length; m++) {
          var span = spans[m];
          d += spanText(span);
          if (tableInserted) {
            break;
          }
        }
      }
    }
  }
  return fixPunc(d);
}
function parSpans(pTag) {
  var nodes = pTag.childNodes;
  var spans = [];
  for (var i = 0; i < nodes.length; i++) {
    var span = nodes[i];
    if (span.dq) {  // delete tag
      if (span.prt != "n") {
        for (var j = 0, k = span.children.length; j < k; j++) {
          spans.push(span.children[j]);
        }
      }
    } else {
      spans.push(span);
    }
  }
  return spans;
}
function spanText(span) {
  if (span.tagName == "TBODY") {
    return fixTable(span);
  }
  if (span.tagName == "TABLE") {
    return fixTable(span.children[0]);
  }
  if (span.className == 'h' || span.className == 'h2' || span.className == 'clone' || span.className == 'noprt' || span.className == 'icd' || span.className == 'cmd erx') {
    return "";
  }
  if (span.getAttribute('name') == "clonePop") {
    return cloneText(span, false);
  }
  if (span.id == "ft") {
    return dFreePopText(span);
  }
  if (span.prt == "n") {
    return "";
  }
  var t = "";
  var tags = span.childNodes;
  if (tags == null) {
    return getInnerText(span);
  }
  if (tags.length == 0 && span.tagName == "P") {
    t += "\\par\\par ";
  } else {
    for (var i = 0; i < tags.length; i++) {
      var tt = trim(tagText(tags[i]));
      if (tt.length) {
        t += tt + " ";
      }
    }
  }
  return t;
}
function tagText(tag) {
  if (tag.tagName == null) {
    if (tag.data)
      return tag.data;
    else
      return tag.toString();
  } else {
    if (tag.tagName == "A") {
      if (tag.className == 'icd' || tag.className == 'cmd erx')
        return '';
      else if (tag.className.contains("bu")) 
        return "\\b\\ul " + spanText(tag) + "\\ulnone\\b0 ";
      else
        return spanText(tag);
    } else if (tag.tagName == "SPAN") {
      return spanText(tag);
    } else if (tag.tagName == "P") {
      return "\\par\\par ";
    } else if (tag.tagName == "U") {
      return "\\ul " + getInnerText(tag) +"\\ulnone ";
    } else if (tag.tagName == "B") {
      return "\\b " + spanText(tag) + "\\b0 ";
    } else if (tag.tagName == "I") {
      return "\\i " + spanText(tag) + "\\i0 ";
    } else if (tag.tagName == "BR") {
      return "\\par ";
    } else if (tag.tagName == "TBODY") {
      return fixTable(tag);
    } else if (tag.tagName == "TABLE") {
      return fixTable(tag.children[0]);
    }
  }
  return "";
}
function cloneText(span, asHtml) {
  var divs = span.getElementsByTagName("DIV");  // each instance is wrapped in a DIV  
  // If no clones inserted, return nothing 
  if (divs.length == 0) {
    return "";
  }
  // Append before-text plus clone-texts
  var t = getInnerText(span.firstChild);
  t += crlf(asHtml);
  for (var j = 0; j < divs.length; j++) {
    var tags = divs[j].childNodes;
    for (var i = 0; i < tags.length; i++) {
      var tag = tags[i];
      if (tag.className != "clone" && getInnerText(tag) != null) {
        if (tag.id == "ft") {
          t += " " + dFreePopText(tag);
        } else {
          t += getInnerText(tag);
        }
      }
    }
    t += crlf(asHtml);
  }
  return t;
}
var TR_DEF = "\\trowd\\intbl\\trbrdrt\\brdrs\\brdrw10\\brdrcf2\\trbrdrl\\brdrs\\brdrw10\\brdrcf2\\trbrdrb\\brdrs\\brdrw10\\brdrcf2\\trbrdrr\\brdrs\\brdrw10\\brdrcf2\\trbrdrh\\brdrs\\brdrw10\\brdrcf2\\trbrdrv\\brdrs\\brdrw10\\brdrcf2\\trgaph50";
function fixTable(h) {
  tableInserted = true;
  var trs = h.childNodes;
  var x = "";
  for (var i = 0; i < trs.length; i++) {
    var ex = extractTds(trs[i].childNodes);
    if (ex != null) {
      var tds = ex.tds;
      var cw = 9000 / ex.cols;  // do integer division
      var c = 0;
      var def = TR_DEF;
      for (var j = 0; j < tds.length; j++) {
        c += tds[j].colSpan;
        def += "\\clbrdrt\\brdrs\\brdrw10\\brdrcf2\\clbrdrl\\brdrs\\brdrw10\\brdrcf2\\clbrdrb\\brdrs\\brdrw10\\brdrcf2\\clbrdrr\\brdrs\\brdrw10\\brdrcf2";
        if (tds[j].tagName == "TH") {
          def += "\\clcbpat3";
        }
        def += "\\cellx" + cw * c;
      }
      x += def + "{";
      for (var j = 0; j < tds.length; j++) {
        x += "\\qc ";
        if (tds[j].tagName == "TH") {
          x += "\\b ";
        }
        x += spanText(tds[j]) + "\\b0\\cell ";
      }
      x += "}{" + def + "\\row }";
    }
  }
  x += "\\pard ";
  return x;
}
function extractTds(a) {
  var tds = [];
  var cols = 0;
  for (var i = 0; i < a.length; i++) {
    if (a[i].tagName == "TD" || a[i].tagName == "TH") {
      tds.push(a[i]);
      cols += parseInt(a[i].colSpan); 
    }
  }
  if (cols == 0) {
    return null;
  } else {
    return {"tds":tds,"cols":cols};
  }
}
function crlf(asHtml) {
  return asHtml ? "<br>" : "\\par ";
}
function dFreePopText(span) {
  if (noIcdSet)
    return '';
  var a = span.childNodes[0];
  if (a.className == "ftd") {
    return "";
  }
  return getInnerText(a) + "  ";
}
function getInnerText(e) {
  return e.textContent || e.innerText;
}
function fixPunc(d) {
  d = d.replace(/\xa0/g, " ");  // change hex-160 to space
  d = d.replace(/\s+\./g, ".");  // elim spaces before period
  d = d.replace(/\.\s+/g, ". ");  // one space after period
  return trim(d);
}
function getPrintCustoms() {
  var logofile = lu_custom.logofile;
  var logofile2 = lu_custom.logofile2;
  var sigfile = lu_custom.sigfile;
  var leftHead = false;
  var sigDos = false;
  var c = lu_tcustoms[session.tid];
  if (c != null) {
    if (c.logofile) logofile = c.logofile;
    if (c.logofile2) logofile2 = c.logofile2;
    if (c.sigfile) sigfile = c.sigfile;
    if (c.leftAlignHead) leftHead = true;
    if (c.sigDos) sigDos = true;
  }
  return {
      logofile:logofile,
      logofile2:logofile2,
      sigfile:sigfile,
      leftHead:leftHead,
      sigDos:sigDos};
}
 
/*
 * Copy to Clipboard
 */ 
function copyDoc(forSigning) {
  var d = "<div id='dSections'>";
  var s = $("dSections");
  var secs = s.childNodes;
  if (secs) {
    for (var i = 0; i < secs.length; i++) {
      var sec = secs[i];
      var char1 = sec.getAttribute('uid').substring(0, 1);
      d += "<div id='" + sec.id +"' uid='" + sec.getAttribute('uid') + "'";
      if (char1 == "@") {
        d += " class='h' style='display:none'>";
      } else {
        d += " class='v'>";
      }
      var pars = sec.childNodes;
      if (sec.className == VISIBLE || char1 == "@") {
        if (sec.getAttribute('uid') != "@header" || forSigning) {
          d += parsCopyText(pars, sec.getAttribute('uid'));
        }
      }
      d += "</div>";
    }
  }
  d += "</div>";
  if (! forSigning && ! lu_custom.noTag) {
    d += "<div><center><small><br/><br/><br/>This note created at clicktate.com</small></center></div>";
  }
  // Fix hex chars
  //d = d.replace(/\xa0/g, "&nbsp;");  see fixPunc
  d = d.replace(/\u2022/g, "&#149;")  // bullets
  return d;
}
function parsCopyText(pars, suid) {
  var d = "";
  if (pars[0].className != HIDDEN) {
    var sTitle = getInnerText(pars[0]);
    d = "<p class=pTitle><u><b>" + sTitle + "</b></u></p>";
  }
  for (var j = 1; j < pars.length; j++) {
    var par = pars[j];
    if (par.className == VISIBLE || suid.substring(0, 1) == "@") {
      d += "<div class=v><p><span class=v>" + parCopyText(par) + "</span></p></div>";
    }
  }
  return d;
}
var noIcdSet;
function parCopyText(par, removeIcd) {
  noIcdSet = removeIcd;
  var d = "";
  var pTags = par.childNodes;
  for (var k = 0; k < pTags.length; k++) {
    var pTag = pTags[k];
    if (pTag.className != HIDDEN) {
      var spans = pTag.childNodes;
      if (spans) {
        tableInserted = false;
        for (var m = 0; m < spans.length; m++) {
          var span = spans[m];
          d += spanCopyText(span);
          if (tableInserted) {
            break;
          } 
        }
      }
    }
  }
  var text = fixPunc(d);
  return text;
}
function spanCopyText(span) {
  if (span.tagName == "TBODY") {
    return fixCopyTable(span);
  }
  if (span.tagName == "TABLE") {
    return fixCopyTable(span.children[0]);
  }
  if (span.className == HIDDEN || span.className == "notext" || span.className == 'cmd erx') {
    return "";
  }
  if (span.getAttribute('name') == "clonePop") {
    return cloneText(span, true);
  }
  if (span.id == "ft") {
    return (noIcdSet) ? '' : freePopText(span);
  }
  var text = getInnerText(span); 
  if (text && text.indexOf("chpgn") >= 0) {
    return "";
  }
  if (span.getAttribute('prt') == "n") {
    return "";
  }
  var t = "";
  var tags = span.childNodes;
  if (tags == null) {
    return getInnerText(span);
  }
  for (var i = 0; i < tags.length; i++) {
    t += tagCopyText(tags[i]);
  }
  return t;
}
function tagCopyText(tag) {
  if (tag.tagName == null) {
    if (tag.data)
      return tag.data;
    else
      return tag.toString();
  } else {
    if (tag.tagName == "A") {
      if (tag.className == 'icd' || tag.className == 'cmd erx')
        return '';
      if (noIcdSet && (tag.className == 'dficd' || tag.className == 'icdset'))
        return '';
      return spanCopyText(tag);
    } else if (tag.tagName == "SPAN") {
      return spanCopyText(tag);
    } else if (tag.tagName == "P") {
      return "</p><p> ";
    } else if (tag.tagName == "U") {
      return "<u>" + getInnerText(tag) +"</u>";
    } else if (tag.tagName == "B") {
      return "<b> " + getInnerText(tag) + "</b>";
    } else if (tag.tagName == "BR") {
      return "<br>";
    } else if (tag.tagName == "TBODY") {
      return fixCopyTable(tag);
    } else if (tag.tagName == "TABLE") {
      return fixCopyTable(tag.children[0]);
    }
  }
  return "";
}
function fixCopyTable(h) {
  tableInserted = true;
  var trs = h.childNodes;
  var x = "<table width=100% border=0 cellpadding=0 cellspacing=0 style='border-collapse:collapse;'>";
  for (var i = 0; i < trs.length; i++) {
    var ex = extractTds(trs[i].childNodes);
    if (ex != null) {
      x += "<tr>";
      var tds = ex.tds;
      for (var j = 0; j < tds.length; j++) {
        var cs = "";
        if (tds[j].colSpan > 0) {
          cs = " colspan=" + tds[j].colSpan;
        }
        if (tds[j].tagName == "TH") {
          x += "<th" + cs + " style='border:1px solid #a0a0a0;padding:0 4px;background-color:#eaeaea;'>";
          x += spanText(tds[j]);
          x += "</th>";
        } else {
          x += "<td" + cs + " style='border:1px solid #a0a0a0;padding:0 4px;text-align:center;'>";
          x += spanText(tds[j]);
          x += "</td>";
        }
      }
      x += "</tr>";
    }
  }
  x += "</table><table border=0 cellpadding=0 cellspacing=0><tr><td height=10></td></tr></table>";
  return x;
}
 