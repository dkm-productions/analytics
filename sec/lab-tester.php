<?php
require_once 'inc/requireLogin.php';
require_once 'php/data/hl7/msg/_HL7Message.php';
require_once 'php/data/rec/sql/HL7_Labs.php';
require_once 'php/data/rec/sql/Facesheets.php';
require_once 'php/data/hl7/msg/VXUMessage.php';
require_once 'php/data/hl7/msg/ADTMessage.php';
require_once 'php/data/xml/pqri/PQRI.php';
//
?>
<html>
  <head>
    <script language="JavaScript1.2" src="js/ui.js"></script>
    <script language="JavaScript1.2" src="js/_ui/Templates.js"></script>
  </head>
  <body>
<?php 
echo '<pre>';
switch ($_GET['t']) {
  case '0':
    $data = <<<eos
MSH|^~\&||Amazing Clinical^24D0404292^CLIA|MIDOH|MI|201107280941||ORU^R01|20080320031629921238|P|2.3.1
PID|||246^^^^^Columbia Valley Memorial Hospital&01D0355944&CLIA~95101100001^^^^^MediLabCo-Seattle&45D0470381&CLIA||sara^blaunt^Q^Jr|Clemmons|19900602|F||W|2166WellsDr^AptB^Seattle^WA^98109||^^^^^206^6793240|||M|||423523049||U|N
PV1|1||||||9999^Account^Test^^^^^PHYID||||||||U||||||||||||||||||||||||
ORC|RE||||P
OBR|1||SER122145|^^^78334^Chemistry, serum sodium^meq/l|||201107280941||||||||BLDV|^Welby^M^J^Jr^Dr^MD|^WPN^PH^^^206^4884144||||||||F
OBX|1|NM|2951-2^SODIUM,SERUM^LN^230-007^Na&CLIA|1|141|meq/l|135-146||||F|||||^Doe^John|||||||Oakton Crest Laboratories|5570 Eden Street^^Oakland^California^94607|8006315250|
OBR|2||SER122145|^^^27760-3^POTASSIUM,SERUM^LN^230-006^K^CLIA|1|4.5|meq/l|3.4-5.3|||201107280941
OBX|2|NM|22760-3^POTASSIUM,SERUM^LN^230-006^K^CLIA|1|4.5|meq/l|3.4-5.3|N|||F|||||^Doe^John|||||||Oakton Crest Laboratories|5570 Eden Street^^Oakland^California^94607|8006315250|||201107280941
eos;
    $data = <<<eos
MSH|^~\&|RD|MAYO CLINIC DEPT. OF LAB MED AND PATHOLOGY^24D0404292^CLIA|MIDOH|MI|200803200316||ORU^R01|20080320031629921238|P|2.3.1
PID|1||||D9419991^FIRSTNAME^MIDDLE||19280205|F||U^^HL7 005^^^L|STREET1^STREET2^CITY^^ZIPCODE^COUNTRY^^^COUNTY
NK1|1|NEXT OF KIN|||^^^^^^
ORC|||||||||||||||||||||NORTHERN MICHIGAN HOSPITAL|416 CONNABLE AVENUE^^PETOSKEY^MI^49770-2297|^^^^^231^4874219|416 CONNABLE AVENUE^^PETOSKEY^MI^49770-2297
OBR|1||D9499161|^^^82048^HSV AND VZV-PCR, DERMAL^L|||200803171356||||||DETECTION OF HSV AND VZV BY LIGHTCYCLER POLYMERASE CHAIN REACTION (PCR) (PCR IS UTILIZED PURSUANT TO A LICENSE AGREEMENT WITH ROCHE MOLECULAR SYSTEM, INC.) THIS TEST DISTINGUISHES HSV-1 FROM HSV-2 GENOTYPES.|200803181058|^^SKIN SWAB|55005^SMITS Q^^^^|^^^^^^|||||200803191028|||F
OBX|1|ST|82048^HSV AND VZV-PCR, DERMAL^MAYO||SEE COMMENT FOR RESULTS||||||F|||20080319102800|24D0404292^MAYO CLINIC DEPT. OF LAB MED AND PATHOLOGY^CLIA
NTE|1||HERPES SIMPLEX TYPE I DNA DETECTED
NTE|2||VARICELLA-ZOSTER VIRUS DNA DETECTED
eos;
    $data = <<<eos
MSH|^~\&||MA0000||GA0000|19970901||VXU^V04|19970522MA53|T|2.3.1|||AL
PID|||1234^^^^SR^~1234-12^^^^LR^~3872^^^^MR~221345671^^^^SS^~430078856^^^^MA^ ||KENNEDY^JOHN^FITZGERALD^JR^^^L|BOUVIER^^^^^^M|19900607|M|KENNEDY^BABY BOY^^^^^^ B|W^WHITE^NY8 RACE CODES^W^WHITE^HL70005|123 MAIN ST^APT 3B^LEXINGTON^MA^00210^ ^M^MSA CODE^MA034~345 ELM ST^^BOSTON^MA^00314^^BLD~^^^^^^BR^^MA002| |(617) 555-1212 ^PRN^PH^^^617^5551212^^||EN^ENGLISH^HL70296^^^|||||||WN^NOT HISPANIC^LOCAL CODE SET^NH^NOT OF HISPANIC ORIGIN^HL70189|CHILDREN=S HOSPITAL
NK1|1|KENNEDY^JACQUELINE^LEE|32^MOTHER^HL70063||||||||||||||||||||||||||||||898666725^^^^SS
NK1|2|KENNEDY^JOHN^FITZGERALD|33^FATHER^HL70063||||||||||||||||||||||||||||||822546618^^^^SS
PV1||R|||||||||||||||A|||V02^19900607~H02^19900607
RXA|0|1|19900607|19900607|08^HEPB-PEDIATRIC/ADOLESCENT^CVX^90744^HEPB-PEDATRIC/ADOLESCENT^CPT|.5|ML^^ISO+||03^HISTORICAL INFORMATION - FROM PARENT=S WRITTEN RECORD^NIP0001|^JONES^LISA|^^^CHILDREN=S HOSPITAL||5|MCG^^ISO+|MRK12345| 199206|MSD^MERCK^MVX
RXA|0|4|19910907|19910907|50^DTAP-HIB^CVX^90721^DTAP-HIB^CPT|.5|ML^^ISO+||00^NEW IMMUNIZATION RECORD^NIP0001|1234567890^SMITH^SALLY^S^^^^^^^^^VEI~1234567891 ^O=BRIAN^ROBERT^A^^DR^MD^^^^^^OEI|^^^CHILD HEALTHCARE CLINIC^^^^^101 MAIN STREET^^ BOSTON^MA||||W46932777|199208|PMC^PASTEUR MERIEUX CONNAUGHT^MVX|||CP|A| 19910907120030
RXR|IM^INTRAMUSCULAR^HL70162|LA^LEFT ARM^HL70163
RXA|0|1|19910907|19910907|03^MMR^CVX|.5|ML^^ISO+|||1234567890^SMITH^SALLY^S^^^^^^^^^VEI~1234567891^O=BRIAN^ROBERT^A^^DR^MD^^^^^^OEI|^^^CHILD HEALTHCARE CLINIC^^^^^101 MAIN STREET^^BOSTON^MA||||W2348796456|19920731|MSD^MERCK^MVX
RXR|SC^SUBCUTANEOUS^HL70162|LA^LEFT ARM^HL70163
RXA|0|5|19950520|19950520|20^DTAP^CVX|.5|ML^^ISO+|||1234567891^O=BRIAN^ROBERT^A^^DR|^^^CHILD HEALTHCARE CLINIC^^^^^101 MAIN STREET^^BOSTON^MA||||W22532806|19950705|PMC^ PASTEUR MERIEUX CONNAUGHT^MVX
RXR|IM^INTRAMUSCULAR^HL70162|LA^LEFT ARM^HL70163
NTE|PATIENT DEVELOPED HIGH FEVER APPROX 3 HRS AFTER VACCINE INJECTION
RXA|0|2|19950520|19950520|03^MMR^CVX|.5|ML^^ISO+|||1234567891^O=BRIAN^ROBERT^A^^DR|^^^CHILD HEALTHCARE CLINIC^^^^^101 MAIN STREET^^BOSTON^MA||||W2341234567|19950630| MSD^MERCK^MVX
RXR|SC^SUBCUTANEOUS^HL70162|LA^LEFT ARM^HL70163
eos;
    $msg = HL7Message::fromHL7($data);
    p_r($msg);
    $hl7 = $msg->toHL7();
    p_r($hl7);
    exit;
  case '1':
    $data = <<<eos
MSH|^~\&||||999997|201012030736||ORU^R01|002056206356183038|P|2.3
PID||PF0009|||TEST^ALLFIELDS^M^JR^MR||19710923|M|TESTY^^||123 MAIN ST^APT 4J, MIDDLE ROOM^NEW YORK^NY^10021^||(212)555-1212|||D|||123456789|NY18882882
PV1|1||||||9999^Account^Test^^^^^PHYID||||||||U||||||||||||||||||||||||
ORC|RE||||P
OBR|1|LE09999970000050||90093^Comp Metabolic Panel|||201011181357||||||stat order clinical comment|201011181425||^Account^Test^||||||201011181429|||F||^^^^^S
OBX|1||1090^Glucose||100|mg/dL|65 - 99|H|||F||||SHIEL
NTE|1||Criteria for the diagnosis of diabetes:
NTE|2||(Fasting Glucose)
NTE|3||<100 mg/dL: Normal fasting glucose
NTE|4||100-125 mg/dL: Impaired fasting glucose
NTE|5||>125 mg/dL: Indicative of diabetes
NTE|6||Ref: Diabetes Care 29:S43-S48, 2006
OBX|2||1168^Urea Nitrogen||100|mg/dL|7 - 25|HH|||F||||
OBX|3||1066^Creatinine||5.00|mg/dL|0.5 - 1.3|HH|||F||||
OBX|4||10662^eGFR (calculation)||13||>60 -|L|||F||||
NTE|1||For African-Americans, multiply EGFR result  x 1.2
OBX|5||1039^BUN/Creat Ratio||20.0||5.3 - 50.0||||F||||
OBX|6||1147^Sodium||<110|mEq/L|135 - 146|LL|||F||||
OBX|7||1135^Potassium||5.0|mmol/L|3.5 - 5.5||||F||||
OBX|8||1048^Chloride||<80|mEq/L|98 - 110|LL|||F||||
OBX|9||1045^Carbon Dioxide||<10|mEq/L|21 - 33|LL|||F||||
OBX|10||1042^Calcium||55.0|mg/dL|8.6 - 10.4|HH|||F||||
OBX|11||1144^Protein, Total||5.0|g/dL|6.2 - 8.3|L|||F||||
OBX|12||1009^Albumin||5.0|g/dL|3.6 - 5.3||||F||||
OBX|13||1087^Globulin||0.0|g/dL|2.1 - 3.7|L|||F||||
OBX|14||1003^A/G Ratio||0.0|Ratio|||||F||||
OBX|15||1012^Alkaline Phosphatase||5|IU/L|40 - 115|L|||F||||
OBX|16||1027^AST (SGOT)||5|IU/L|10 - 40|L|||F||||
OBX|17||1018^ALT (SGPT)||5|IU/L|9 - 60|L|||F||||
OBX|18||1036^Bilirubin, Total||5.0|mg/dL|0.2 - 1.2|HH|||F||||
OBR|2|LE09999970000050||4126^TSH|||201011181357||||||stat order clinical comment|201011181425||^Account^Test^||||||201011181429|||F||^^^^^S
OBX|1||4126^TSH 3rd Generation Ultra||5.00|uIU/mL|0.55 - 4.78|H|||F||||
OBR|3|LE09999970000050||90138^Hemogram inc. Platelets|||201011181357||||||stat order clinical comment|201011181425||^Account^Test^||||||201011181429|||F||^^^^^S
OBX|1||2252^White Blood Count||10.0|x10 3/uL|4.0 - 11.0||||F||||
OBX|2||2210^Red Blood Count||10.00|x10 6/uL|4.2 - 6.0|HH|||F||||
OBX|3||2090^Hemoglobin||10.0|g/dL|12.6 - 16.4|L|||F||||
OBX|4||2087^Hematocrit||10.0|%|38 - 52|LL|||F||||
OBX|5||2186^Platelets||10|x10 3/uL|150 - 450|LL|||F||||
OBX|6||2186^PlateVHts||10|x10 3/uL|150 - 450|LL|||F||||
OBR|4|LE09999970000050||2219^Reticulocyte Count|||201011181357||||||stat order clinical comment|201011181425||^Account^Test^||||||201011181429|||F||^^^^^S
OBX|1||2219^Reticulocytes||10.0|%|0.5 - 2.5|HH|||F||||
OBR|5|LE09999970000050||90204^Protime with Inr|||201011181357||||||stat order clinical comment|201011181425||^Account^Test^||||||201011181429|||F||^^^^^S
OBX|1||2102^INR||5.00|Ratio|2.0 - 3.0|HH|||F||||
NTE|1||Recommended therapeutic range: 2.0 - 3.0 Acute M.I., prophylaxis and treatment of venous thrombosis, pulmonary embolism, tissue heart valve, atrial fibrillation, valvular heart disease, prevention of systemic embolism. 2.5 - 3.5 Mechanical Heart Valve.
NTE|2||An INR reference interval of 0.8-1.3 is applicabVH to patients not receiving anti-coagulant medication.
OBR|6|LE09999970000050||90349^Urogram|||201011181357||||||stat order clinical comment|201011181425||^Account^Test^||||||201011181429|||F||^^^^^S
OBX|1||5015^Color, Urine||Yellow||Yellow - Straw||||F||||
OBX|2||5003^Appearance, Urine||CVHar||CVHar - CVHar||||F||||
OBX|3||5054^pH, Urine||5.0||5.0 - 8.0||||F||||
OBX|4||5063^Specific Gravity||10.000|R.I.|1.005 - 1.030|H|||F||||
OBX|5||5009^Bilirubin, Urine||NEGATIVE||NEGATIVE - NEGATIVE||||F||||
OBX|6||5012^Blood, Urine||NEGATIVE||NEGATIVE - TRACE||||F||||
OBX|7||5042^Leuk. Esterase, U||NEGATIVE||NEGATIVE - NEGATIVE||||F||||
OBX|8||5048^Nitrites, Urine||NEGATIVE||NEGATIVE - NEGATIVE||||F||||
OBX|9||5027^Glucose, Urine||NEGATIVE|mg/dL|NEGATIVE - NEGATIVE||||F||||
OBX|10||5039^Ketones, Urine||NEGATIVE|mg/dL|NEGATIVE - NEGATIVE||||F||||
OBX|11||5057^Protein, Urine||NEGATIVE|mg/dL|NEGATIVE - NEGATIVE||||F||||
OBX|12||5069^Urobilinogen, U||0.2|mg/dL|0 - 1.0||||F||||
OBX|13||5042^VHuk. Esterase, U||NEGATIVE||NEGATIVE - NEGATIVE||||F||||
OBR|7|LE09999970000050||90158^Lipid Panel|||201011181357||||||stat order clinical comment|201011181425||^Account^Test^||||||201011181429|||F||^^^^^S
OBX|1||1054^Cholesterol||<25|mg/dL|125 - 200|L|||F||||
OBX|2||1099^HDL Cholesterol||<10|mg/dL|39.9 -|L|||F||||
OBX|3||1057^Cholesterol/HDL||1.0|Ratio|less than - 4.99||||F||||
OBX|4||1000^HDL Chol (%)||100.0|%|15.0 -||||F||||
OBX|5||1114^LDL Cholesterol (Calculated)||-1|mg/dL|less than - 130|LL|||F||||
OBX|6||1183^VLDL Cholesterol (Calculated)||1||8 - 35|L|||F||||
OBX|7||1162^Triglycerides||<10|mg/dL|VHss than - 150|LL|||F||||
OBX|8||1054^ChoVHsterol||<25|mg/dL|125 - 200|L|||F||||
OBX|9||1099^HDL ChoVHsterol||<10|mg/dL|39.9 -|L|||F||||
OBX|10||1057^ChoVHsterol/HDL||1.0|Ratio|VHss than - 4.99||||F||||
OBX|11||1114^LDL ChoVHsterol (Calculated)||-1|mg/dL|VHss than - 130|LL|||F||||
OBX|12||1183^VLDL ChoVHsterol (Calculated)||1||8 - 35|L|||F||||
FTS|1|End Of File
eos;
    $msg = HL7Message::fromHL7($data);
    p_r($msg);
    //$rec = HL7_Labs::receive($data, 'LABCORP', 1);
    exit;
  case '2':
    $data = <<<eos
MSH|^~\&||||999997|201012030736||ORU^R01|002056206356183038|P|2.3
PID||PF0009|||TEST^ALLFIELDS^M^JR^MR||19710923|M|TESTY^^||123 MAIN ST^APT 4J, MIDDLE ROOM^NEW YORK^NY^10021^||(212)555-1212|||D|||123456789|NY18882882
PV1|1||||||9999^Account^Test^^^^^PHYID||||||||U||||||||||||||||||||||||
ORC|RE||||P
OBR|1|LE09999970000050||90093^Comp Metabolic Panel|||201011181357||||||stat order clinical comment|201011181425||^Account^Test^||||||201011181429|||F||^^^^^S
OBX|1||1090^Glucose||100|mg/dL|65 - 99|H|||F||||SHIEL
NTE|1||Criteria for the diagnosis of diabetes:
NTE|2||(Fasting Glucose)
NTE|3||<100 mg/dL: Normal fasting glucose
NTE|4||100-125 mg/dL: Impaired fasting glucose
NTE|5||>125 mg/dL: Indicative of diabetes
NTE|6||Ref: Diabetes Care 29:S43-S48, 2006
OBX|2||1168^Urea Nitrogen||100|mg/dL|7 - 25|HH|||F||||
OBX|3||1066^Creatinine||5.00|mg/dL|0.5 - 1.3|HH|||F||||
OBX|4||10662^eGFR (calculation)||13||>60 -|L|||F||||
NTE|1||For African-Americans, multiply EGFR result  x 1.2
OBX|5||1039^BUN/Creat Ratio||20.0||5.3 - 50.0||||F||||
OBX|6||1147^Sodium||<110|mEq/L|135 - 146|LL|||F||||
OBX|7||1135^Potassium||5.0|mmol/L|3.5 - 5.5||||F||||
OBX|8||1048^Chloride||<80|mEq/L|98 - 110|LL|||F||||
OBX|9||1045^Carbon Dioxide||<10|mEq/L|21 - 33|LL|||F||||
OBX|10||1042^Calcium||55.0|mg/dL|8.6 - 10.4|HH|||F||||
OBX|11||1144^Protein, Total||5.0|g/dL|6.2 - 8.3|L|||F||||
OBX|12||1009^Albumin||5.0|g/dL|3.6 - 5.3||||F||||
OBX|13||1087^Globulin||0.0|g/dL|2.1 - 3.7|L|||F||||
OBX|14||1003^A/G Ratio||0.0|Ratio|||||F||||
OBX|15||1012^Alkaline Phosphatase||5|IU/L|40 - 115|L|||F||||
OBX|16||1027^AST (SGOT)||5|IU/L|10 - 40|L|||F||||
OBX|17||1018^ALT (SGPT)||5|IU/L|9 - 60|L|||F||||
OBX|18||1036^Bilirubin, Total||5.0|mg/dL|0.2 - 1.2|HH|||F||||
OBR|2|LE09999970000050||4126^TSH|||201011181357||||||stat order clinical comment|201011181425||^Account^Test^||||||201011181429|||F||^^^^^S
OBX|1||4126^TSH 3rd Generation Ultra||5.00|uIU/mL|0.55 - 4.78|H|||F||||
OBR|3|LE09999970000050||90138^Hemogram inc. Platelets|||201011181357||||||stat order clinical comment|201011181425||^Account^Test^||||||201011181429|||F||^^^^^S
OBX|1||2252^White Blood Count||10.0|x10 3/uL|4.0 - 11.0||||F||||
OBX|2||2210^Red Blood Count||10.00|x10 6/uL|4.2 - 6.0|HH|||F||||
OBX|3||2090^Hemoglobin||10.0|g/dL|12.6 - 16.4|L|||F||||
OBX|4||2087^Hematocrit||10.0|%|38 - 52|LL|||F||||
OBX|5||2186^Platelets||10|x10 3/uL|150 - 450|LL|||F||||
OBX|6||2186^PlateVHts||10|x10 3/uL|150 - 450|LL|||F||||
OBR|4|LE09999970000050||2219^Reticulocyte Count|||201011181357||||||stat order clinical comment|201011181425||^Account^Test^||||||201011181429|||F||^^^^^S
OBX|1||2219^Reticulocytes||10.0|%|0.5 - 2.5|HH|||F||||
OBR|5|LE09999970000050||90204^Protime with Inr|||201011181357||||||stat order clinical comment|201011181425||^Account^Test^||||||201011181429|||F||^^^^^S
OBX|1||2102^INR||5.00|Ratio|2.0 - 3.0|HH|||F||||
NTE|1||Recommended therapeutic range: 2.0 - 3.0 Acute M.I., prophylaxis and treatment of venous thrombosis, pulmonary embolism, tissue heart valve, atrial fibrillation, valvular heart disease, prevention of systemic embolism. 2.5 - 3.5 Mechanical Heart Valve.
NTE|2||An INR reference interval of 0.8-1.3 is applicabVH to patients not receiving anti-coagulant medication.
OBR|6|LE09999970000050||90349^Urogram|||201011181357||||||stat order clinical comment|201011181425||^Account^Test^||||||201011181429|||F||^^^^^S
OBX|1||5015^Color, Urine||Yellow||Yellow - Straw||||F||||
OBX|2||5003^Appearance, Urine||CVHar||CVHar - CVHar||||F||||
OBX|3||5054^pH, Urine||5.0||5.0 - 8.0||||F||||
OBX|4||5063^Specific Gravity||10.000|R.I.|1.005 - 1.030|H|||F||||
OBX|5||5009^Bilirubin, Urine||NEGATIVE||NEGATIVE - NEGATIVE||||F||||
OBX|6||5012^Blood, Urine||NEGATIVE||NEGATIVE - TRACE||||F||||
OBX|7||5042^Leuk. Esterase, U||NEGATIVE||NEGATIVE - NEGATIVE||||F||||
OBX|8||5048^Nitrites, Urine||NEGATIVE||NEGATIVE - NEGATIVE||||F||||
OBX|9||5027^Glucose, Urine||NEGATIVE|mg/dL|NEGATIVE - NEGATIVE||||F||||
OBX|10||5039^Ketones, Urine||NEGATIVE|mg/dL|NEGATIVE - NEGATIVE||||F||||
OBX|11||5057^Protein, Urine||NEGATIVE|mg/dL|NEGATIVE - NEGATIVE||||F||||
OBX|12||5069^Urobilinogen, U||0.2|mg/dL|0 - 1.0||||F||||
OBX|13||5042^VHuk. Esterase, U||NEGATIVE||NEGATIVE - NEGATIVE||||F||||
OBR|7|LE09999970000050||90158^Lipid Panel|||201011181357||||||stat order clinical comment|201011181425||^Account^Test^||||||201011181429|||F||^^^^^S
OBX|1||1054^Cholesterol||<25|mg/dL|125 - 200|L|||F||||
OBX|2||1099^HDL Cholesterol||<10|mg/dL|39.9 -|L|||F||||
OBX|3||1057^Cholesterol/HDL||1.0|Ratio|less than - 4.99||||F||||
OBX|4||1000^HDL Chol (%)||100.0|%|15.0 -||||F||||
OBX|5||1114^LDL Cholesterol (Calculated)||-1|mg/dL|less than - 130|LL|||F||||
OBX|6||1183^VLDL Cholesterol (Calculated)||1||8 - 35|L|||F||||
OBX|7||1162^Triglycerides||<10|mg/dL|VHss than - 150|LL|||F||||
OBX|8||1054^ChoVHsterol||<25|mg/dL|125 - 200|L|||F||||
OBX|9||1099^HDL ChoVHsterol||<10|mg/dL|39.9 -|L|||F||||
OBX|10||1057^ChoVHsterol/HDL||1.0|Ratio|VHss than - 4.99||||F||||
OBX|11||1114^LDL ChoVHsterol (Calculated)||-1|mg/dL|VHss than - 130|LL|||F||||
OBX|12||1183^VLDL ChoVHsterol (Calculated)||1||8 - 35|L|||F||||
FTS|1|End Of File
eos;
    $msg = HL7Message::fromHL7($data);
    //$rec = HL7_Labs::receive($data, 'LABCORP', 1);
    p_r($msg);
    exit;
  case '3':
    $data = <<<eos
MSH|^~\&||||999997|201012030808||ORU^R01|002056206358138421|P|2.3
PID||1126412|1126412||PATIENT^TEST||19320331|F|||400 South Service Road^^MELVILLE^NY^11747^|||||||1126412|||
PV1|1||||||^^^^^^^PHYID||||||||U||||||||||||||||||||||||
ORC|RE||||P
OBR|1|C10101192||1093^Glucose, Fasting|||201009090435|||||||201009091017||^^^||||||201009091300|||F
OBX|1||1093^Glucose (grey)||112|mg/dL|65 - 99|H|||F||||SHIEL
NTE|1||Fasting
NTE|2||Criteria for the diagnosis of diabetes:
NTE|3||(Fasting Glucose)
NTE|4||<100 mg/dL: Normal fasting glucose
NTE|5||100-125 mg/dL: Impaired fasting glucose
NTE|6||>125 mg/dL: Indicative of diabetes
NTE|7||Ref: Diabetes Care 29:S43-S48, 2006
OBR|2|C10101192||90093^Comp Metabolic Panel|||201009090435|||||||201009091017||^^^||||||201009091300|||F
OBX|1||1168^Urea Nitrogen||15|mg/dL|7 - 25||||F||||
OBX|2||1066^Creatinine||0.74|mg/dL|0.5 - 1.3||||F||||
OBX|3||10662^eGFR (calculation)||>60||>60 -||||F||||
NTE|1||For African-Americans, multiply EGFR result  x 1.2
OBX|4||1039^BUN/Creat Ratio||20.3||5.3 - 50.0||||F||||
OBX|5||1147^Sodium||144|mEq/L|135 - 146||||F||||
OBX|6||1135^Potassium||3.6|mmol/L|3.5 - 5.5||||F||||
OBX|7||1048^Chloride||112|mEq/L|98 - 110|H|||F||||
OBX|8||1045^Carbon Dioxide||25|mEq/L|21 - 33||||F||||
OBX|9||1042^Calcium||7.8|mg/dL|8.6 - 10.4|L|||F||||
OBX|10||1144^Protein, Total||5.0|g/dL|6.2 - 8.3|L|||F||||
NTE|1||Confirmed
OBX|11||1009^Albumin||2.8|g/dL|3.3 - 4.7|L|||F||||
OBX|12||1087^Globulin||2.2|g/dL|2.1 - 3.7||||F||||
OBX|13||1003^A/G Ratio||1.3|Ratio|||||F||||
OBX|14||1012^Alkaline Phosphatase||51|IU/L|33 - 130||||F||||
OBX|15||1027^AST (SGOT)||17|IU/L|10 - 35||||F||||
OBX|16||1018^ALT (SGPT)||17|IU/L|6 - 40||||F||||
OBX|17||1036^Bilirubin, Total||0.6|mg/dL|0.2 - 1.2||||F||||
OBR|3|C10101192||1123^Magnesium, Urine|||201009090435|||||||201009091017||^^^||||||201009091300|||F
OBX|1||1123^Magnesium||2.0|mg/dL|1.5 - 2.5||||F||||
OBR|4|C10101192||4141^Vitamin B12|||201009090435|||||||201009091017||^^^||||||201009091300|||I
OBX|1||4141^Vitamin B12||||||||I||||
OBR|5|C10101192||4051^Folic Acid|||201009090435|||||||201009091017||^^^||||||201009091300|||I
OBX|1||4051^Folate||||||||I||||
NTE|1||Reference Range:
NTE|2||Deficient <3.4 ng/mL
NTE|3||Indeterminate: 3.4 -5.4 ng/mL
NTE|4||Normal  >5.4 ng/mL
FTS|1|End Of File
eos;
    p_r($data);
    $msg = HL7Message::fromHL7($data);
    //p_r($msg);
    $hl7 = $msg->toHL7();
    p_r($hl7);
    exit;
  case '4':
    $data = <<<eos
MSH|^~\&|||||||VXU^V04|19970522MA53|P|2.3.1
PID|||221345671^^^^SS||KENNEDY^JOHN^FITZGERALD^JR|BOUVIER^^^^^^M|19900607|M|||^^^^MA^^^BLD
NK1|1|KENNEDY^JACQUELINE^LEE|32^MOTHER^HL70063
RXA|0|1|19900607|19900607|08^HEPB-PEDIATRIC/ADOLESCENT^CVX|.5|ML^^ISO+||||||||MRK12345||MSD^MERCK^MVX    
eos;
    $msg = HL7Message::fromHL7($data);
    p_r($msg);
    exit;
  case '5':
    $data = <<<eos
MSH|^~\&||||999997|201012030812||ORU^R01|002056206358347042|P|2.3
PID||PF111|||TEST^PATIENTBILL||19870419|M|||20 MAIN ST^^NEW YORK^NY^10021^||(212)555-1212|||||||
PV1|1||||||^^^^^^^PHYID||||||||U||||||||||||||||||||||||
ORC|RE||||F
OBR|1|LE09999970000048||90204^Protime with Inr|||201011152241|||||||201011181351||^Account^Test^||||||201011181354|||F
OBX|1||2102^INR||1.00|Ratio|2.0 - 3.0|L|||F||||
NTE|1||Recommended therapeutic range: 2.0 - 3.0 Acute M.I., prophylaxis and treatment of venous thrombosis, pulmonary embolism, tissue heart valve, atrial fibrillation, valvular heart disease, prevention of systemic embolism. 2.5 - 3.5 Mechanical Heart Valve.
NTE|2||An INR reference interval of 0.8-1.3 is applicabVH to patients not receiving anti-coagulant medication.
FTS|1|End Of File
eos;
    //p_r(json_encode($data));
    $rec = HL7_Labs::receive($data, 'LABCORP', 1);
    p_r($rec);
    exit;
  case '6':
    $recs = HL7_Labs::getInbox();
    p_r($recs);
    exit;
  case '7':
    $rec = HL7_Labs::getLabRecon(9);
    $r = jsondecode(jsonencode($rec));
    p_r($r->Msg);
    exit;  
  case '8':
    $rec = HL7_Labs::getLabRecon(9);
    p_r($rec->Client);
    exit;
  case '10':
    $fs = Facesheet_Hl7Immun::from(3485);
    //p_r($fs);
    $msg = VXUMessage::from($fs);
    p_r($msg, 'msg');
    $hl7 = $msg->toHL7();
    p_r($hl7);
    exit;
  case '11':
    $rec = Immun_HL7Codes::fetch(Immuns::getPid());
    p_r($rec);
  case '20':
    $fs = Facesheet_Hl7PubHealthSurv::from(3027);
    p_r($fs);
    $msg = ADTMessage::from($fs);
    unset($msg->_fs);
    p_r($msg, 'msg');
    $hl7 = $msg->toHL7();
    p_r($hl7);
    exit;
  case '21':
    $fs = Facesheet_Hl7Adt::from(3684);
    p_r($fs);
    $msg = ADTMessage::asPapyrus($fs);
    unset($msg->_fs);
    p_r($msg, 'msg');
    $hl7 = $msg->toHL7();
    p_r($hl7);
    exit;
  case '30':
    $fs = Facesheet_Hl7Ccd::from(3486);
    p_r($fs);
    exit;
  case '40':
    $xml = new PqriSubmission();
    $xml->file_audit_data = new PqriFileAuditData();
    $xml->registry = new PqriRegistry();
    $xml->measure_group = new PqriMeasureGroup();
    $xml->measure_group->provider = new PqriProvider();
    $xml->measure_group->provider->pqri_measure = new PqriMeasure();
    echo htmlentities($xml->toXml(true, null, true));
    
    exit;
}
?>
</html>


