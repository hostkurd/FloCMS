
function checkAlias(selector, url){
    const aliasSelector = document.getElementById(selector);
    aliasSelector.addEventListener("change",function(){
        //console.log(this.value);
        $.post( site_url + url, { alias: this.value }, function (data){
            if(data=='0'){
                //do 1
                console.log('Empty');
                aliasSelector.classList.add('is-invalid');
                return false;
            }
            else if(data=='1'){
                console.log('Exists');
                aliasSelector.classList.add('is-invalid');
                aliasSelector.setCustomValidity('This alias already exist.');
                aliasSelector.reportValidity();
                return false;
            }else if(data=='2'){
                console.log('Not Existe');
                aliasSelector.classList.remove('is-invalid');
                aliasSelector.setCustomValidity('');
                return true;
            }
        });
    })
}

// Function for Validating Form Fields
// Uses Bootstrap Form Validation Techniques
function validateForm(selector='.needs-validation'){
    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.querySelectorAll(selector)
    // Loop over them and prevent submission
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
                form.classList.add('was-validated')
        }, false)
    })
}

// Function for previewing image inside placeholder
function previewImage(selector, container, placeholder, text, isEdit = false){
    
    const ImageSelector = document.getElementById(selector);
    const previewContainer = document.getElementById(container);
    const previewImage = previewContainer.querySelector(placeholder);
    const previewText = previewContainer.querySelector(text);

    ImageSelector.addEventListener("change",function(){
        const file = this.files[0];
        
        if(file){ 
            const reader = new FileReader();

            previewText.style.display = "none";
            previewImage.style.display = "block";

            reader.addEventListener("load",function(){
                previewImage.setAttribute("src",this.result);
            })
            reader.readAsDataURL(file);
        }
    });

    if (isEdit){
        previewText.style.display = "none";
        previewImage.style.display = "block";
    }
}

// Replace Spaces with dash for input value
function replaceSpaces(selector){

    const inputSelector = document.getElementById(selector);

    inputSelector.addEventListener('input',function(){
        str = this.value;
        str = str.replace(/\s+/g, '-').toLowerCase();
        this.value = str;
    })
}