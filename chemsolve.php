<?php
include("solve_amounts.phpi");

//STARTPORTION This portion should be replaced by reading from the form itself if used on a web page
function read_real_line($prompt = '') {
    echo $prompt;
    return rtrim(fgets(STDIN));
}

$target = read_real_line('Target Formula: ');
$source = read_real_line('Starting Materials (comma separated): ');
$dummy = read_real_line('DO NOT Balance (comma separated): ');
$amount = read_real_line('Quantity (mg): ');
$quantType = read_real_line('Is quantity of starting materials (y/n)?: ');
if ($quantType === null || substr(strtolower($quantType),0,1) == 'y' || strlen($quantType) == 0)
  $quantType = 1;
else
  $quantType = 2;
//ENDPORTION

/* Use the above for command line execution, the below for web server execution.
function sanitize_paranoid_string($string, $min=-1, $max=-1)
{
  if ($string === null) return null;
  $string = preg_replace("/[^a-zA-Z0-9.,()_]/", "", $string);
  $len = strlen($string);
  if((($min != -1) && ($len < $min)) || (($max != -1) && ($len > $max)))
    return null;
  return $string;
}
function sanitize_paranoid_tokens($string, $min=-1, $max=-1)
{
  if ($string === null) return null;
  $string = preg_replace("/[^a-zA-Z0-9.,=]/", "", $string);
  $len = strlen($string);
  if((($min != -1) && ($len < $min)) || (($max != -1) && ($len > $max)))
    return null;
  return $string;
}
if (array_key_exists('target', $_GET)) $target = sanitize_paranoid_string($_GET['target']);
else $target = '';
if (array_key_exists('source', $_GET)) $source = sanitize_paranoid_string($_GET['source']);
else $source = '';
if (array_key_exists('dummy', $_GET)) $dummy  = sanitize_paranoid_tokens($_GET['dummy']); //dummy === null is different than dummy = ''
if (array_key_exists('amount', $_GET)) $amount = sanitize_paranoid_string($_GET['amount']);
else $amount = '';
if (array_key_exists('quantType', $_GET)) $quantType = sanitize_paranoid_string($_GET['quantType']);
else $quantType = 1;
 */

$doSolve = true;
$solveOK = true;
if (strlen($target) < 1 || strlen($source) < 1 || strlen($amount) < 1 || !isset($dummy)) {
    $doSolve = false;
    $solveOK = false;
    if (strlen($target) < 1) 
        $errors[] = "Target Not Specified!";
    if (strlen($source) < 1)
        $errors[] = "Source Not Specified!";
    if (strlen($amount) < 1)
        $errors[] = "Amount Not Specified!";
    if (strlen($target) < 1 && strlen($source) < 1 && strlen($amount) < 1 && !isset($dummy))
        $errors = array();
}

if ($doSolve == true) {
    $result = solve_amounts($target, explode(",", $source), explode(",", $dummy), $amount);
    $warnings = $result[1];
    $errors = $result[2];
    $solveOK = $result[3];
}

//STARTPORTION
if ($solveOK == true) {
  echo $result[4] . "\n";
  echo $result[5] . "\n";
// Adjust for quantity of product, if needed (quantType == 1 means of starting material)
  if ($quantType == 2) { // quantity of product
    $productMass = end($result[0])[1];
    reset($result[0]);
    $mf = $amount/$productMass;
    for ($i = 0; $i < count($result[0]); $i++)
      $result[0][$i][1] = $result[0][$i][1] * $mf;
  }
  $padlen = 8;
  for ($i = 0; $i < count($result[0]); $i++)
    if (strlen(str_replace("<sub>","_",str_replace("</sub>","",$result[0][$i][0]))) > $padlen)
      $padlen = strlen(str_replace("<sub>","_",str_replace("</sub>","",$result[0][$i][0])));
  $padlen += 1;
  echo "\n" . str_pad("Material",$padlen," ") . "Quantity/mg\n";
  for ($i = 0; $i < count($result[0]); $i++) {
    echo str_pad(str_replace("<sub>","_",str_replace("</sub>","",$result[0][$i][0])),$padlen," ");
    echo sprintf("%.1f", $result[0][$i][1]) . "\n";
  }
}

if ((isset($warnings) && count($warnings) > 0) || (isset($errors) && count($errors) > 0)) {
  for ($i = 0; isset($warnings) && $i < count($warnings); $i++) {
    echo "Warning: " . $warnings[$i] . "\n";
  }
  for ($i = 0; isset($errors) && $i < count($errors); $i++) {
    echo "Error: " . $errors[$i] . "\n";
  }
  if (count($errors) > 0 && is_array($result)) {
    echo "Raw Return value: ";
    print_r($result[0]); echo "\n";
  }
}

echo "\nNotes: see https://occamy.chemistry.jhu.edu/chemsolve/index.php for notes.\n";
//ENDPORTION

