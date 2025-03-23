// Custom Functions

// Website URL For Java Class Functions
// var site_url = 'http://localhost/fectum/';


/* Show Alert for Delete Record */
function ConfirmDelete(controller, id, name = null, action=null){
    console.log('site url is '+ site_url);
    var childAction, itemName;
    if(action ==null){childAction = 'delete';}else{childAction = action;}
    if(name ==null){itemName = 'Item';}else{itemName = name;}
    Swal.fire({
        icon: 'info',
        title:'Delete ' + itemName,
        text:'Are you Sure you want to Delete The selected ' + itemName + ' ?',
        showDenyButton: true,
        confirmButtonText: `Yes`,
        denyButtonText: `No`,
        customClass: {
            confirmButton: 'order-2',
            denyButton: 'order-3',
        }
    }).then((result)=>{
        if (result.isConfirmed) {
            $.ajax({
                type: 'post',
                url: site_url + '/admin/'+ controller +'/' + childAction + '/' + id,
                data:{'id':id},
                success:function(){
                    //Refresh Current Page
                    setTimeout(
                        function() 
                        {
                          location.reload();
                        }, 100);//min 0001

                    // load(site_url + 'admin/'+ controller +'/');
                },
                error:function(res){
                    if(res.status =='404'){
                        swal.fire({
                            title:'Error '+res.status,
                            text:'Requested has URL not been Found.'
                        })
                    }else{
                        swal.fire(res.responseText)
                    }
                }
            })
            return true;
        } else  {
            return false;
        }
    })
}

// Redirect to another Page
function redirectTo(timeout,url){
    setTimeout(
        function() 
        {
            window.location.replace(site_url+url);
        }, timeout);//min 0001
}

// Copy Text From an input area
function copyText(textInput){
// Get the text field
    var copyText = document.getElementById(textInput);

    // Select the text field
    copyText.select();
    copyText.setSelectionRange(0, 99999); // For mobile devices

    // Copy the text inside the text field
    navigator.clipboard.writeText(copyText.value);

    Swal.fire({ icon: 'success',
        title:'Image Path Copied ',
        showCancelButton: false, // There won't be any cancel button
        showConfirmButton: false,   
        timer: 1000
    });
    // Alert the copied text
    //alert("Copied the text: " + copyText.value);
}
