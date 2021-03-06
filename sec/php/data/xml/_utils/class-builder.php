<?php
require_once 'XmlParser.php';
//
if (isset($argv)) {
  $in = $argv[1];
  $out = $argv[2];
} else {
  $in = 'C:\Program Files (x86)\Apache Software Foundation\Apache2.2\htdocs\clicktate\sec\php\data\xml\_utils\PatientFullMedicationHistoryV6.xsd';
  $out = null;
  echo '<pre>';
}
echo "Schema: $in\n";
echo "Output: $out\n";
$schema = new Schema($in);
if ($schema == null) {
  echo 'ERROR: Schema not found.\n';
  exit;
}
echo "Processing $in...\n";
$php = $schema->toPHP();
if ($out) {
  $fo = @fopen($out, 'w');
  fputs($fo, "<?php\n$php\n?>");
  fclose($fo);
  echo "Wrote $out\n";
} else {
  print_r($php);
  echo '</pre>';
}
/**
 * XML Schema
 */
class Schema {
  //
  public $xml;
  public $root;
  public $prefix;
  public $complexTypes; 
  //
  public function __construct($filename) {
    $dom = new DOMDocument();
    $dom->load($filename);
    $root = $dom->documentElement;
    $this->prefix = $this->getPrefix($root);
    $this->xml = XmlParser::parse($dom->saveXML($root));
    $this->root = $this->xml->{$root->nodeName};
    $this->complexTypes = $this->getComplexTypes(); 
  } 
  public function toPHP() {
    $out = array();
    Schema::arrayToPHP($out, $this->complexTypes);
    return implode("\n", $out);
  }
  //
  private function getComplexTypes() {
    return ComplexType::from($this->root, $this->prefix);
  }
  private function getPrefix($root) {
    $n = explode(':', $root->nodeName);
    if (count($n) > 1) 
      return $n[0];
  }
  //
  public static function get(&$o, $node, $default = null) {
    if (isset($o->$node))
      return $o->$node; 
    else
      return $default;
  }
  public static function getArray($o, $node) {
    if (isset($o->$node))
      if (is_array($o->$node)) 
        return $o->$node;
      else
        return array($o->$node);
  }
  public static function getRecursive($o, $node, $class, &$array = null) {
    if ($array == null) 
      $array = array();
    foreach ($o as $fid => $value) {
      if ($fid === $node) {
        $recs = self::getArray($o, $node);
        foreach ($recs as $rec)
          $array[$rec->name] = new $class($rec);
      } else if ($value !== null && ! is_scalar($value)) {
        self::getRecursive($value, $node, $class, $array);
      }
    }
    return array_values($array);
  }
  public static function arrayToPHP(&$out, $array, $comment = null) {
    if ($array) { 
      if ($comment) 
        $out[] = $comment;    
      foreach ($array as $o) 
        $out[] = $o->toPHP();
    }
  }
  public static function formatPHPType($name) {
    return str_replace('.', '_', $name);
  }
} 
class ComplexType {
  //
  public $name;
  public $mixed;
  public $extends;
  public $attributes;
  public $elements;
  //
  public function __construct($o, $prefix) {
    $this->name = $o->name;
    $this->mixed = Schema::get($o, 'mixed');
    $this->attributes = Attribute::from($o, $prefix);
    $this->elements = Element::from($o, $prefix, $this->mixed);
    $oc = Schema::get($o, "$prefix:complexContent");
    if ($oc) {
      $ocr = Schema::get($oc, "$prefix:restriction");
      if ($ocr) {
        $this->extends = $ocr->base;
        $this->attributes = Attribute::getFixed($this->attributes);
      } else {
        $oce = Schema::get($oc, "$prefix:extension");
        $this->extends = $oce->base;
      }
    } else {
      $this->extends = 'XmlRec';
    }
  }
  public function toPHP() {
    $out = array();
    $out[] = $this->formatPHPClass();
    Schema::arrayToPHP($out, $this->attributes); 
    Schema::arrayToPHP($out, $this->elements, '  //');
    $out[] = '}';
    return implode("\n", $out); 
  }
  //
  private function formatPHPClass() {
    $out = 'class '. Schema::formatPHPType($this->name);
    if ($this->extends) 
      $out .= ' extends ' . Schema::formatPHPType($this->extends);
    return "$out {";
  }
  //
  public static function from($o, $prefix) {
    $array = Schema::getArray($o, "$prefix:complexType");
    if ($array) 
      foreach ($array as &$o) 
        $o = new ComplexType($o, $prefix);
    return $array;
  }
}
class Attribute {
  //
  public $name;
  public $type; 
  public $fixed;
  public $use;
  //
  public function __construct($o) {
    $this->name = $o->name;
    $this->type = $o->type;
    $this->fixed = Schema::get($o, 'fixed');
    $this->use = Schema::get($o, 'use', 'optional');
  }
  public function isRequired() {
    return $this->use == 'required';
  }
  public function toPHP() {
    $out = array();
    $out[] = "  public /*$this->type*/ \$_$this->name";
    if ($this->fixed && $this->isRequired())
      $out[] = " = \"$this->fixed\"";
    $out[] = ";";
    if ($this->isRequired())
      $out[] = ' //REQ';     
    return implode('', $out); 
  }
  //
  public static function from($o, $prefix) {
    return Schema::getRecursive($o, "$prefix:attribute", 'Attribute');
  }
  public static function getFixed($array) {
    if ($array) {
      $recs = array();
      foreach ($array as $attr) 
        if ($attr->fixed)
          $recs[] = $attr; 
      return $recs;
    }
  }
}
class Element {
  //
  public $name;
  public $minOccurs;
  public $maxOccurs;
  public $type;
  //
  public function __construct($o = null) {
    if ($o) {
      $this->name = $o->name;
      $this->type = Schema::get($o, 'type');
      $this->minOccurs = Schema::get($o, 'minOccurs', 1);
      $this->maxOccurs = Schema::get($o, 'maxOccurs', 1);
    }
  }
  public function isRequired() {
    return $this->minOccurs > 0;
  }
  public function isArray() {
    return $this->maxOccurs > 1 || $this->maxOccurs == 'unbounded';
  }
  public function toPHP() {
    $type = Schema::formatPHPType($this->type);
    if ($this->isArray())
      $type .= '[]';
    return "  public /*$type*/ \$$this->name;" . $this->formatComment();
  }
  private function formatComment() {
    if ($this->isRequired())
      return ' //REQ';
  }
  //
  public static function from($o, $prefix, $mixed) {
    self::processChoices($o, $prefix);
    $array = Schema::getRecursive($o, "$prefix:element", 'Element');
    if ($mixed) 
      $array[] = Element::asInnerText(); 
    return $array;
  }
  public static function getRequired($elements) {
    $reqs = array();
    foreach ($elements as $e) 
      if ($e->isRequired()) 
        $reqs[] = $e;
    return $reqs;
  }
  public static function asInnerText() {
    $e = new Element();
    $e->name = '_';
    $e->type = '(innerText)';
    return $e;
  }
  //
  private static function processChoices(&$o, $prefix) {
    foreach ($o as $fid => &$value) {
      if ($fid === "$prefix:choice")
        self::processChoice($value, $prefix);
      else if ($value !== null && ! is_scalar($value))
        self::processChoices($value, $prefix);
    }
  }
  private static function processChoice(&$o, $prefix) {
    $minOccurs = Schema::get($o, 'minOccurs');
    $elements = Schema::get($o, "$prefix:element");
    if ($minOccurs == '0' && $elements) {
      if (is_array($elements)) 
        foreach ($elements as &$e)
          $e->minOccurs = 0;
      else
        $elements->minOccurs = 0;
    }
  }
}
?>