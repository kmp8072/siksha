$('#no_of_entries').on('change', function() {

     	var perpage = this.value;
     	var value=1;

     	if (value=='' || value==null || value==0) {

     		value=1;
     	}

     	

     	 $.blockUI({ message: $('#divMessage') });
    	 $("#wait").css("display", "block");

 
    	$.ajax({
          url: 'submitteddata.php',
          //async: false,
		  type: 'POST',
		  data: {page: value,perpage: perpage},
		  success: function(response){
		  $( "#ajax_data" ).empty();	
          $( "#ajax_data" ).append("<div class='no-overflow table-wrap-remui'><span class='indicate-right'><i class='fa fa-arrow-right fa-lg' style='padding: 10px 1px;' aria-hidden='true'></i></span></div>");
          $( ".no-overflow" ).append(response);
          $.unblockUI();
           $("#wait").css("display", "none");
          $('#usertable').excelTableFilter();  


        }
   
       });
   
      });

    function paginate(button){

    	var value=button.value;
    	var perpage=$('#no_of_entries').val();
    	//localStorage.setItem("button_value", value);


    	
    	//console.log(perpage);

    	 $.blockUI({ message: $('#divMessage') });
    	 $("#wait").css("display", "block");

 
    	$.ajax({
          url: 'submitteddata.php',
          //async: false,
		  type: 'POST',
		  data: {page: value,perpage: perpage},
		  success: function(response){

          $( "#ajax_data" ).empty();
          $( "#ajax_data" ).append("<div class='no-overflow table-wrap-remui'><span class='indicate-right'><i class='fa fa-arrow-right fa-lg' style='padding: 10px 1px;' aria-hidden='true'></i></span></div>");
          $( ".no-overflow" ).append(response);
          $.unblockUI();
          $('#pageno').val(value);
           $("#wait").css("display", "none");
          $('#usertable').excelTableFilter();  


        }
   
       });

    }

    function sendwelcomemessage(userid,button){

    	var key='sendwelcomemessage';

    	$.blockUI({ message: $('#divwelcomeMessage') });

    	$.ajax({
          url: 'functions.php',
		  type: 'POST',
		  data: {userid: userid,key: key},
		  success: function(response){

		  	$.unblockUI();
		  	
         	if (response==1) {

         		var data=$('<div class="success message"><h5>Message sent Successfully!</h5><p>This is just an info notification message.</p> </div>');    
var popup= $('<div>');
popup.append(data);
$('#usertable').append(popup);
popup.css("position","absolute");
popup.css("background","#fff");
popup.css("padding","20px");
popup.css("border","1px solid #333");
popup.css("border-radius","5px");
popup.css("top", "50%");
popup.css("left", "50%");            
popup.fadeOut(3000);

button.style.display = 'none';
         	}

         	if (response==0) {

         		var data=$('<div class="success message"><h5>Message sending Failed</h5><p>This is just an info notification message.</p> </div>');    
var popup= $('<div>');
popup.append(data);
$('#usertable').append(popup);
popup.css("position","absolute");
popup.css("background","#fff");
popup.css("padding","20px");
popup.css("border","1px solid #333");
popup.css("border-radius","5px");

popup.css("top", "50%");
popup.css("left", "50%");            
popup.fadeOut(3000);

         		
         	}
           
         	

        }
   
       });

    }


    //  function mapaguru(userid){

    // 	$('#guru-modal').modal('show');
    // }

    var hideelement="";


    $(document).on("click", ".open-AssignGuru", function () {
     var userid = $(this).data('id');
     var key='findgurustable';
         hideelement=$(this).data('id');
     $.blockUI({ message: $('#divMessage') });
     
     $.ajax({
          url: 'functions.php',
		  type: 'POST',
		  data: {userid: userid,key: key},
		  success: function(response){

		  	$('#gurus').empty();
		  	$('#gurus').append(response);
		  	$.unblockUI();
		  	$('#gurustable').excelTableFilter(); 

          }
   
       });
     
     });

    $('#checksubmission').on('click', function(e){
         var data=$('#assignguruform').serializeFormJSON(); 
         var key='assignguru';
           // console.log(data); 
          $("#assignwait").css("display", "block");
          $.ajax({
          url: 'functions.php',
		  type: 'POST',
		  data: {data: data,key: key},
		  success: function(response){

		  	//console.log(response);
		  	if (response==1) {

		  		$("#assignwait").css("display", "none");
		  		$("#closewarningModal").click();
          $("#"+hideelement).hide();
		  	    //$("#warningModal").modal('hide');

		  	}else{

		  		$("#assignwait").css("display", "none");

		  	}
		  	
		  	 

          }
   
       });
         
    });

  
    function enablesubmission(value) {

    	//console.log(value);

    	if (value) {

    		//console.log($('#checksubmission'));

    		$('#checksubmission').attr('disabled',false);
    	}
    	
    	

    }

    
    (function ($) {
    $.fn.serializeFormJSON = function () {

        var o = {};
        var a = this.serializeArray();
        $.each(a, function () {
            if (o[this.name]) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };
})(jQuery);

 function sleep(milliseconds) {
  var start = new Date().getTime();
  for (var i = 0; i < 1e7; i++) {
    if ((new Date().getTime() - start) > milliseconds){
      break;
    }
  }
}