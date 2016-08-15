<?php
require_once 'CqmReports_Sql.php';
//
class CMS166v4 extends CqmReport {
  //
  static $CQM = "CMS166v4";
  static $ID = "40280381-4555-e1c1-0145-9500c0b22dc1";
  static $SETID = "b6016b47-b65d-4be0-866f-1d397886ca89";
  static $NQF = "0052";
  static $VERSION = "4";
  static $TITLE = "Use of Imaging Studies for Low Back Pain";
  static $POPCLASSES = array('CMS166v4_Pop');
}
class CMS166v4_Pop extends CqmPop {
  //
  static $IPP = "ED0F23BC-F811-4F9E-A19C-7263F1C6F174";
  static $DENOM = "77999DD4-72B9-45CB-9B7A-8025D87A7731";
  static $DENEX = "8c395680-2899-4499-ad44-43fa92369aef";
  static $NUMER = "7ceffff4-7d1b-411a-9e66-074a8eea71c8";
  static $DENEXCEP = null;
    //
  protected function getIpp($ugid, $from, $to, $uid) {
    return Client166_Ipp::fetchAll($ugid, $from, $to, $uid);
  }
  protected function getNumer($ugid, $from, $to, $uid) {
    return Client166_Numer::fetchAll($ugid, $from, $to, $uid);
  }
  protected function getExclu($ugid, $from, $to, $uid) {
    return Client166_Exclu::fetchAll($ugid, $from, $to, $uid);    
  }
}
class Client166_Ipp extends Client_Cqm {
  //
  static function asCriteria($ugid, $from, $to, $uid) {
    $c = static::from($ugid, $from, $to, 18, 50);
    $c->Encounters = CriteriaJoin::requiresAsArray(Proc166::asEncounter($from, $to, $uid));
    $before = futureDate(337, 0, 0, $from);
    $c->BackPains = CriteriaJoin::requiresAsArray(Diag166::asBackPain($before));
    return $c;
  }
  static function filter($recs, $from, $to) {
    return array_filter($recs, function($rec) {
      $rec->BackPain = array_pop($rec->BackPains);
      return true;
    });
  }
}
class Client166_Exclu extends Client166_Ipp {
  //
  static function asCriteria($ugid, $from, $to, $uid) {
    $c = parent::asCriteria($ugid, $from, $to, $uid);
    $c->Cancer = CriteriaJoin::optionalAsArray(Diag166::asCancer($to));
    $start = pastDate(1, 0, 0, $from);
    $c->Trauma = CriteriaJoin::optionalAsArray(Diag166::asTrauma($start, $to));
    $c->Drug = CriteriaJoin::optionalAsArray(Diag166::asDrug($start, $to));
    $c->Neuro = CriteriaJoin::optionalAsArray(Diag166::asNeuro($start, $to));
    return $c;
  }
  static function filter($recs, $from, $to) {
    $recs = parent::filter($recs, $from, $to);
    return array_filter($recs, function($rec) {
      return 
        $rec->hasAny('Cancer', 'Trauma', 'Drug', 'Neuro') ||
        $rec->has2BackPainsWithin180();        
    });
  }
  //
  public function has2BackPainsWithin180() {
    foreach (gets($this, 'BackPains') as $diag) {
      if (abs(daysBetween($this->BackPain->date, $diag->date)) <= 180)
        return true;
    }
  }
}
class Client166_Numer extends Client166_Ipp {
  //
  static function asCriteria($ugid, $from, $to, $uid) {
    $c = parent::asCriteria($ugid, $from, $to, $uid);
    $c->Study = CriteriaJoin::optionalAsArray(Proc166::asDiagStudy($to, $uid));
    return $c;
  }
  static function filter($recs, $from, $to) {
    $recs = parent::filter($recs, $from, $to);
    return array_filter($recs, function($rec) {
      return $rec->hasNoStudy();
    });
  }
  //
  public function hasNoStudy() {
    foreach (gets($this, 'Study') as $proc) {
      $days = daysBetween($this->BackPain->date, $proc->date);
      if ($days >= 0 && $days <= 28)
        return false;
    }
    return true;
  }
}
class Proc166 extends Proc_Cqm {
  //
  static function asDiagStudy($before, $uid) {
    return static::from(null, $before, $uid)->ipcs('602440','602441','602442');
  }
}
class Diag166 extends Diag_Cqm {
  //
  static function asBackPain($before) {
    $c = static::from(null, $before)->icds('721.3','722.10','722.32','722.52','722.93','724.02','724.03','724.2','724.3','724.5','724.6','724.70','738.5','739.3','739.4','846.0','846.1','846.2','846.3','846.8','846.9','847.2');
    return $c;
  }
  static function asTrauma($from, $to) {
    $c = static::from($from, $to)->icds('800.00','800.01','800.02','800.03','800.04','800.05','800.06','800.09','800.10','800.11','800.12','800.13','800.14','800.15','800.16','800.19','800.20','800.21','800.22','800.23','800.24','800.25','800.26','800.29','800.30','800.31','800.32','800.33','800.34','800.35','800.36','800.39','800.40','800.41','800.42','800.43','800.44','800.45','800.46','800.49','800.50','800.51','800.52','800.53','800.54','800.55','800.56','800.59','800.60','800.61','800.62','800.63','800.64','800.65','800.66','800.69','800.70','800.71','800.72','800.73','800.74','800.75','800.76','800.79','800.80','800.81','800.82','800.83','800.84','800.85','800.86','800.89','800.90','800.91','800.92','800.93','800.94','800.95','800.96','800.99','801.00','801.01','801.02','801.03','801.04','801.05','801.06','801.09','801.10','801.11','801.12','801.13','801.14','801.15','801.16','801.19','801.20','801.21','801.22','801.23','801.24','801.25','801.26','801.29','801.30','801.31','801.32','801.33','801.34','801.35','801.36','801.39','801.40','801.41','801.42','801.43','801.44','801.45','801.46','801.49','801.50','801.51','801.52','801.53','801.54','801.55','801.56','801.59','801.60','801.61','801.62','801.63','801.64','801.65','801.66','801.69','801.70','801.71','801.72','801.73','801.74','801.75','801.76','801.79','801.80','801.81','801.82','801.83','801.84','801.85','801.86','801.89','801.90','801.91','801.92','801.93','801.94','801.95','801.96','801.99','802.0','802.1','802.20','802.21','802.22','802.23','802.24','802.25','802.26','802.27','802.28','802.29','802.30','802.31','802.32','802.33','802.34','802.35','802.36','802.37','802.38','802.39','802.4','802.5','802.6','802.7','802.8','802.9','803.00','803.01','803.02','803.03','803.04','803.05','803.06','803.09','803.10','803.11','803.12','803.13','803.14','803.15','803.16','803.19','803.20','803.21','803.22','803.23','803.24','803.25','803.26','803.29','803.30','803.31','803.32','803.33','803.34','803.35','803.36','803.39','803.40','803.41','803.42','803.43','803.44','803.45','803.46','803.49','803.50','803.51','803.52','803.53','803.54','803.55','803.56','803.59','803.60','803.61','803.62','803.63','803.64','803.65','803.66','803.69','803.70','803.71','803.72','803.73','803.74','803.75','803.76','803.79','803.80','803.81','803.82','803.83','803.84','803.85','803.86','803.89','803.90','803.91','803.92','803.93','803.94','803.95','803.96','803.99','804.00','804.01','804.02','804.03','804.04','804.05','804.06','804.09','804.10','804.11','804.12','804.13','804.14','804.15','804.16','804.19','804.20','804.21','804.22','804.23','804.24','804.25','804.26','804.29','804.30','804.31','804.32','804.33','804.34','804.35','804.36','804.39','804.40','804.41','804.42','804.43','804.44','804.45','804.46','804.49','804.50','804.51','804.52','804.53','804.54','804.55','804.56','804.59','804.60','804.61','804.62','804.63','804.64','804.65','804.66','804.69','804.70','804.71','804.72','804.73','804.74','804.75','804.76','804.79','804.80','804.81','804.82','804.83','804.84','804.85','804.86','804.89','804.90','804.91','804.92','804.93','804.94','804.95','804.96','804.99','805.00','805.01','805.02','805.03','805.04','805.05','805.06','805.07','805.08','805.10','805.11','805.12','805.13','805.14','805.15','805.16','805.17','805.18','805.2','805.3','805.4','805.5','805.6','805.7','805.8','805.9','806.00','806.01','806.02','806.03','806.04','806.05','806.06','806.07','806.08','806.09','806.10','806.11','806.12','806.13','806.14','806.15','806.16','806.17','806.18','806.19','806.20','806.21','806.22','806.23','806.24','806.25','806.26','806.27','806.28','806.29','806.30','806.31','806.32','806.33','806.34','806.35','806.36','806.37','806.38','806.39','806.4','806.5','806.60','806.61','806.62','806.69','806.70','806.71','806.72','806.79','806.8','806.9','807.00','807.01','807.02','807.03','807.04','807.05','807.06','807.07','807.08','807.09','807.10','807.11','807.12','807.13','807.14','807.15','807.16','807.17','807.18','807.19','807.2','807.3','807.4','807.5','807.6','808.0','808.1','808.2','808.3','808.41','808.42','808.43','808.49','808.51','808.52','808.53','808.59','808.8','808.9','809.0','809.1','810.00','810.01','810.02','810.03','810.10','810.11','810.12','810.13','811.00','811.01','811.02','811.03','811.09','811.10','811.11','811.12','811.13','811.19','812.00','812.01','812.02','812.03','812.09','812.10','812.11','812.12','812.13','812.19','812.20','812.21','812.30','812.31','812.40','812.41','812.42','812.43','812.44','812.49','812.50','812.51','812.52','812.53','812.54','812.59','813.00','813.01','813.02','813.03','813.04','813.05','813.06','813.07','813.08','813.10','813.11','813.12','813.13','813.14','813.15','813.16','813.17','813.18','813.20','813.21','813.22','813.23','813.30','813.31','813.32','813.33','813.40','813.41','813.42','813.43','813.44','813.45','813.46','813.47','813.50','813.51','813.52','813.53','813.54','813.80','813.81','813.82','813.83','813.90','813.91','813.92','813.93','814.00','814.01','814.02','814.03','814.04','814.05','814.06','814.07','814.08','814.09','814.10','814.11','814.12','814.13','814.14','814.15','814.16','814.17','814.18','814.19','815.00','815.01','815.02','815.03','815.04','815.09','815.10','815.11','815.12','815.13','815.14','815.19','816.00','816.01','816.02','816.03','816.10','816.11','816.12','816.13','817.0','817.1','818.0','818.1','819.0','819.1','820.00','820.01','820.02','820.03','820.09','820.10','820.11','820.12','820.13','820.19','820.20','820.21','820.22','820.30','820.31','820.32','820.8','820.9','821.00','821.01','821.10','821.11','821.20','821.21','821.22','821.23','821.29','821.30','821.31','821.32','821.33','821.39','822.0','822.1','823.00','823.01','823.02','823.10','823.11','823.12','823.20','823.21','823.22','823.30','823.31','823.32','823.40','823.41','823.42','823.80','823.81','823.82','823.90','823.91','823.92','824.0','824.1','824.2','824.3','824.4','824.5','824.6','824.7','824.8','824.9','825.0','825.1','825.20','825.21','825.22','825.23','825.24','825.25','825.29','825.30','825.31','825.32','825.33','825.34','825.35','825.39','826.0','826.1','827.0','827.1','828.0','828.1','829.0','829.1','830.0','830.1','831.00','831.01','831.02','831.03','831.04','831.09','831.10','831.11','831.12','831.13','831.14','831.19','832.00','832.01','832.02','832.03','832.04','832.09','832.10','832.11','832.12','832.13','832.14','832.19','832.2','833.00','833.01','833.02','833.03','833.04','833.05','833.09','833.10','833.11','833.12','833.13','833.14','833.15','833.19','834.00','834.01','834.02','834.10','834.11','834.12','835.00','835.01','835.02','835.03','835.10','835.11','835.12','835.13','836.0','836.1','836.2','836.3','836.4','836.50','836.51','836.52','836.53','836.54','836.59','836.60','836.61','836.62','836.63','836.64','836.69','837.0','837.1','838.00','838.01','838.02','838.03','838.04','838.05','838.06','838.09','838.10','838.11','838.12','838.13','838.14','838.15','838.16','838.19','839.00','839.01','839.02','839.03','839.04','839.05','839.06','839.07','839.08','839.10','839.11','839.12','839.13','839.14','839.15','839.16','839.17','839.18','839.20','839.21','839.30','839.31','839.40','839.41','839.42','839.49','839.50','839.51','839.52','839.59','839.61','839.69','839.71','839.79','839.8','839.9','850.0','850.11','850.12','850.2','850.3','850.4','850.5','850.9','851.00','851.01','851.02','851.03','851.04','851.05','851.06','851.09','851.10','851.11','851.12','851.13','851.14','851.15','851.16','851.19','851.20','851.21','851.22','851.23','851.24','851.25','851.26','851.29','851.30','851.31','851.32','851.33','851.34','851.35','851.36','851.39','851.40','851.41','851.42','851.43','851.44','851.45','851.46','851.49','851.50','851.51','851.52','851.53','851.54','851.55','851.56','851.59','851.60','851.61','851.62','851.63','851.64','851.65','851.66','851.69','851.70','851.71','851.72','851.73','851.74','851.75','851.76','851.79','851.80','851.81','851.82','851.83','851.84','851.85','851.86','851.89','851.90','851.91','851.92','851.93','851.94','851.95','851.96','851.99','852.00','852.01','852.02','852.03','852.04','852.05','852.06','852.09','852.10','852.11','852.12','852.13','852.14','852.15','852.16','852.19','852.20','852.21','852.22','852.23','852.24','852.25','852.26','852.29','852.30','852.31','852.32','852.33','852.34','852.35','852.36','852.39','852.40','852.41','852.42','852.43','852.44','852.45','852.46','852.49','852.50','852.51','852.52','852.53','852.54','852.55','852.56','852.59','853.00','853.01','853.02','853.03','853.04','853.05','853.06','853.09','853.10','853.11','853.12','853.13','853.14','853.15','853.16','853.19','854.00','854.01','854.02','854.03','854.04','854.05','854.06','854.09','854.10','854.11','854.12','854.13','854.14','854.15','854.16','854.19','860.0','860.1','860.2','860.3','860.4','860.5','861.00','861.01','861.02','861.03','861.10','861.11','861.12','861.13','861.20','861.21','861.22','861.30','861.31','861.32','862.0','862.1','862.21','862.22','862.29','862.31','862.32','862.39','862.8','862.9','863.0','863.1','863.20','863.21','863.29','863.30','863.31','863.39','863.40','863.41','863.42','863.43','863.44','863.45','863.46','863.49','863.50','863.51','863.52','863.53','863.54','863.55','863.56','863.59','863.80','863.81','863.82','863.83','863.84','863.85','863.89','863.90','863.91','863.92','863.93','863.94','863.95','863.99','864.00','864.01','864.02','864.03','864.04','864.05','864.09','864.10','864.11','864.12','864.13','864.14','864.15','864.19','865.00','865.01','865.02','865.03','865.04','865.09','865.10','865.11','865.12','865.13','865.14','865.19','866.00','866.01','866.02','866.03','866.10','866.11','866.12','866.13','867.0','867.1','867.2','867.3','867.4','867.5','867.6','867.7','867.8','867.9','868.00','868.01','868.02','868.03','868.04','868.09','868.10','868.11','868.12','868.13','868.14','868.19','869.0','869.1','905.0','905.1','905.2','905.3','905.4','905.5','905.6','905.7','905.8','905.9','906.0','906.1','906.2','906.3','906.4','906.5','906.6','906.7','906.8','906.9','907.0','907.1','907.2','907.3','907.4','907.5','907.9','908.0','908.1','908.2','908.3','908.4','908.5','908.6','908.9','909.0','909.1','909.2','909.3','909.4','909.5','909.9','926.11','926.12','929.0','929.9','952.00','952.01','952.03','952.04','952.05','952.06','952.07','952.08','952.09','952.10','952.11','952.12','952.13','952.14','952.15','952.16','952.17','952.18','952.19','952.2','952.3','952.4','952.8','952.9','958.0','958.1','958.2','958.3','958.4','958.5','958.6','958.7','958.8','958.90','958.91','958.92','958.93','958.99','959.01','959.09','959.11','959.12','959.13','959.14','959.19','959.2','959.3','959.4','959.5','959.6','959.7','959.8','959.9');
    return $c;
  }
  static function asDrug($from, $to) {
    $c = static::from($from, $to)->icds('304.00','304.01','304.02','304.03','304.10','304.11','304.12','304.13','304.20','304.21','304.22','304.23','304.40','304.41','304.42','304.43','305.40','305.41','305.42','305.43','305.50','305.51','305.52','305.53','305.60','305.61','305.62','305.63','305.70','305.71','305.72','305.73');
    return $c;
  }
  static function asNeuro($from, $to) {
    $c = static::from($from, $to)->icds('344.60','729.2');
    return $c;
  }
}