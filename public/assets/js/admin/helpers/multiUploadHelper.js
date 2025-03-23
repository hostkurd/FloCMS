class MultiUploadHelper{
    constructor(options){
        this.barsContainer = document.getElementById(options.barsContainer);
        this.placeHolder = document.getElementById(options.placeHolder);
        this.imageContainer = document.getElementById(options.imageContainer);  
        this.imagesHolder = document.getElementById(options.imagesHolder);  

        this.isEdit = options.isEdit,
        this.ajaxHandler = {};
        this.cancelIndexs = [];
        this.allowedFiles = ['jpeg','jpg','png','webp','gif'];

        if(this.isEdit){
            this.getUploadedData();
        }
    }

    tempFiles = [];
    uploadedFiles = [];

    // get list of gallery images During Edit
    getUploadedData(){
        let data = this.imagesHolder.getAttribute('value');
        //console.log('data is: ' + data);
        if(!data)
            return;
        const dataArray = data.split(',');
        this.imageContainer.style.display = 'flex';
        this.placeHolder.style.display = 'none';
        dataArray.forEach(e=>{
            console.log('Edit Mode: '+ e);
            this.imageContainer.append(this.createImage(e));
            this.uploadedFiles.push(e);
        });
    }
    // Delete Image From the List
    deleteImage(e){
        var imagePath = e.getAttribute('data-image');
        const index = this.uploadedFiles.indexOf(imagePath);
        if (index > -1) { // only splice array when item is found
            this.uploadedFiles.splice(index, 1); // 2nd parameter means remove one item only
        }

        this.imagesHolder.setAttribute('value',this.uploadedFiles);
        console.log(imagePath);
        this.refreshImages();
    }
    // Recreate images based on file pathes data
    refreshImages(){
        this.imageContainer.innerHTML = '';
        this.imageContainer.style.display = 'flex';

        //this.barsContainer.style.display = 'none';
        this.placeHolder.style.display = 'none';

        if(this.uploadedFiles.length < 1){
            this.placeHolder.style.display = 'flex';
            this.imageContainer.style.display = 'none';
            return;
        }
        this.uploadedFiles.forEach(e=>{
            this.imageContainer.append(this.createImage(e));
        }); 
        this.imagesHolder.setAttribute('value',this.uploadedFiles);
    }
    // Upload Files
    upload(files){
        this.barsContainer.innerHTML = '';
        this.barsContainer.display = 'flex';
        // Temporary UploadedFiles
        this.tempFiles = [];
        this.ajaxHandler = {};
        this.cancelIndexs = [];
        var uploadedFiless = [];

        for (var i = 0; i < files.length; i++) {
            this.ajaxHandler[i] = new XMLHttpRequest();
            let file = files[i];
            let ext = file.name.split(".").pop();

            if(!this.allowedFiles.includes(ext.toLowerCase()))
            {
                alert("this file is not valid: " + file.name);
                continue;
            }

			this.barsContainer.append(this.createProgressBar(i, file.name));

            var form = new FormData();
            form.append("file",file);

            this.ajaxHandler[i].addEventListener('readystatechange',function(e){
                if(e.target.readyState == 4)
                {
                    if(e.target.status == 200)
                    {
                        let response = null;
                        if(e.target.responseText){
                            response = JSON.parse(e.target.responseText);
                        }
                        if(response.url){
                            uploadedFiless.push(response.url);
                        }
                        if(response.error){
                            this.CancelUpload(index);
                            console.log(response);
                        }
                    }
                }
            });

            this.ajaxHandler[i].upload.myindex = i;

            //console.log('Index setting ' + this.ajaxHandler[i].upload.myindex);
            this.ajaxHandler[i].upload.addEventListener('progress',function(e){

                let index = e.target.myindex;

                let prog = document.querySelector(".prog-bar"+index);
                let percent = Math.round((e.loaded / e.total) * 100);
                prog.style.width = percent + "%";
                prog.innerHTML = percent + "%";
            });
            this.ajaxHandler[i].open('post', site_url + '/uploadHelper.php', true);
            this.ajaxHandler[i].send(form);
        }

        this.tempFiles.push(uploadedFiless);

        this.delay(1000).then(() => {
            //this.placeHolder.style.display = 'none';
            this.tempFiles.forEach(arr=>{
                arr.forEach(e=>{
                    this.uploadedFiles.push(e);
                })
            });
            this.refreshImages();
            //this.refreshImages();
        }); 
    }
    // Delay Function
    delay(time) {
    return new Promise(resolve => setTimeout(resolve, time));
    }
    //Create Image element
    createImage(path){
        var img = document.createElement('img');
        img.classList.add('gal-image');
        img.src = path;

        var div = document.createElement('div');
        div.classList.add('gal-image-box')

        var a = document.createElement('a');
        a.classList.add('gal-image-link');
        a.setAttribute("onclick",'uploadClass.deleteImage(this)');
        a.setAttribute('data-image',path);
        a.title = 'Delete Image';

        var i = document.createElement('i');
        i.classList.add('fa-sharp');
        i.classList.add('fa-regular');
        i.classList.add('fa-xmark');

        a.appendChild(i);
        div.appendChild(img);
        div.appendChild(a);

        return div;
    }
    createProgressBar(index, fileName){
        let main = document.createElement('div');
            main.classList.add('col-md-3');
            main.classList.add('p-0');
            main.classList.add('pe-2');
            main.classList.add('mb-3');

        let div = document.createElement('div');
            div.classList.add('bar-block')

        let div2 = document.createElement('div');
            div2.classList.add('prog');
            div2.classList.add('me-2');

        let div3 = document.createElement('div');
            div3.classList.add('prog-bar');
            div3.classList.add('prog-bar' + index);
            div3.appendChild(document.createTextNode('0%'));

        let progressBar = document.createElement('div');
            progressBar.classList.add('gal-bar');
            progressBar.classList.add('my-1');
        
        let fileInfo = document.createElement('div');
            fileInfo.appendChild(document.createTextNode(fileName));

        let abortButton =  document.createElement('a');
            abortButton.classList.add('abort-btn');
            abortButton.title = 'Cancel Upload';
            abortButton.setAttribute('onclick',`uploadClass.CancelUpload(${index})`);

        let buttonIcon = document.createElement('i');
            buttonIcon.classList.add('fa-sharp');
            buttonIcon.classList.add('fa-regular');
            buttonIcon.classList.add('fa-xmark');

        
        abortButton.append(buttonIcon)
        progressBar.append(div2);
        progressBar.append(abortButton);

        div2.append(div3);

        div.append(fileInfo)
        div.append(progressBar);
        main.append(div);
        return main;
    }
    // Cancel the upload progress
    CancelUpload(index)
    {
        this.ajaxHandler[index].abort();
    }
    

}