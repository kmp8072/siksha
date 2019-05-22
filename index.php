<?php

require_once('config.php');
require_once($CFG->libdir . '/badgeslib.php');
require_once($CFG->libdir . '/filelib.php');
// require_once($CFG->libdir . '/coursecatlib.php');
require_login();


// $PAGE->set_title($SITE->fullname);
// $PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();
global $USER, $DB,$CFG;
$uid =$USER->id;
?>
<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<style>
@import url('https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
section.content-header {
    display: none;
}

div.panel,main.tabs{
	-webkit-box-shadow: 8px 8px 49px 0px rgba(206,207,217,1);
	-moz-box-shadow: 8px 8px 49px 0px rgba(206,207,217,1);
	box-shadow: 8px 8px 49px 0px rgba(206,207,217,1);
}

main {
  /*min-width: 320px;
  max-width: 1100px;*/
  padding: 20px;
  margin: 0 auto;
  background: #fff;
}

section.tabs {
  display: none;
  padding: 20px 0 0;
  border-top: 1px solid #ddd;
}

input {
  display: none;
}

label {
  display: inline-block;
  margin: 0 0 -1px;
  padding: 15px 25px;
  font-weight: 600;
  text-align: center;
  color: #bbb;
  border: 1px solid transparent;
}

label:before {
  font-family: fontawesome;
  font-weight: normal;
  margin-right: 10px;
}

label[for*='1']:before { content: '\f2bc'; }
label[for*='2']:before { content: '\f0ca'; }
label[for*='3']:before { content: '\f02f'; }

label:hover {
  color: #888;
  cursor: pointer;
}

input:checked + label {
  color: #555;
  border: 1px solid #ddd;
  border-top: 2px solid #17407C;
  border-bottom: 1px solid #fff;
}

#tab1:checked ~ #content1,
#tab2:checked ~ #content2,
#tab3:checked ~ #content3{
  display: block;
}
button.btn.btn-primary {
    color: white;
    background-color: #17407C !important;
    margin-left: 0px;
    margin-top: 10px;
}
button.course{
	background-color: white;
	border-radius: 20px;
	color: #17407C;
	border: 1px solid #17407C;
}

.courseimage>img{
	    width:65%;
}


@media screen and (min-width: 769px) {
 .calendar{
	border-left: 1px solid #ccc;

	}
	.calendar>div.subcal{
		margin-left: 40px;
	}
}
@media screen and (max-width: 768px) {
    
	.subcal{
		margin-left: 12px;
	    margin-top: 20px;
	    border-top: 1px solid #ccc;
	}
}

table{
	width: 100%;
}
td{
	width:33.33%; 
}

