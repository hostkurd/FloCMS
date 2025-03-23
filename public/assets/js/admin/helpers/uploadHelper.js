var ajax = new XMLHttpRequest(); 

var uploadContainer = document.getElementById('upload-container');

var barContainer = document.getElementById('bar-container');
var progressbar = document.getElementById('bar');

var imageContainer = document.getElementById('image-container');
var uploadedImage = document.getElementById('uploaded-image');
var imagePathInput = document.getElementById('imagePath');

var errorContainer = document.getElementById('error-container');
var errorTitle = document.getElementById('error-title');
var errorDesc = document.getElementById('error-desc');

var placeHolder = document.getElementById('placeHolder');

if(uploadedImage.getAttribute('src')){
    //let currentValue = uploadedImage.getAttribute('src');
    uploadContainer.style.display = 'flex';
    uploadContainer.style.padding = 0;
    //uploadedImage.setAttribute('src',currentValue);
    imageContainer.style.display = 'block';
    barContainer.style.display = 'none';

    placeHolder.style.display = 'none';
}

function uploadFile(file, selector, maxSize){

    if(!file){ 
        // No File has been passed
        return;
    }
  
    // Collecting Form Data
    var form_Data = new FormData;
    form_Data.append('file',file);
    // Creating Responce Object
    let responce = null;
    let allowed = ['jpeg','jpg','png', 'webp'];
    let ext = file.name.split(".").pop();
    // File Size in KB, Format 00.00
    let fileSize = (file.size / 1024).toFixed(2);
    if(fileSize > maxSize){
        alert("File Size exceeded, Your File Size is: " +  fileSize +"KB, Allowed is: "+ maxSize +"KB");
        selector.value = null;
        return;
    }

    if(!allowed.includes(ext.toLowerCase()))
    {
        alert("this file is not valid: " + file.name);
        selector.value = null;
        return;
    }
    // Capture changes
    ajax.addEventListener('readystatechange',function(e){
        // unhide Upload Container and progress bar
        uploadContainer.style.display = 'flex';
        barContainer.style.display = 'flex';
        // Hide Placeholder Element
        placeHolder.style.display = 'none';

        if(ajax.readyState == 4){
            if(ajax.status == 200){
                if(ajax.responseText){
                    responce = JSON.parse(ajax.responseText);
                }
                //console.log(responce.fullpath);
                if(responce.url){
                    errorContainer.style.display = 'none';

                    uploadedImage.setAttribute('src', responce.url);
                    imagePathInput.setAttribute('value', responce.path);
                    
                    uploadContainer.style.padding = 0;
                    imageContainer.style.display = 'block';

                    barContainer.style.display = 'none';
                }
                if (responce.error) {
                    cancelUpload();
                    //get Value of video

                    uploadContainer.style.padding = '5px';

                    errorContainer.style.display = 'block';
                    errorTitle.innerHTML = responce.error.title;
                    errorDesc.innerHTML = responce.error.message;
                    barContainer.style.display = 'none';

                    imagePathInput.setAttribute('value', '');
                    imageContainer.style.display = 'none';
                    console.log('Error occured: ' + responce.error.message);
                }
            }
        }
    });

    //upload progress
    ajax.upload.addEventListener('progress',function(e){
        let percent = e.total ? Math.round(100 * e.loaded / e.total) : 0;//Math.round((e.loaded / e.total) * 100);
        progressbar.style.width = percent + '%';
        console.log('percent uploaded : ' + percent + ' | Loaded: ' + e.total);
    });

    if(responce && responce.url){
        ajax.abort;
    }
    ajax.open('post', site_url + '/uploadHelper.php', true);
    ajax.send(form_Data);
}

function freshStart(){
    //toogleImage();
    //console.log('entered');
    progressbar.style.width = '0%';

    imageContainer.style.display = 'none';
    errorContainer.style.display = 'none';
    uploadContainer.style.display = 'flex';
    barContainer.style.display = 'flex';
}

function cancelUpload(){
    ajax.abort;
    progressbar.style.width = '0%';
}

function deleteFile(elementId){
    //ajax.abort;
    //ajax = new XMLHttpRequest();
    var element = document.getElementById(elementId);
    var form_Data = new FormData;
    form_Data.append('action','delete');
    form_Data.append('url',uploadedImage.getAttribute('src'));
    
    var delAjax = new XMLHttpRequest();

    delAjax.addEventListener('readystatechange',function(e){
        if(delAjax.readyState == 4){
            if(delAjax.status == 200){
                // if(delAjax.responseText){
                //     responce = JSON.parse(delAjax.responseText);
                // }
                let delResponce;
                if(delAjax.responseText){
                    delResponce = JSON.parse(delAjax.responseText);
                }
                placeHolder.style.display = 'flex';
                console.log(delResponce);
            }
        }
    });

    delAjax.open('POST', site_url + '/uploadHelper.php', true);
    delAjax.send(form_Data);

    // reset File input
    element.value = '';

    // reset the interface
    imagePathInput.setAttribute('value', '');
    uploadedImage.setAttribute('src', '');

    //toogleImage();
    //showElement()
    imageContainer.style.display = 'none';
    uploadContainer.style.display = 'none';
}