/* This input form only needed in the web interface
// BEGIN CONTENT OUTPUT

echo "<div id=\"body\">\n";
echo "<div class=\"container\">\n";

// Compound form
echo "<div class=\"querycontainer\">\n";
echo "<h4>Enhanced ChemSolve V1.2 </h4>\n";
echo "Please talk with me or email feedback to <a href=\"mailto:mcqueen@jhu.edu\">mcqueen@jhu.edu</a><br><br>\n";
echo "<form action=\"index.php\" method=\"get\">\n";
echo "<table class=\"query\">\n";
echo "  <tr>\n";
echo "   <td><B>Target Formula:</B></td>\n";
echo "   <td><input type=\"text\" name=\"target\" value=\"" . $target . "\"></td>\n";
echo "   <td>The target formula you want to make</td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "   <td><B>Starting Materials:</B></td>\n";
echo "   <td><input type=\"text\" name=\"source\" value=\"" . $source . "\"></td>\n";
echo "   <td>The metals, oxides, carbonates, nitrates, etc. to start with.</td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "   <td><B>DO NOT Balance:</B></td>\n";
if (isset($dummy))
  echo "   <td><input type=\"text\" name=\"dummy\" value=\"" . $dummy . "\"></td>\n";
else
  echo "   <td><input type=\"text\" name=\"dummy\" value=\"CO3=O,NO3=O,O\"></td>\n";
echo "   <td>These elements/compounds are ignored for atom-balance purposes.  CO3=O means, e.g., that Na2CO3 is treated as Na2O and NO3=O means, e.g., that NaNO3 is treated as NaO. O means that oxygen can equilibrate with the atmosphere.</td>\n";
echo "  </tr>\n";
echo "  <tr>\n";
echo "   <td><B>Quantity / mg:</B></td>\n";
if (strlen($amount) > 0)
  echo "   <td><input type=\"text\" name=\"amount\" value=\"" . $amount . "\"></td>\n";
else
  echo "   <td><input type=\"text\" name=\"amount\" value=\"300\"></td>\n";
echo "   <td>The total quantity of <select name=\"quantType\"><option value=\"1\">starting materials</option><option value=\"2\"";
if ($quantType == 2) echo " selected";
echo ">product</option></select>, in milligrams.</td>\n";
echo "  </tr>\n";
echo "</table>\n";
echo "<pre><input type=\"submit\" value=\"calculate\"></pre>\n";
echo "</form>\n";
echo "</div>\n";


// Result, if appropriate
if ($solveOK == true) {
  echo "<div class=\"answercontainer\">\n";
  echo "  <div class=\"reaction\">\n";
  echo "<pre>" . $result[4] . "</pre>\n";
  echo "  </div>\n";
  echo "  <div class=\"reactionsubs\">\n";
  echo "<pre>" . $result[5] . " (Molecular weights in g/mol)</pre>\n";
  echo "  </div>\n";
  echo "  <table class=\"answer\">\n";
  echo "  <tr>\n   <td><B>Material</B></td>\n";
  echo "   <td><B>Quantity/mg</B></td>\n";
  echo "  </tr>\n";
// Adjust for quantity of product, if needed (quantType == 1 means of starting material)
  if ($quantType == 2) { // quantity of product
    $productMass = end($result[0])[1];
    reset($result[0]);
    $mf = $amount/$productMass;
    for ($i = 0; $i < count($result[0]); $i++)
      $result[0][$i][1] = $result[0][$i][1] * $mf;
  }
  for ($i = 0; $i < count($result[0]); $i++) {
    echo "  <tr>\n   <td>";
    echo $result[0][$i][0] . "</td>\n";
    echo "   <td>" . sprintf("%.1f", $result[0][$i][1]) . "</td>\n";
    echo "  </tr>\n";
  }
  echo "</table>\n";
  echo "</div>\n";
}

// Warnings & Errors, if any
if ((isset($warnings) && count($warnings) > 0) || (isset($errors) && count($errors) > 0)) {
  echo "<div class=\"warningcontainer\">\n";
  for ($i = 0; isset($warnings) && $i < count($warnings); $i++) {
    echo "  <div class=\"warning\">\n";
    echo "<B>Warning:</B> " . $warnings[$i] . "\n";
    echo "  </div>\n";
  }
  for ($i = 0; isset($errors) && $i < count($errors); $i++) {
    echo "  <div class=\"error\">\n";
    echo "<B>Error:</B> " . $errors[$i] . "\n";
    echo "  </div>\n";
  }
  if (count($errors) > 0 && is_array($result)) {
    echo "  <div class=\"error\">\n";
    echo "<B>Raw Return value:</B> ";
    print_r($result[0]); echo "\n";
    echo "  </div>\n";
  }
  echo "</div>\n";
}



echo "<br>\n";
echo "<p>\n";
echo "<B>Notes:</B><br><ul>\n";
echo "  <li>NaNO3 and Ba(NO3)2 are recognized as nitrates whereas ZrNbO3N\n";
echo "      is treated as an oxynitride. To force recognition of Ba(NO3)2 as an oxynitride you would write BaN2O6 instead.\n";
echo "      To force recognition of ZrNbO3N as a nitrate, you would write ZrNbNO3.</li>\n";
echo "  <li>Generally items on the do not balance list should be ordered from most to least specific.</li>\n";
echo "  <li>For do not balance items, <I>only</I> individual elements may be directly ignored. If you wish to have the\n";
echo "      program ignore more complicated units (e.g. water, such as if a starting material is a hydrate), and keep\n";
echo "      the rest of the \"normal\" conditions, then you should put a UNIT= on the line, indicating that UNIT should be replaced with nothing,\n";
echo "      e.g. \"CO3=O,NO3=O,O,H2O=\" for ignoring water units. \"CO3=O,NO3=O,O,H2O\" <I>would not</I> work.</li>\n";
echo "  <li>Isotopes can now be specified. For example, you can target Sm(B_11)5B_10 , with Sm, B_11, and B_10 as starting materials.</li>\n";
echo "</ul><br>\n";
echo "<p><B>Please notify me of any problems! Thank you!</B></p>\n";
echo "</div>\n";

echo "</div>\n";

#echo "</div>\n";

//************************ END CONTENT OUTPUT ****************************
*/

?>
