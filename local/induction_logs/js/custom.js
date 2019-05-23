var hideelement="";


    $(document).on("click", ".open-logs", function () {
     var induction_id = $(this).data('id');
     var key='findlogs';
         hideelement=$(this).data('id');
     $.blockUI({ message: $('#divMessage') });
     
     $.ajax({
          url: 'functions.php',
		  type: 'POST',
		  data: {induction_id: induction_id,key: key},
		  success: function(response){

		  	$('#logs').empty();
		  	$('#logs').append(response);
		  	$.unblockUI();
		  	$('#logstable').excelTableFilter(); 

          }
   
       });
     
     });