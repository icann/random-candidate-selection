<?php
/*
  Random Selection implementation based on OCTO-41.
  https://www.icann.org/en/system/files/files/octo-041-25feb25-en.pdf

  Copyright Internet Corporation For Assigned Names and Numbers

  All rights reserved.

  See LICENSE file for details.
  https://github.com/icann/random-candidate-selection/blob/main/LICENSE
*/

error_reporting(E_ALL);

$NUM_CANDIDATES = 10; // Number of possible candidates
$MIN_CHARS_CAND = 3; // Minimum length of name in UTF-8 characters
$MAX_CHARS_CAND = 30; // Maximum length of name in UTF-8 characters
$NUM_PV = 2; // Number of possible P values 
$MIN_CHARS_PV = 3; // Minimum length of P-value
$MAX_CHARS_PV = 30; // Maximum length of P-value

// Check passed candidate
// Returns true if good otherwise false
function check_candidate($txt){
  if(! mb_detect_encoding($txt, 'UTF-8') === 'UTF-8'){
    return false; // The string is not valid UTF-8
  }
  if(iconv_strlen($txt, 'UTF-8') < $GLOBALS['MIN_CHARS_CAND']){
    return false;
  }
  if(iconv_strlen($txt, 'UTF-8') > $GLOBALS['MAX_CHARS_CAND']){
    return false;
  }
  return true;
}

// Check passed P values
// Returns true if good otherwise false
function check_pv($pv){
  if(! preg_match('/^[0-9\. ]*$/', $pv)){
    return false; // The string contains invalid characters
  }
  if(strlen($pv) < $GLOBALS['MIN_CHARS_PV']){
    return false;
  }
  if(strlen($pv) > $GLOBALS['MAX_CHARS_PV']){
    return false;
  }
  return true;
}

// Clean up POST data and check input
// Returns 2D array of candidates, P values, and D value
function process_post($pd){
  $rv = array();
  $rv['can'] = array();
  $rv['pv'] = array();
  $rv['dv'] = "";

  for($ii = 1; $ii <= $GLOBALS['NUM_CANDIDATES']; $ii++){
    $name = trim($pd['can_' . $ii]);
    if(strlen($name) === 0){
      array_push($rv['can'], "");
    }elseif(check_candidate($name)){
      array_push($rv['can'], $name);
    }else{
      array_push($rv['can'], "INVALID CANDIDATE");
    }
  }

  for($ii = 1; $ii <= $GLOBALS['NUM_PV']; $ii++){
    $pv = trim($pd['pv_' . $ii]);
    if(strlen($pv) === 0){
      array_push($rv['pv'], "");
    }elseif(check_pv($pv)){
      array_push($rv['pv'], $pv);
      $rv['dv'] .= $pv . "/";
    }else{
      array_push($rv['pv'], "INVALID P-Value");
    }
  }

  $rv['dv'] = trim($rv['dv'], "/");
  return $rv;
}

// Generate results from passed data
// Returns assoc array ordered ascending by SHA-256 hash
function gen_results($data){
  $rv = array();
  for($ii = 0; $ii < $GLOBALS['NUM_CANDIDATES']; $ii++){
    if(strlen($data['can'][$ii]) == 0){
      continue;
    }
    
    $hex_name = bin2hex($data['can'][$ii]);
    $comp_name = $hex_name . "/" . bin2hex($data['dv']);
    $hash = hash('sha256', $comp_name);
    
    $rv[$hash]['hash'] = $hash;
    $rv[$hash]['name'] = $data['can'][$ii];
    $rv[$hash]['hex_name'] = $hex_name;
    $rv[$hash]['comp_name'] = $comp_name;
  }
  
  ksort($rv);
  return $rv;
}

/////////////////////
// BEGIN EXECUTION //
/////////////////////

if(isset($_POST['can_1'], $_POST['pv_1'])){
    $data = process_post($_POST);
}
?>

<!doctype html>
<html>
<head>
 <meta charset="utf-8"/>
<title>Random Candidate Selection</title>
</head>
<body>

<h3>
  This is a demo of Random Candidate Selection as described in <a href="https://www.icann.org/en/system/files/files/octo-041-25feb25-en.pdf">ICANN OCTO-41.</a><br/>
</h3>

<form method="post">
  <table>
  <tr><td><b>Candidates</b></td></tr>
<?php
  for($ii=1; $ii <= $NUM_CANDIDATES; $ii++){
    print("<tr><td>Candidate " . $ii . "</td><td><input type=\"text\"
      name=\"can_" . $ii . "\" id=\"can_" . $ii . "\" value=\"" . $data['can'][$ii-1] . "\"></td></tr>");
  }
?>
</table>

<br/>
<table>
  <tr><td><b><i>P</i> Values</b></td></tr>
<?php
  for($ii=1; $ii <= $NUM_PV; $ii++){
    print("<tr><td>P-" . $ii . "</td><td><input type=\"text\"
      name=\"pv_" . $ii . "\" id=\"pv_" . $ii . "\" value=\"" . $data['pv'][$ii-1] . "\"></td></tr>");
  }
?>
</table>

<br/>
<table>
<tr>
  <td><input type="submit" id="generate" value="Generate" name="generate"></td>
</tr>
</table>
</form>

<?php
   if(count($data['can']) > 0 || strlen($data['dv']) > 0){
     if(strlen($data['dv']) > 0){
       print("<br/>");
       print("\n<table>");
       print("\n<tr><td colspan=2><b><i>P</i> Values</b></td></tr>");
       print("\n<tr><td>P-1</td><td>" . $data['pv'][0] . "</td></tr>");
       print("\n<tr><td>P-2</td><td>" . $data['pv'][1] . "</td></tr>");
       print("\n</table>");
       
       print("\n<br/>");
       print("\n<table>");
       print("\n<tr><td><b><i>D</i> Value</b></td><td><b>Hex</b></td></tr>");
       print("\n<tr><td>" . $data['dv'] . "</td><td>" . bin2hex($data['dv']) . "</td></tr>");
       print("\n</table>");
     }

     if(count($data['can']) > 0){
       print("\n<br/>");
       print("\n<table>");
       print("\n<tr><td><b>Candidate</b></td><td><b>Hex</b></td></tr>");
       for($ii=1; $ii <= $GLOBALS['NUM_CANDIDATES']; $ii++){
         if(strlen($data['can'][$ii-1]) > 0){
           print("\n<tr><td>" . $data['can'][$ii-1] . "</td><td>" . bin2hex($data['can'][$ii-1]) . "</td></tr>");
         }
       }
       print("\n</table>");
     }

     if(count($data['can']) > 0 && strlen($data['dv']) > 0){
       $outcome = gen_results($data);

       print("\n<br/>");
       print("\n<table>");
       print("\n<tr><td><b>Hash (Winners on top)</b></td><td><b>Candidate Comparison Name</b></td><td><b>Candidate</b></td></tr>");
       while(count($outcome) > 0){
         $entry = array_shift($outcome);

         $hash = $entry['hash'];
         $comp_name = $entry['comp_name'];
         $candidate = $entry['name'];
         print("\n<tr><td>" . $hash . "</td><td>" . $comp_name . "</td><td>" . $candidate . "</td></tr>");
       }
       print("\n</table>");
     }
   }
?>

</body>
</html>
