class ContactHelper{
    constructor(options){
        this.form = document.getElementById(options.form);
        this.loadingElement = document.getElementById(options.loadingElement);
        this.captcha = document.getElementById(options.captcha);
    }

    // Using SweetAlert 2 in this Function
    showAlert($type, $title, $message){
        Swal.fire({
          icon:$type,
          title:$title,
          text:$message
        });
    }

    isJsonString(str) { 
        try {
            JSON.parse(str);
        } catch (e) {
            return false;
        }
        return true;
    }

    refreshCaptcha(){
        this.captcha.style.background = "url('"+ site_url +"/assets/captcha-image/?" + Math.floor(Math.random() * 100) + "')"
    }

    sendMessage(callback){
        var formFields = new FormData(this.form);
        var ajax = new XMLHttpRequest();
    
          this.loadingElement.style.display = 'block';

          ajax.addEventListener('readystatechange',function(e){ 
    
            if(e.target.readyState == 4){
              if(e.target.status == 200)
              {
                callback.refreshCaptcha();
                callback.loadingElement.style.display = 'none';
    
                let response = null;
                console.log(e.target.responseText);

                if(e.target.responseText){
                  if(callback.isJsonString(e.target.responseText)){
                    response = JSON.parse(e.target.responseText);
                  }else{
                    callback.showAlert('error', 'Error', 'Something went wrong, please contact the website administrator');
                    return;
                  }
                }
                  
                if(response.status == 'success'){
                    callback.form.reset();
                    callback.showAlert('success', 'Message Sent', response.message);
                  }
                  if(response.status == 'error'){
                    callback.showAlert('error', 'Error Sending Message', response.message);
                  }
                }
              }
          });
          
          ajax.open('post', site_url + '/contact/', true);
          ajax.send(formFields);
    }
}