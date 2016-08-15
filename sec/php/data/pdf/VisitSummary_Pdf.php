<?php
require_once 'php/data/rec/sql/Documentation.php';
require_once 'php/data/pdf/_PdfHtmlRec.php';
//
class VisitSummary_Pdf extends PdfHtmlRec {
  //
  static function from($rec, $recordIpc = true) {
    if ($recordIpc)
      static::recordIpc($rec);
    $me = parent::from($rec);
    $me->addDisclaimer();
    return $me;
  }
  static function asReprint($rec) {
    $me = static::from($rec, false);
    $me->addPrintDate();
    return $me;
  }
  static function download_asReprint($rec) {
    $me = static::asReprint($rec);
    $me->downloadPdf();
  }
  private static function recordIpc($rec) {
    $days = daysBetween($rec->dos, nowNoQuotes());
    if ($days > 3)
      Proc_PatientSummary3d::record($rec->clientId);
    else
      Proc_PatientSummary::record($rec->clientId);
  }
  //
  public function getPdfFilename() {
    return static::makeFilename('Summary', $this->clientId, $this->finalId);
  }
  public function getPdfTitle() {
    return 'Visit Summary';
  }
  public function getPdfHeader() {
    return $this->finalHead;
  }
  public function getPdfBody() {
    return DocVisitSum::getHtmlBody($this);
  }
  public function addDisclaimer() {
    $this->finalBody .= "<p>&nbsp;</p><table border=1 cellpadding=5><tr><td>This document is provided as a summary of your office visit. If you have any worsening or change in your condition, or if your condition doesn't seem to be responding to therapy, please contact your provider immediately. If you notice any inaccuracies, errors or incomplete information on the summary, please notify your provider.</td></tr></table>";
  }
  public function addPrintDate() {
    $this->finalHead .= "Date Printed: " . formatNowTimestamp() . "<br>";
  }
}
