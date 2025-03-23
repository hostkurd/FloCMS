class DataHelper{

    constructor(options){
        this.form = document.getElementById(options.form);
        this.postUrl = options.url;
        this.modalDialog = document.getElementById(options.modal);
        this.formFields = options.fields;
        
        // Listen to modal dialog event
        this.listenToEvents(this);
    }

    showAlert($type, $title, $message, reload = false){
        Swal.fire({
          icon:$type,
          title:$title,
          text:$message
        }).then(function(){
            if(reload){
                location.reload();
            }
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

    saveData(c){
        console.log(site_url);
        var ajax = new XMLHttpRequest();
        var formFields = new FormData(this.form);
        ajax.addEventListener('readystatechange',function(e){ 
            if(e.target.readyState == 4){
                if(e.target.status == 200){
                    let response = null;
                    console.log(e.target.responseText);

                    if(e.target.responseText){
                        if(c.isJsonString(e.target.responseText)){
                            response = JSON.parse(e.target.responseText);

                            if(response.status == 'success'){
                                c.form.reset();
                                c.showAlert('success', 'Category Updated', response.message, true);
                                var modal = bootstrap.Modal.getInstance(c.modalDialog);
                                modal.hide();
                            }
                            if(response.status == 'error'){
                                c.showAlert('error', 'Not Added', response.message);
                            }

                        }else{
                            c.showAlert('error', 'Error', 'Something went wrong, please contact the website administrator');
                            return;
                        }
                    }
                }
            }
        });

        ajax.open('post', site_url + this.postUrl , true);
        ajax.send(formFields);
    }

    listenToEvents(callback){
        this.modalDialog.addEventListener('show.bs.modal',function (event) {
            var headerText = document.getElementById('headerText');
            var submitButton = document.getElementById('submitButton');

            var id = document.getElementById('id');
            if(id){
                callback.form.removeChild(id);
            }
            // Button that triggered the modal
            var button = event.relatedTarget;
            
            // Extract info from data-bs-* attributes
            var curId = button.getAttribute('data-id');

            if(curId){
                var i = document.createElement("input");;
                i.setAttribute("type", "hidden");
                i.setAttribute("name", "id");
                i.setAttribute("value", curId);
                i.setAttribute("id", 'id');

                callback.form.appendChild(i);
                submitButton.textContent = 'Save Changes';
                headerText.textContent = 'Edit Category Item';

                for (let i = 0; i < callback.formFields.length; i++) {
                    var value = button.getAttribute('data-v'+i);
                    document.getElementById(callback.formFields[i]).value = value;
                }
            }else{
                submitButton.textContent = 'Add Category';
                headerText.textContent = 'Add Category Item';
                for (let i = 0; i < callback.formFields.length; i++) {
                    document.getElementById(callback.formFields[i]).value = '';
                }
            }
        });
    }
    
}