</style>
<body>

   <section class="main"> 
	    <section class="tab-content">
	        <section class="tab-pane active fade in content" id="dashboard">    
	            <div class="row">

                    <div class="col-xs-6 col-sm-4">
	                    <div class="panel">
	                        <div class="panel-body">
	                        	<div class="col-sm-4">
	                        		<i class="fa fa-graduation-cap fa-4x" aria-hidden="true" style="padding : 10px 10px;  color: #17407ccf; "></i>
	                        	</div>
	                        	<div class="col-sm-8">
	                        	<?php
	                        		$course = $DB->get_records_sql("SELECT * FROM {user_enrolments} where userid = ".$USER->id."");
	                        		$count = count($course);
	                        	?>
		                        	<span style="color:#17407ccf;font-size: 29px;"><?php echo $count; ?></span>
		                        	<div>COURSES TO DO</div>
		                        </div>

	                        </div>
	                    </div>
	                </div>
	                 
                    <div class="col-xs-6 col-sm-4">
                        <div class="panel">
                            <div class="panel-body">
                            	<div class="col-sm-4">
                            		<i class="fa fa-tachometer fa-4x" aria-hidden="true" style="padding : 10px 10px;  color: #17407ccf;"></i>
                            	</div>
                            	<div class="col-sm-8">
                            	<?php
	                        		$course = $DB->get_records_sql("SELECT * FROM {enrol} e JOIN {user_enrolments} ue on e.id = ue.enrolid where ue.userid = ".$USER->id."");
	                        		//print_r($course);
	                        		$i = 0;
	                        		$currentdate = time();
	                        		foreach ($course as $key => $value) {
	                        			$complition = $DB->get_record_sql("SELECT * FROM {course_completions} WHERE course = ".$value->courseid." AND status = 50 AND userid = ".$USER->id." ");
	                        			if($complition){

	                        			}else{
		                        			$enddate = $DB->get_record_sql("SELECT * FROM {course} WHERE id = ".$value->courseid." AND enddate < '".$currentdate."' ");
		                        			if($enddate->enddate  == 0){

		                        			}else{
		                        				$i++;
		                        			}
		                        		}
	                        		}
	                        	?>
                            		<span style="color:#17407ccf;font-size: 29px;"><?php echo $i; ?></span>
	                        		<div>OVERDUE COURSES</div>
	                        	</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xs-6 col-sm-4">
                        <div class="panel">
                            <div class="panel-body">
                            	<div class="col-sm-4">
                            		<i class="fa fa-certificate fa-4x" style="padding : 10px 10px;  color: #17407ccf;"></i>
                            	</div>
                            	<div class="col-sm-8">
                            		<?php
	                        		$course = $DB->get_records_sql("SELECT * FROM {enrol} e JOIN {user_enrolments} ue on e.id = ue.enrolid where ue.userid = ".$USER->id."");
	                        		// print_r($course);
	                        		$i = 0;
	                        		foreach ($course as $key => $value) {

	                        			$complition = $DB->get_record_sql("SELECT * FROM {course_completions} WHERE course = ".$value->courseid." AND status = 50 AND userid = ".$USER->id." ");
	                        			if($complition){
	                        				$i++;
	                        			}
	                        			
	                        		}
	                        	?>
	                            	<span style="color:#17407ccf;font-size: 29px;"><?php echo $i; ?></span>
		                        	<div >COMPLETED COURSES</div>
	                        	</div>
                            </div>
                        </div>
                    </div>

	            </div>
	            
	            <div class="row"> 

                    <div class="col-xs-12 col-sm-12">
                        <div class="panel">
                        	<div class="panel-body">
                        		<div class="col-sm-6 col-xs-12">
	                              	<div class="col-sm-6">
	                                	<span style="color:#17407ccf;font-size: 29px;">BADGES</span>
	                                	<div>Your Latest Achievements</div>
	                                	<a href="<?php echo $CFG->wwwroot.'/user/profile.php?id='.$USER->id.'#badges'; ?>"><button name="view" class="btn btn-primary">VIEW</button></a>
	                                </div>
	                                <div class="col-sm-6">
	                                <?php
	                                $query =$DB->get_records_sql("SELECT * FROM {badge_issued} as bi JOIN {badge} as b ON b.id=bi.badgeid WHERE bi.userid=?",array($USER->id));

	                                
	                               foreach ($query as $key => $query) {
	                                	$pathhash = badges_bake($query->uniquehash, $query->id, $USER->id, true);
	                                	// print_r($pathhash);
	                                	$filepath= $DB->get_record_sql("SELECT * FROM {files} where pathnamehash=?",array($pathhash));
										$imageurl = $CFG->wwwroot.'/pluginfile.php/1/'.$filepath->component.'/badgeimage/'.$filepath->itemid.'/f1';
	                                	
	                                	$badgepath = $CFG->wwwroot.'/badges/badge.php?hash='.$query->uniquehash;
	                                	?>
	                                	<span >
										  <a href="<?php echo $badgepath; ?>"><img src="<?php echo $imageurl ;?>" style="width: 15%;"></a>
										</span>
										<?php 

	                                }
	                                 ?>
	                                	
										
	                                </div>
	                            </div>
	                        
		                        <div class="col-sm-6 col-xs-12 calendar" >
		                        	<div class="subcal">
		                               	<span style="color:#17407ccf;font-size: 29px;">CALENDAR</span>
	                                	<div>Your Calendar</div>
	                                		<a href="<?php echo $CFG->wwwroot;?>/calendar/view.php?view=month"><button name="view" class="btn btn-primary">VIEW</button></a>
	                                	
	                                </div>
	                            
		                        </div>
	                        </div>
                        </div>
                    </div>

                <!--     <div class="col-xs-6 col-sm-6">
                        <div class="panel">
                            <div class="panel-body">
                                <br/><br/><br/><br/>
                            </div>
                        </div>
                    </div> -->

                </div>
                <div class="row">
	                <main class="tabs">
  
						<input id="tab1" type="radio" name="tabs" checked>
						<label for="tab1">COURSES <br>Your Recent Courses</label>
						    
						<input id="tab2" type="radio" name="tabs">
						<label for="tab2">LEARNING PLAN<br>Your Learning Plan</label>
						    
						<input id="tab3" type="radio" name="tabs">
						<label for="tab3">LEADER BOARD<br>Your LeaderBoard</label>
    
  
						<section id="content1" class="tabs">
							<?php
							$course1 = $DB->get_records_sql("SELECT * FROM {enrol} e JOIN {user_enrolments} ue on e.id = ue.enrolid where ue.userid = ".$USER->id."");
							foreach ($course1 as $key => $co) {
								$courseid = $DB->get_records_sql("SELECT id FROM {course} WHERE id = ".$co->courseid." ");
							
								$course = get_courses("all");
								
								foreach($course as $key=>$ci){
									foreach ($courseid as $key => $value) {
									 	// $coursecontext = context_course::instance($ci->id);
									 	// print_r($coursecontext);
									 	if($ci->id == $value->id)
									    {
									    	$title = $ci->fullname;
									    	$coid = $ci->id;
									    	$completionstatus = $DB->get_record_sql("SELECT * FROM {course_completions} WHERE course = ".$coid." AND userid = ".$USER->id."");
									    	if($completionstatus->status == 50){
									    		$cstatus = "Completed";
									    	}else if($completionstatus->status == 25){
									    		$cstatus = "InProgress";
									    	}else if($completionstatus->status == 10){
									    		$cstatus = "Not Yet Started";
									    	}else{
									    		$cstatus = "Not Yet Started";
									    	}
										 	if ($ci instanceof stdClass) {
									            require_once($CFG->libdir. '/coursecatlib.php');
									            $course = new course_in_list($ci);
									            $outputimage = '';
												foreach ($course->get_course_overviewfiles() as $file) {
												    if ($file->is_valid_image()) {
												        $imagepath = '/' . $file->get_contextid() .
												                '/' . $file->get_component() .
												                '/' . $file->get_filearea() .
												                $file->get_filepath() .
												                $file->get_filename();
												        $imageurl = file_encode_url($CFG->wwwroot . '/pluginfile.php', $imagepath,
												                false);
												        $outputimage = html_writer::tag('div',
												                html_writer::empty_tag('img', array('src' => $imageurl)),
												                array('class' => 'courseimage'));
												        // Use the first image found.
												        break;
												    }
												}
												//echo $title."<br>";
												//echo $outputimage."<br>";
												if($outputimage == ""){
													$imageurl = $CFG->wwwroot."/defaultcourseimage.jpg";
													$outputimage = html_writer::tag('div',
												                html_writer::empty_tag('img', array('src' => $imageurl)),
												                array('class' => 'courseimage'));
													
												}
									      
								    
							?>
						  	<div class="row">
						  		<div class="col-sm-2 col-xs-6" style="float: left; overflow: hidden;"><?php echo $outputimage; ?>
						  		</div>
  								<div class="col-sm-4 col-xs-6"><h4><?php echo $title; ?></h4>
  								</div>
  								<div class="col-sm-4 col-xs-6" style="background: #f7f7f7; padding: 10px; color:#17407C">Course Status: <?php echo $cstatus; ?>
  								</div> 
  								<div class="col-sm-2 col-xs-6" style="float: right;" ><a href="<?php echo $CFG->wwwroot.'/course/view.php?id='.$coid; ?>"><button type="button" class="course">Continue</button></a>
  								</div>
						  	</div>
						  	<hr>
						  	<?php
						  	  				}
									    }
						  			}
								}          
							}
						  	?>
						</section>
    
					  	<section id="content2" class="tabs">
						    <div class="row">
						    	<table >
						    		<tr>
						    			<th>Active Plans</th>
						    			<th>Due date</th>
						    			<th>Status</th>
						    	
						    			
						    		</tr>
						    		<?php
						    			$sql = $DB->get_records_sql('SELECT dp.name,dp.enddate,dp.status FROM {dp_plan} dp JOIN {dp_template} dt ON dt.id=dp.templateid where dp.userid ='.$uid.' ' );
						    			foreach ($sql as $key => $val) {

						    				?>
						    				<tr>
						    					<td><?php echo $val->name ;?></td>
						    					<td><?php echo date('d-M-Y',$val->enddate) ;?></td>
						    					<td>
						    						<div class="progress" style="    width: 30%;">
    														<div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="<?php echo $val->status ;?>" aria-valuemin="0" aria-valuemax="100" style="width:<?php echo $val->status.'%' ;?>">
      														<?php echo $val->status .'% Complete' ;?>
   														 	</div>
													  	</div>
												</td>
						    					
						    				 </tr>	
						    				<?php
						    			}
						    		?>
						    	</table>
						    	
						    </div>
					  	</section>
    
					   	<section id="content3" class="tabs">
						    <div class="row">
   								<div class="col-sm-12">
       								<div id="content">
					   				 	<ul id="tabs" class="nav nav-tabs" data-tabs="tabs">
					        				<li class="active"><a href="#red" data-toggle="tab">Course</a></li>
					       					<li><a href="#orange" data-toggle="tab">Program</a></li>
					  				 	</ul>
									    <div id="my-tab-content" class="tab-content">
									        <div class="tab-pane active" id="red">
									         <div class="form-group">
									           
												<select id="id_courses" >
											
												  <option value="0">Please Select a Course</option>
												  <?php
										  			$courses = $DB->get_records_sql("SELECT * FROM {course}");
													foreach ($courses as $key => $value) {
														$id = $value->id;
														$cname = $value->fullname;
														
														?>
															<option value="<?php echo $id; ?>"><?php echo $cname; ?></option>
														<?php
													}
													?>			

												</select>
											  </div>
											<button id="id_submit">Submit</button>
												<div class="show">
													
												</div>
								        </div>
								        <div class="tab-pane" id="orange">
								            	<div class="form-group">
									           
												<select id="id_program" >
											
												  <option value="0">Please Select a Program</option>
												  <?php

											  		if(is_siteadmin()){
  													 $program = $DB->get_records_sql("SELECT * FROM {prog}");
  													   	foreach ($program as $key => $value) {
												  			$id = $value->id;
												  			$pname = $value->fullname;
												  			?>
															<option value="<?php echo $id; ?>"><?php echo $pname; ?></option>
															<?php
												  			
												  		}

											  			  }else{
											  			  	
											  			  }

													?>			

												</select>
											  </div>

													<button id="id_submit1">Submit</button>
													<div class="show1">
													
													</div>
								        </div>

									 </div>
									</div>

     							</div>
    						</div>
					  	</section>
					</main>
	                   
	            </div>
	        </section>
	    </section>
	</section>
</body>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script type="text/javascript">

$('#id_submit').on('click',function(){
	var val =$('#id_courses').val();
	$.ajax({
		type:'POST',
		url:'show.php?type=course',
		data:{courseid:val},
		success:function(data){
			if(data == "No Data Found")
			{
				alert("No Data Found")
			}else{
				$('.show').html(data);
			}
		}

	})
})

$('#id_submit1').on('click',function(){
	var val1 = $('#id_program').val();
	$.ajax({
		type:'POST',
		url:'show.php?type=program',
		data:{programid:val1},
		success:function(data){
			if(data == "No Data Found")
			{
				alert("No Data Found")
			}else{
				$('.show1').html(data);
			}
		}

	})
})


</script>
</html>
<?php
echo $OUTPUT->footer();