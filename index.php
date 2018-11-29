<?php
  // Version 1.0 
  // Released 8/6/2018
  // Mikey Ling

  // BOM difference engine

  function upload($header, $id, $get_name) {
    $all_parts = array();
    $all_parts_final = array();

  		$tmpName = $_FILES[$id]['tmp_name'];
  		$csvAsArray = array_map('str_getcsv', file($tmpName));
		array_shift($csvAsArray);

		//Get Comcode and Version 
		$comcode = $csvAsArray[0][2];
		$version = $csvAsArray[0][4];
		$comcode_and_version = $comcode.', Version: '.$version;
		$comcode_and_version = str_replace(',', ' -', $comcode_and_version);
		array_shift($csvAsArray);
		array_push($all_parts_final, $comcode_and_version);

  		foreach($csvAsArray as $entry){
  			// if the part is missing a comcode, then use its board_assembly number instead
			if ($entry[0] == "") {
				$entry[0] = $entry[1]; 
			}
			array_push($all_parts_final, array('refdes' => $entry[0], 
                                            'board_assembly' => $entry[1],
                                            'comcode' => $entry[2],
                                            'quantity' => $entry[3],
                                            'version' => $entry[4]));
  		}
    return $all_parts_final;
  }

  
  $content = "";
  if (isset($_POST['submit'])) {
    $bom1= upload("File 1", 'f1', False);
    $bom2 = upload("File 2", 'f2', False);
    $bom1_commcode = $bom1[0];
    array_shift($bom1);
    $bom2_commcode = $bom2[0];
    array_shift($bom2); 

    $bom1_refdes_dict = array();
    $bom2_refdes_dict = array(); 

    foreach($bom1 as $object){
      $bom1_refdes_dict[$object['refdes']] = array('comcode' => $object['comcode'], 'quantity' => $object['quantity']);
    }

    foreach($bom2 as $object){
      $bom2_refdes_dict[$object['refdes']] = array('comcode' => $object['comcode'], 'quantity' => $object['quantity']);
    }

    $bom1_only_refdes = array_diff(array_map(function($element){return $element['refdes'];}, $bom1),
    							   array_map(function($element){return $element['refdes'];}, $bom2));

    $bom2_only_refdes = array_diff(array_map(function($element){return $element['refdes'];}, $bom2),
    							   array_map(function($element){return $element['refdes'];}, $bom1));

    $shared_refdes_diff_commcode = array();
    $shared_refdes_same_commcode_diff_quantity = array();

    foreach($bom1 as $bom1_object){
      if(array_key_exists($bom1_object['refdes'], $bom2_refdes_dict)){
        $bom1_comcode = $bom1_refdes_dict[$bom1_object['refdes']]['comcode'];
        $bom2_comcode = $bom2_refdes_dict[$bom1_object['refdes']]['comcode'];
        $bom1_quantity = $bom1_refdes_dict[$bom1_object['refdes']]['quantity'];
        $bom2_quantity = $bom2_refdes_dict[$bom1_object['refdes']]['quantity'];        
        if(strcmp($bom1_comcode, $bom2_comcode) !== 0){
          array_push($shared_refdes_diff_commcode, $bom1_object['refdes']);
        }
        else {
          if(strcmp($bom1_quantity, $bom2_quantity) !== 0){
            array_push($shared_refdes_same_commcode_diff_quantity,  $bom1_object['refdes']);
          }
        }
      }
    }

    $content = "<table id = 'main_table'>";
    $content .= "<button id = 'export_button'> Export table to CSV file </button>";
    $content .= "
                  <tr>
                    <th colspan = '3' class = 'left_vertical_line'> BOM1 - ".$bom1_commcode."</th>
                    <th colspan = '3' class = 'right_vertical_line' style=': 10px'> BOM2 - ".$bom2_commcode."</th>
                  </tr>
                  <tr id='header_row'>
                    <td> Refdes </td>
                    <td> Comcode </td>
                    <td class='left_vertical_line'> Quantity </td> 
                    <td> Refdes </td>
                    <td> Comcode </td>
                    <td class='right_vertical_line'> Quantity </td>
                    <td><b> Status </b></td>  
                  </tr>";
      foreach($bom1_only_refdes as $refdes){
        $content .= "<tr id='bom1_only'>
                      <td>".$refdes."</td>
                      <td>".$bom1_refdes_dict[$refdes]['comcode']."</td>
                      <td class='left_vertical_line'>".$bom1_refdes_dict[$refdes]['quantity']."</td>
                      <td></td>
                      <td></td>
                      <td  class='right_vertical_line'></td>
                      <td>". 'In BOM1 Only' ."</td>
                    </tr>";

      }
      foreach($bom2_only_refdes as $refdes){
        $content .= "<tr id='bom2_only'>
                      <td></td>
                      <td></td>
                      <td class='left_vertical_line'></td>
                      <td>".$refdes."</td>
                      <td>".$bom2_refdes_dict[$refdes]['comcode']."</td>
                      <td  class='right_vertical_line'>".$bom2_refdes_dict[$refdes]['quantity']."</td>
                      <td>". 'In BOM2 Only' ."</td>
                    </tr>";
      }
      foreach($shared_refdes_diff_commcode as $refdes){
        $content .= "<tr id='diff_comcode'>
                      <td>".$refdes."</td>
                      <td id = 'different_comcode'>".$bom1_refdes_dict[$refdes]['comcode']."</td>
                      <td class='left_vertical_line'>".$bom1_refdes_dict[$refdes]['quantity']."</td>
                      <td>".$refdes."</td>
                      <td id = 'different_comcode'>".$bom2_refdes_dict[$refdes]['comcode']."</td>
                      <td  class='right_vertical_line'>".$bom2_refdes_dict[$refdes]['quantity']."</td>
                      <td>". 'Different Comcode'."</td>
                    </tr>";
      }
      foreach($shared_refdes_same_commcode_diff_quantity as $refdes){
        $content .= "<tr id='diff_quantity'>
                      <td>".$refdes."</td>
                      <td>".$bom1_refdes_dict[$refdes]['comcode']."</td>
                      <td id = 'different_quantity' class='left_vertical_line'>" .$bom1_refdes_dict[$refdes]['quantity']."</td>
                      <td>".$refdes."</td>
                      <td>".$bom2_refdes_dict[$refdes]['comcode']."</td>
                      <td id = 'different_quantity'  class='right_vertical_line'>".$bom2_refdes_dict[$refdes]['quantity']."</td>
                      <td>". 'Different Quantity'."</td>
                    </tr>";    
      }
      $content .= "</table>";  
  }
?>

<html>
  <head>
	<title>BOM Compare</title>
	<link rel='stylesheet' type='text/css' href='stylesheet.css' />
  </head>

  <body>
    <h1 class = 'bigred'>Fast BOM Compares<p id='author'> By Mikey Ling <p> </h1>
    <h2 class = 'instructions_header'> Instructions for Saving BOMs in Windchill: </h2>

    <ul class = 'instructions_list'>
    	<li> Navigate to the desired BOM</li>
    	<li> In the 'Structure' tab, change the structure view to 'BOM COMPARE'</li>
    	<li> Save the BOM by choosing 'Export List to CSV'</li>
    </ul>

    <div>
    	<form method='post' action='index.php' enctype='multipart/form-data'>
		    BOM 1: <input type = file name='f1' class = 'input_file'><br><br>
		    BOM 2: <input type = file name='f2' class = 'input_file'><br><br>
		    <input type=submit name='submit'>
    	</form>
    </div>

    <div> 
    	<?php print($content); ?>
    </div>
  </body>

  <script type="text/javascript" src="javascript.js"></script>

</html>
