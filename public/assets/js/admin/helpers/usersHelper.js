class UsersHelper{
    constructor(options){
        //this.form = document.getElementById(options.form);
        //this.loadingElement = document.getElementById(options.loadingElement);
        //this.captcha = document.getElementById(options.captcha);
    }

    // Verify User
    verifyUser(userToken){
      Swal.fire({
        title: 'Are You Sure You Want to Verify This User?',
        showDenyButton: true,
        confirmButtonText: 'Yes',
        denyButtonText: 'No',
        showLoaderOnConfirm: true,
        showClass: {
          backdrop: 'swal2-noanimation', // disable backdrop animation
          popup: '',                     // disable popup animation
          //icon: ''                       // disable icon animation
        },
        hideClass: {
          popup: '',                     // disable popup fade-out animation
        },
        customClass: {
          confirmButton: 'order-1',
          denyButton: 'order-2',
        },
        preConfirm: async () => {
            await makeRequest({"token":userToken},'/admin/users/verify/').then((resp)=>{
            console.log(resp);
            let responseData = null;
            if(isJsonString(resp)){
              responseData = JSON.parse(resp);
            }else{
              Swal.showValidationMessage('Something went wrong, please contact the website administrator. ' + userToken);
              return;
            }
            if(responseData.status == 'success'){
              showAlert('success', 'User Suspended', responseData.message).then((result) =>{
                if(result.isConfirmed == true){
                  location.reload();
                }
              });
              return;
            }else{
              Swal.showValidationMessage(responseData.message);
              return;
              //showAlert('error', 'Error Suspending User', responseData.message);
            }
            console.log(resp);
            //Swal.showValidationMessage(resp);
            //return;
          })
          .catch(err=>{
            console.log(err);
          });

        },
      allowOutsideClick: () => false,
      backdrop:true
      }).then((result) => {
        //console.log('result is: ' + JSON.stringify(result));
      });
    }
    


    // Suspending/Unsuspending User
    suspendUser(id, action){
      let MessageText = "";
      switch(action){
        case "suspend":
          MessageText = 'Are you sure you want to suspend this User?';
          break;
        case "unsuspend":
          MessageText = 'Are you sure you want to Unblock this User?';
          break;
      }

      Swal.fire({
        title: MessageText,
        showDenyButton: true,
        confirmButtonText: 'Yes',
        denyButtonText: 'No',
        showLoaderOnConfirm: true,
        showClass: {
          backdrop: 'swal2-noanimation', // disable backdrop animation
          popup: '',                     // disable popup animation
          //icon: ''                       // disable icon animation
        },
        hideClass: {
          popup: '',                     // disable popup fade-out animation
        },
        customClass: {
          confirmButton: 'order-1',
          denyButton: 'order-2',
        },
        preConfirm: async () => {
            await makeRequest({"userid":id, "action": action},'/admin/users/suspend/').then((resp)=>{
            let responseData = null;
            if(isJsonString(resp)){
              responseData = JSON.parse(resp);
            }else{
              Swal.showValidationMessage('Something went wrong, please contact the website administrator');
              return;
            }
            if(responseData.status == 'success'){
              showAlert('success', 'User Suspended', responseData.message).then((result) =>{
                if(result.isConfirmed == true){
                  location.reload();
                }
                console.log(JSON.stringify(result));
              });
              return;
            }else{
              Swal.showValidationMessage(responseData.message);
              console.log(responseData.message);
              return;
              //showAlert('error', 'Error Suspending User', responseData.message);
            }
            console.log(resp);
            //Swal.showValidationMessage(resp);
            //return;
          })
          .catch(err=>{
            console.log(err);
          });

        },
      allowOutsideClick: () => false,
      backdrop:true
      }).then((result) => {
        console.log('result is: ' + JSON.stringify(result));
      });
    }
    
}