<?php

$response = array(

						array('qid' => 4, 'ans' => 'asdfadsf'),
						array('qid' => 4, 'ans' => 'asdfadsf'),
						array('qid' => 4, 'ans' => 'asdfadsf'),
						array('qid' => 4, 'ans' => 'asdfadsf'),
						array('qid' => 4, 'ans' => 'asdfadsf'),
						array('qid' => 4, 'ans' => 'asdfadsf'),
						array('qid' => 4, 'ans' => 'asdfadsf'),
						array('qid' => 4, 'ans' => 'asdfadsf')
					

);

echo  $s = json_encode($response);
$in=json_decode($s);

foreach ($in as $ins) {
	            $questionId=$ins->qid;
		    	$answer=$responses->ans;
		    	echo $questionId;
		    	echo $answer;
}


// [{"qid":4,"ans":"asdfadsf"},{"qid":4,"ans":"asdfadsf"},{"qid":4,"ans":"asdfadsf"},{"qid":4,"ans":"asdfadsf"},{"qid":4,"ans":"asdfadsf"},{"qid":4,"ans":"asdfadsf"},{"qid":4,"ans":"asdfadsf"},{"qid":4,"ans":"asdfadsf"}]