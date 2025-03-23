function InlineUpload(file, selector, maxSize, uniqueId){
    var ajax = new XMLHttpRequest();

    if(!file){ 
        // No File has been passed
        return;
    }
    var uContainer = document.getElementById('upload-container-' + uniqueId);
    var iContainer = document.getElementById('image-container-' + uniqueId);
    var bContainer = document.getElementById('bar-container-' + uniqueId);
    var eContainer = document.getElementById('error-container-' + uniqueId);
    var pHolder = document.getElementById('placeHolder-' + uniqueId);
    var pBar = document.getElementById('bar-' + uniqueId);
    var uImage = document.getElementById('uploaded-image-' + uniqueId);
    var imagePath = document.getElementById('imagePath-' + uniqueId);

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
        uContainer.style.display = 'flex';
        bContainer.style.display = 'flex';
        // Hide Placeholder Element
        pHolder.style.display = 'none';

        if(ajax.readyState == 4){
            if(ajax.status == 200){
                if(ajax.responseText){
                    responce = JSON.parse(ajax.responseText);
                }
                //console.log(responce.fullpath);
                if(responce.url){
                    eContainer.style.display = 'none';

                    uImage.setAttribute('src', responce.url);
                    imagePath.setAttribute('value', responce.path);

                    uContainer.style.padding = 0;
                    iContainer.style.display = 'block';

                    bContainer.style.display = 'none';
                }
                if (responce.error) {
                    cancelUpload();
                    //get Value of video

                    uContainer.style.padding = '5px';

                    eContainer.style.display = 'block';
                    errorTitle.innerHTML = responce.error.title;
                    errorDesc.innerHTML = responce.error.message;
                    bContainer.style.display = 'none';

                    imagePath.setAttribute('value', '');
                    iContainer.style.display = 'none';
                    console.log('Error occured: ' + responce.error.message);
                }
            }
        }
    });

    //upload progress
    ajax.upload.addEventListener('progress',function(e){
        let percent = e.total ? Math.round(100 * e.loaded / e.total) : 0;//Math.round((e.loaded / e.total) * 100);
        pBar.style.width = percent + '%';
        console.log('percent uploaded : ' + percent + ' | Loaded: ' + e.total);
    });

    if(responce && responce.url){
        ajax.abort;
    }
    ajax.open('post', site_url + '/uploadHelper.php', true);
    ajax.send(form_Data);
}

function Checkimage(uniqueId){
    var uContainer = document.getElementById('upload-container-' + uniqueId);
    var iContainer = document.getElementById('image-container-' + uniqueId);
    var bContainer = document.getElementById('bar-container-' + uniqueId);
    var pHolder = document.getElementById('placeHolder-' + uniqueId);
    var uImage = document.getElementById('uploaded-image-' + uniqueId);
    
    var imagePath = uImage.getAttribute('src');

    //console.log(imagePath);
    isImageExists(imagePath, (exists) => {
      if (exists) {
        // Success code
        uContainer.style.display = 'flex';
        uContainer.style.padding = 0;
        uImage.setAttribute('src',imagePath);
        iContainer.style.display = 'block';
        bContainer.style.display = 'none';
        pHolder.style.display = 'none';
      } else {
        // Fail code
        console.log ('Image Path not valid: ' + imagePath);
      }
    });
    // if(imagePath){
    //     //let currentValue = uploadedImage.getAttribute('src');
    //     uContainer.style.display = 'flex';
    //     uContainer.style.padding = 0;
    //     //uploadedImage.setAttribute('src',currentValue);
    //     iContainer.style.display = 'block';
    //     bContainer.style.display = 'none';
    
    //     pHolder.style.display = 'none';
    // }
}

function DeleteFile(uniqueId){
    var element = document.getElementById('fileInput-' + uniqueId);
    var uImage = document.getElementById('uploaded-image-' + uniqueId);
    var uContainer = document.getElementById('upload-container-' + uniqueId);
    var iContainer = document.getElementById('image-container-' + uniqueId);
    var pHolder = document.getElementById('placeHolder-' + uniqueId);
    var imagePath = document.getElementById('imagePath-' + uniqueId);

    var form_Data = new FormData;
    form_Data.append('action','delete');

    form_Data.append('url',uImage.getAttribute('src'));
    
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
                pHolder.style.display = 'flex';
                console.log(delResponce);
            }
        }
    });

    delAjax.open('POST', site_url + '/uploadHelper.php', true);
    delAjax.send(form_Data);

    // reset File input
    element.value = '';

    // reset the interface
    imagePath.setAttribute('value', '');
    uImage.setAttribute('src', '');

    //toogleImage();
    //showElement()
    iContainer.style.display = 'none';
    uContainer.style.display = 'none';
}

function isImageExists(url, callback) {
    const img = new Image();
    img.src = url;

    if (img.complete) {
      callback(true);
    } else {
      img.onload = () => {
        callback(true);
      };
      
      img.onerror = () => {
        callback(false);
      };
    }
  }