<?php
require('config.php');
require_once ('htmltable/html_table.php');

$userdetails = $DB->get_records_sql('SELECT * FROM {user} WHERE id != ? AND id != ?',array(1,2));
foreach($userdetails as $userdetail){
	$userenrolledcourses = $DB->get_records_sql('SELECT rs.contextid,us.firstname,us.lastname,us.username,rs.userid,ca.id,ca.fullname from {role_assignments} as rs INNER JOIN {user} as us ON us.id = rs.userid INNER JOIN {context} as cx ON cx.id = rs.contextid INNER JOIN {course} as ca ON ca.id = cx.instanceid where roleid = ? AND userid = ?',array('5',$userdetail->id));

	foreach($userenrolledcourses as $userenrolledcourse){
		$coursemodules = $DB->get_records_sql('SELECT id,instance from {course_modules} where course = ? AND module = ? ORDER BY id DESC LIMIT 0 , 1',array($userenrolledcourse->id,20));
		foreach($coursemodules as $coursemodule){
		$sql = 'SELECT id,timemodified from {course_modules_completion} where coursemoduleid ='.$coursemodule->id.' AND userid = '.$userenrolledcourse->userid.' AND completionstate !=0 AND flag IS null';
	
		$usercoursemodulescompletions = $DB->get_records_sql($sql);
		if(!empty($usercoursemodulescompletions)){
		foreach($usercoursemodulescompletions as $usercoursemodulescompletion){
			
			
            /*$html = '<div class="container">
    <div class="main">
        <div class="content" style="text-align:center;padding-bottom: 21%;padding-top: 20%;">
           <span style="font-size:35px;font-family:SayaSemiSansFY;color:#00584d;">CERTIFICATE <span style="color:#836140;"><i>of</i></span> ACHIEVEMENT</span>
           <br><br>
           <span style="font-size:25px;color:#836140;font-family: SayaSemiSansFY;">This is to certify that</span>
           <br><br>
           <span style="font-size:30px;margin-left:10%;font-family: SayaSemiSansFY;"><b>'.$userenrolledcourse->firstname ."_".$userenrolledcourse->lastname.'</b></span><br/><br/>
           <span style="font-size:25px;color:#836140;font-family: SayaSemiSansFY;;">Has successfully completed the following course ON Date: '.date('d-m-y',$usercoursemodulescompletion->timemodified).'</span> <br/><br/>
           <span style="font-size:30px;font-family: SayaSemiSansFY;">'.$userenrolledcourse->fullname.'</span> <br/><br/>
           <div style=" float: left; padding: 5%; text-align: left;">
           <span style="font-size:20px;color:#00584d;"><i>Signature :_____________ </i><b></b></span><br/><br/>
          
           <span style="font-size:20px;color:#00584d;">Mr&nbsp; Ibrahim&nbsp; Siddiq</span><br><br>
           <span style="font-size:20px;color:#00584d;">Accountable&nbsp; Training&nbsp; Manager</span><br>
        </div>
        </div>
    </div>
    </div>
</html>';*/


//$img = $_SERVER['DOCUMENT_ROOT'].'/nucleusremui/icon.png';
$pdf=new PDF();

$pdf->AddPage('L');
$pdf->SetFont('Arial','B',18);
$pdf->SetTextColor(12,89,76);
$pdf->Image('image/Certificate-13_02.png', 5, 5, 287, 200, 'PNG', '', '', true, 150, '', false, false, 1, false, false, false);



$pdf->SetFont('Arial','B',18);
$pdf->SetTextColor(17,73,130);
$pdf->SetXY(168, 96);	
$pdf->Cell(0, 0, $userenrolledcourse->firstname ." ".$userenrolledcourse->lastname, 0, 0, 'L');

$pdf->SetFont('Arial','B',18);
$pdf->SetTextColor(17,73,130);
$pdf->SetXY(169, 124);	
$pdf->Cell(0, 0, $userenrolledcourse->fullname, 0, 0, 'L');

$pdf->SetFont('Arial','B',18);
$pdf->SetTextColor(17,73,130);
$pdf->SetXY(167, 142);
$pdf->Cell(0, 0, date('d F, Y',$usercoursemodulescompletion->timemodified), 0, 0, 'L');


$dir=$_SERVER['DOCUMENT_ROOT'].'/nucleus/pix/pdfimages/';
$filename= $userenrolledcourse->username."_".$usercoursemodulescompletion->id.".pdf";
$contents = $pdf->Output($dir.$filename,"F");
echo "Save PDF Certificate in folder For Userid $userenrolledcourse->userid i.e belongs to course $userenrolledcourse->fullname";
echo '<br>';

$updatecoursemoduleflag=$DB->execute("update {course_modules_completion} set flag = 1 where coursemoduleid ='".$coursemodule->id."' AND userid = '".$userdetail->id."' AND completionstate != 0");

		
		$records = new stdClass();
		
		$records->userid     	=  $userenrolledcourse->userid;
		
		$records->username     	=  $userenrolledcourse->username;

		$records->firstname     =  $userenrolledcourse->firstname;

		$records->lastname    	=  $userenrolledcourse->lastname;

		$records->filename     	=  $userenrolledcourse->username."_".$usercoursemodulescompletion->id.".pdf";
		
		$records->course    	=  $userenrolledcourse->fullname;


		$insertitems = $DB->insert_record('usercompletion_pdf', $records);
           
			}
		}else{
			echo "No new PDF Certificate For Userid $userenrolledcourse->userid i.e belongs to course $userenrolledcourse->fullname";
echo '<br>';
			
			}
		
		}
		
		
		}
		//die;
	}
?>