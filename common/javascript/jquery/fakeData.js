var ringupJson = {
    "ringuptag": "Campaign_1",
    "custid": "",
    "trunks": "",
    "simult": "",
    "maxduration": "600",
    "sheduleArray": [
	{
	    "weekdays[]": "",
	    "timefrom[]": "0",
	    "timetill[]": "0",
	    "inputa[]": "",
	    "inputb[]": ""
	}
    ],
    "dest": "",
    "callerids": ""
};

var ivrJson = {
    "ivrname": "",
    "repeats": "2",
    "waitsecsfordigits": "6",
    "soundArray": [
	{
	    "timeout": "1",
	    "playsound": ""
	}
    ],
    "destArray": [
	{
	    "waitdigits": "",
	    "destinationnum": "",
	    "soundArray": [
		{
		    "timeout": "1",
		    "playsound": ""
		}
	    ],
	    "playsoundcallee": ""
	}
    ]
};

var PopUpDayTimeJson = {
    "shedule": [
	{
	    "weekdays[]": "",
	    "timefrom[]": "0",
	    "timetill[]": "0",
	    "inputa[]": "10",
	    "inputb[]": "1",
	    "inputc[]": "60"
	}
    ]
};

$(document).ready(function(){
// Create a hidden input element, and append it to the form:
    function addHidden(theForm, key, value) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        theForm.appendChild(input);
    }
// Shows JSON in nice popup
    function showFormJson(json,pt) {
        var jsonText = JSON.stringify(json, null, "    ");
        $('#popup')
            .empty()
            .append(pt)
            .append( $('<pre></pre>').append(jsonText) )
            .dialog({
                title: "JSON representation of the form",
                width: 600,
                height: 500
            });
    }
    $('#myForm').jqDynaForm();
    $('#myForm').jqDynaForm('set', PopUpDayTimeJson);
    $('a[name="ton"]').filter(function(){return this.previousElementSibling.value;}).css('visibility', 'visible');
    $('#myForm').submit(function(event){
	var json = $('#myForm').jqDynaForm('get');
	var postTo = $('#myForm').attr('action');
	var data = {json_data:JSON.stringify(json)};
	var name,
		form = document.createElement("form"),
		node = document.createElement("input");
	// Define what should happen when the response is loaded
	form.action = postTo;
	form.method = "POST";
	for(name in data) {
		node.name  = name;
		node.value = data[name].toString();
		form.appendChild(node.cloneNode());
	}
	// To be sent, the form needs to be attached to the main document.
	form.style.display = "none";
	document.body.appendChild(form);
	form.submit();
	// But once the form is sent, it's useless to keep it.
	document.body.removeChild(form);
	return false;
    });
});