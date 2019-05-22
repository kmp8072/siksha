<?php

// This file is part of Moodle - http://moodle.org/

//

// Moodle is free software: you can redistribute it and/or modify

// it under the terms of the GNU General Public License as published by

// the Free Software Foundation, either version 3 of the License, or

// (at your option) any later version.

//

// Moodle is distributed in the hope that it will be useful,

// but WITHOUT ANY WARRANTY; without even the implied warranty of

// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the

// GNU General Public License for more details.

//

// You should have received a copy of the GNU General Public License

// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.



/**

 * Register Execution Smart Klass Server Connection

 *

 * @package    local_virtual_class

 * @copyright  KlassData <kttp://www.klassdata.com>

 * @author     Oscar Ruesga <oscar@klassdata.com>

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

 */

//error_reporting(0);

require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');

require_once(dirname(dirname(dirname(__FILE__))) . '/lib/formslib.php');

require_once(dirname(dirname(dirname(__FILE__))) . '/lib/adminlib.php');

require_once(dirname(dirname(dirname(__FILE__))) . '/lib/outputcomponents.php');

$strheading = "Create LeaderBoard";//heading of page
$PAGE->set_pagelayout('standard');
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/create_leaderboard/dashboard.php'));
$PAGE->set_title($strheading);
$PAGE->navbar->add($strheading);
//$user = $USER->id;
?>
<html>
<head>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/tagmanager/3.0.2/tagmanager.min.css">
<style type="text/css">
span.indicate-right {
    display: none;
}

.table-editable {
  position: relative;
  
  .glyphicon {
    font-size: 20px;
  }
}

.table-remove {
  color: #700;
  cursor: pointer;
  
  &:hover {
    color: #f00;
  }
}

.table-up, .table-down {
  color: #007;
  cursor: pointer;
  
  &:hover {
    color: #00f;
  }
}

.table-add {
  color: #070;
  cursor: pointer;
  position: absolute !important;
  top: 8px !important;
  right: 0;
  
  &:hover {
    color: #0b0;
  }
}
</style>

</head>

<?php
require_login();
echo $OUTPUT->header();
global $USER, $DB;
$pid = $_GET['pid'];

?>
<h2>Create LeaderBoard</h2>
<div class="container" style="text-align: center;">
   <div id="table" class="table-editable">
   	<input type="hidden" name="programid" id="programid" value="<?php echo $pid ;?>">
   <!--  <span class="table-add glyphicon glyphicon-plus"></span> -->
    <table class="table">
      <tr>
        <th>Name</th>
        <th>Score</th>
        <th></th>
      </tr>
      <?php
      $rcount = 0;
      $records = $DB->get_records_sql("SELECT * FROM {leaderboard} WHERE programid = ?",array($pid));
      $rcount = count($records);
      //echo $rcount;
      foreach ($records as $key => $value) {
      	?>
      	<tr>
        <td><input type="hidden" name="id[]" value="<?php echo $value->id;?>"><input type = "text" name = "user[]" class = "user" value="<?php echo $value->fullname; ?>"></td>
        <td><input type = "number" name = "score[]"  value="<?php echo $value->score; ?>"></td>
        <td>
          <button value ="<?php echo $value->id;?>" style="background-color: transparent;" class="remove"><span class="table-remove glyphicon glyphicon-remove"  ></span></button>
        </td>
        
      </tr>
      	<?php
      }
      for($i=0; $i<10-$rcount; $i++){
      	?>
      	<tr>
        <td><input type="hidden" name="id[]" value="0"><input type = "text" name = "user[]" class = "user" ></td>
        <td><input type = "number" name = "score[]"></td>

        
      </tr>
      	<?php
      }
?>
    
    </table>
  </div>
  
  <button id="submit" class="btn btn-primary" id="submit">Save</button>
  <p id="export"></p>
</div>
<?php
echo $OUTPUT->footer();
?>

<script src="https://code.jquery.com/jquery-1.9.1.js"></script>
<script src="https://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
<script type="text/javascript">

// AJAX call for autocomplete 
 $(function() {

    $("input:text[name='user[]']").autocomplete({
        source: function(request,response) {
        	
        	var pid = "";
			pid= document.getElementById('programid').value;
            $.ajax({
            	type : "POST",
                url: "users.php",
                dataType: 'json',
                data: {
                    q: request.term,
                    pid1:pid
                },
                success: function(data) {
                  response( $.map( data, function( item ) {
						return {
							label: item,
							value: item
						}
						
					}));
                }
            });
        },
     //    autoFocus: true,
    	// minLength: 0 
    	 change: function (event, ui) {                
            var referenceValue = $(this).val();
            var matches = false;

            $(".ui-autocomplete li").each(function () {
                if ($(this).text() == referenceValue) {
                    matches = true;
                    return false;
                }
            });

            if (!matches) {
                alert('Please select an item from the list');
                this.value = "";
                this.focus();
                this.style.border = "solid 1px red";
            }
            else {
                document.getElementById("submit").disabled = false;
                this.style.border = "solid 1px black";
            }
        }
    });

    $("#submit").on('click',function(){
    	var user = [];
    	var score = [];
    	var id = [];
    	var pid = "";
		pid= document.getElementById('programid').value;
		$("input[name='id[]']").each(function() {
			var text = $(this).val();
			if(text != ""){
		    	id.push($(this).val());
			}
		});
		$("input[name='user[]']").each(function() {
			var text = $(this).val();
			if(text != ""){
		    	user.push($(this).val());
			}
		});
		$("input[name='score[]']").each(function() {
			var text = $(this).val();
			if(text != ""){
		    	score.push($(this).val());
			}
		});
		$.ajax({
			url:"submit.php",
			type:"POST",
			data:{users : user, score:score , pid:pid, id:id},
			success:function(data){
				// alert(data);
				location.reload();
			}
		});
    })
    $(".remove").on('click',function(){
    	var deleteid = this.value;
    	var v = confirm("Are You Sure You want to Delete this Record");
    	if(v){
   			$.ajax({
			url:"submit.php",
			type:"POST",
			data:{did : deleteid},
			success:function(data){
				// alert(data);
				location.reload();
			}
		});
    	}else{
    		return false;
    	}
 
    })
});


</script>