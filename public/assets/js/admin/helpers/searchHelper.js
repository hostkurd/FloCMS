// Public Variables
var resultContainer = document.getElementById('results-container');
var searchPopup = document.getElementById('search-popup');
var loadingText = document.getElementById('searching-text');

// Search Team Members
function searchMembers(element){
    let keyword = element.value;
    var ajax = new XMLHttpRequest;

    // Collecting Form Data
    var form_Data = new FormData;
    form_Data.append('keyword',keyword);

    if(keyword.length < 1){
        resultContainer.innerHTML = emptyIcon();
        searchPopup.style.display = 'none';
        return;
    }

    loadingText.style.display = 'block';
    // Capture changes
    ajax.addEventListener('readystatechange',function(e){
        if(ajax.readyState == 4){
            if(ajax.status == 200){
                loadingText.style.display = 'none';
                if(ajax.responseText){
                    responce = JSON.parse(ajax.responseText);
                }
                resultContainer.innerHTML = "";
                if(responce.length > 0 && keyword.length > 0){
                    searchPopup.style.display = 'block';
                    for (let i = 0; i < responce.length; i++) {

                        var li = createResultElement({
                            text:responce[i].name,
                            copyLink:"/admin/team/edit/"+responce[i].id,
                            defaultLink:"/admin/team/edit/"+responce[i].id,
                            deleteFunction: "ConfirmDelete('team', " + responce[i].id + " , '" + responce[i].name + "' ,'delete')",
                        });
                        resultContainer.appendChild(li);
                    }
                }else{
                    resultContainer.innerHTML = emptyIcon();
                    //searchPopup.style.display = 'block';
                }
            }
        }
    });
    ajax.open('post', site_url + '/admin/team/', true);
    ajax.send(form_Data);

    capturePopupClick(element);
}

// Search Projects
function searchProjects(element){
    let keyword = element.value;
    var ajax = new XMLHttpRequest;

    // Collecting Form Data
    var form_Data = new FormData;
    form_Data.append('keyword',keyword);

    if(keyword.length < 1){
        resultContainer.innerHTML = emptyIcon();
        searchPopup.style.display = 'none';
        return;
    }

    loadingText.style.display = 'block';
    // Capture changes
    ajax.addEventListener('readystatechange',function(e){
        if(ajax.readyState == 4){
            if(ajax.status == 200){
                loadingText.style.display = 'none';
                if(ajax.responseText){
                    //console.log(ajax.responseText);
                    responce = JSON.parse(ajax.responseText);
                }
                resultContainer.innerHTML = "";
                if(responce.length > 0 && keyword.length > 0){
                    searchPopup.style.display = 'block';
                    for (let i = 0; i < responce.length; i++) {

                        var li = createResultElement({
                            text:responce[i].title,
                            copyLink:"/admin/projects/copy/"+responce[i].id,
                            editLink:"/admin/projects/edit/"+responce[i].id,
                            viewLink:"/" + responce[i].lang +"/projects/view/"+responce[i].id,
                            viewTarget:"_blank",
                            deleteFunction: "ConfirmDelete('projects', " + responce[i].id + " , '" + responce[i].title + "' ,'delete')",
                        });
                        resultContainer.appendChild(li);
                    }
                }else{
                    resultContainer.innerHTML = emptyIcon();
                }
            }
        }
    });
    ajax.open('post', site_url + '/admin/projects/', true);
    ajax.send(form_Data);

    capturePopupClick(element);
}

// Search Products
function searchProducts(element){
    let keyword = element.value;
    var ajax = new XMLHttpRequest;

    // Collecting Form Data
    var form_Data = new FormData;
    form_Data.append('keyword',keyword);

    if(keyword.length < 1){
        resultContainer.innerHTML = emptyIcon();
        searchPopup.style.display = 'none';
        return;
    }

    loadingText.style.display = 'block';
    // Capture changes
    ajax.addEventListener('readystatechange',function(e){
        if(ajax.readyState == 4){
            if(ajax.status == 200){
                loadingText.style.display = 'none';
                if(ajax.responseText){
                    //console.log(ajax.responseText);
                    responce = JSON.parse(ajax.responseText);
                }
                resultContainer.innerHTML = "";
                if(responce.length > 0 && keyword.length > 0){
                    searchPopup.style.display = 'block';
                    for (let i = 0; i < responce.length; i++) {

                        var li = createResultElement({
                            text:responce[i].name,
                            copyLink:"/admin/products/copy/"+responce[i].id,
                            editLink:"/admin/products/edit/"+responce[i].id,
                            viewLink:"/" + responce[i].lang +"/products/detail/"+responce[i].id,
                            viewTarget:"_blank",
                            deleteFunction: "ConfirmDelete('products', " + responce[i].id + " , '" + responce[i].title + "' ,'delete')",
                        });
                        resultContainer.appendChild(li);
                    }
                }else{
                    resultContainer.innerHTML = emptyIcon();
                }
            }
        }
    });
    ajax.open('post', site_url + '/admin/products/', true);
    ajax.send(form_Data);

    capturePopupClick(element);
}

// Search Slides
function searchSlides(element){
    let keyword = element.value;
    var ajax = new XMLHttpRequest;

    // Collecting Form Data
    var form_Data = new FormData;
    form_Data.append('keyword',keyword);

    if(keyword.length < 1){
        resultContainer.innerHTML = emptyIcon();
        searchPopup.style.display = 'none';
        return;
    }

    loadingText.style.display = 'block';
    // Capture changes
    ajax.addEventListener('readystatechange',function(e){
        if(ajax.readyState == 4){
            if(ajax.status == 200){
                loadingText.style.display = 'none';
                if(ajax.responseText){
                    //console.log(ajax.responseText);
                    responce = JSON.parse(ajax.responseText);
                }
                resultContainer.innerHTML = "";
                if(responce.length > 0 && keyword.length > 0){
                    searchPopup.style.display = 'block';
                    for (let i = 0; i < responce.length; i++) {

                        var li = createResultElement({
                            text:responce[i].title,
                            defaultLink:"/admin/home/editslide/"+responce[i].id,
                            copyLink:"/admin/home/copyslide/"+responce[i].id,
                            deleteFunction: "ConfirmDelete('home', " + responce[i].id + " , '" + responce[i].title + "' ,'deleteslide')",
                        });
                        resultContainer.appendChild(li);
                    }
                }else{
                    resultContainer.innerHTML = emptyIcon();
                }
            }
        }
    });
    ajax.open('post', site_url + '/admin/home/slides/', true);
    ajax.send(form_Data);

    capturePopupClick(element);
}

// Search Projects
function searchPosts(element){
    let keyword = element.value;
    var ajax = new XMLHttpRequest;

    // Collecting Form Data
    var form_Data = new FormData;
    form_Data.append('keyword',keyword);

    if(keyword.length < 1){
        resultContainer.innerHTML = emptyIcon();
        searchPopup.style.display = 'none';
        return;
    }

    loadingText.style.display = 'block';
    // Capture changes
    ajax.addEventListener('readystatechange',function(e){
        if(ajax.readyState == 4){
            if(ajax.status == 200){
                loadingText.style.display = 'none';
                if(ajax.responseText){
                    //console.log(ajax.responseText);
                    responce = JSON.parse(ajax.responseText);
                }
                resultContainer.innerHTML = "";
                if(responce.length > 0 && keyword.length > 0){
                    searchPopup.style.display = 'block';
                    for (let i = 0; i < responce.length; i++) {

                        var li = createResultElement({
                            text:responce[i].title,
                            copyLink:"/admin/blog/copy/"+responce[i].id,
                            editLink:"/admin/blog/edit/"+responce[i].id,
                            viewLink:"/" + responce[i].lang +"/blog/view/"+responce[i].id,
                            viewTarget:"_blank",
                            deleteFunction: "ConfirmDelete('blog', " + responce[i].id + " , '" + responce[i].title + "' ,'delete')",
                        });
                        resultContainer.appendChild(li);
                    }
                }else{
                    resultContainer.innerHTML = emptyIcon();
                }
            }
        }
    });
    ajax.open('post', site_url + '/admin/blog/', true);
    ajax.send(form_Data);

    capturePopupClick(element);
}

// Search Pages
function searchPages(element){
    let keyword = element.value;
    var ajax = new XMLHttpRequest;

    // Collecting Form Data
    var form_Data = new FormData;
    form_Data.append('keyword',keyword);

    if(keyword.length < 1){
        resultContainer.innerHTML = emptyIcon();
        searchPopup.style.display = 'none';
        return;
    }

    loadingText.style.display = 'block';
    // Capture changes
    ajax.addEventListener('readystatechange',function(e){
        if(ajax.readyState == 4){
            if(ajax.status == 200){
                loadingText.style.display = 'none';
                if(ajax.responseText){
                    //console.log(ajax.responseText);
                    responce = JSON.parse(ajax.responseText);
                }
                resultContainer.innerHTML = "";
                if(responce.length > 0 && keyword.length > 0){
                    searchPopup.style.display = 'block';
                    for (let i = 0; i < responce.length; i++) {

                        var li = createResultElement({
                            text:responce[i].title,
                            copyLink:"/admin/pages/copy/"+responce[i].id,
                            defaultLink:"/admin/pages/edit/"+responce[i].id,
                            viewLink:"/" + responce[i].lang + "/pages/view/"+responce[i].alias,
                            viewTarget:"_blank",
                            deleteFunction: "ConfirmDelete('pages', " + responce[i].id + " , '" + responce[i].title + "' ,'delete')",
                        });
                        resultContainer.appendChild(li);
                    }
                }else{
                    resultContainer.innerHTML = emptyIcon();
                }
            }
        }
    });
    ajax.open('post', site_url + '/admin/pages/list/', true);
    ajax.send(form_Data);

    capturePopupClick(element);
}

// Search Pages
function searchServices(element){
    let keyword = element.value;
    var ajax = new XMLHttpRequest;

    // Collecting Form Data
    var form_Data = new FormData;
    form_Data.append('keyword',keyword);

    if(keyword.length < 1){
        resultContainer.innerHTML = emptyIcon();
        searchPopup.style.display = 'none';
        return;
    }

    loadingText.style.display = 'block';
    // Capture changes
    ajax.addEventListener('readystatechange',function(e){
        if(ajax.readyState == 4){
            if(ajax.status == 200){
                loadingText.style.display = 'none';
                if(ajax.responseText){
                    //console.log(ajax.responseText);
                    responce = JSON.parse(ajax.responseText);
                }
                resultContainer.innerHTML = "";
                if(responce.length > 0 && keyword.length > 0){
                    searchPopup.style.display = 'block';
                    for (let i = 0; i < responce.length; i++) {

                        var li = createResultElement({
                            text:responce[i].title,
                            copyLink:"/admin/services/copy/"+responce[i].id,
                            defaultLink:"/admin/services/edit/"+responce[i].id,
                            editLink:"/admin/services/edit/"+responce[i].id,
                            deleteFunction: "ConfirmDelete('services', " + responce[i].id + " , '" + responce[i].title + "' ,'delete')",
                        });
                        resultContainer.appendChild(li);
                    }
                }else{
                    resultContainer.innerHTML = emptyIcon();
                }
            }
        }
    });
    ajax.open('post', site_url + '/admin/services/', true);
    ajax.send(form_Data);

    capturePopupClick(element);
}

// Search Certificates
function searchCertificates(element){
    let keyword = element.value;
    var ajax = new XMLHttpRequest;

    // Collecting Form Data
    var form_Data = new FormData;
    form_Data.append('keyword',keyword);

    if(keyword.length < 1){
        resultContainer.innerHTML = emptyIcon();
        searchPopup.style.display = 'none';
        return;
    }

    loadingText.style.display = 'block';
    // Capture changes
    ajax.addEventListener('readystatechange',function(e){
        if(ajax.readyState == 4){
            if(ajax.status == 200){
                loadingText.style.display = 'none';
                if(ajax.responseText){
                    //console.log(ajax.responseText);
                    responce = JSON.parse(ajax.responseText);
                }
                resultContainer.innerHTML = "";
                if(responce.length > 0 && keyword.length > 0){
                    searchPopup.style.display = 'block';
                    for (let i = 0; i < responce.length; i++) {

                        var li = createResultElement({
                            text:responce[i].title,
                            copyLink:"/admin/certificates/copy/"+responce[i].id,
                            defaultLink:"/admin/certificates/edit/"+responce[i].id,
                            editLink:"/admin/certificates/edit/"+responce[i].id,
                            deleteFunction: "ConfirmDelete('certificates', " + responce[i].id + " , '" + responce[i].title + "' ,'delete')",
                        });
                        resultContainer.appendChild(li);
                    }
                }else{
                    resultContainer.innerHTML = emptyIcon();
                }
            }
        }
    });
    ajax.open('post', site_url + '/admin/certificates/', true);
    ajax.send(form_Data);

    capturePopupClick(element);
}

// Search Projects
function searchBrands(element){
    let keyword = element.value;
    var ajax = new XMLHttpRequest;

    // Collecting Form Data
    var form_Data = new FormData;
    form_Data.append('keyword',keyword);

    if(keyword.length < 1){
        resultContainer.innerHTML = emptyIcon();
        searchPopup.style.display = 'none';
        return;
    }

    loadingText.style.display = 'block';
    // Capture changes
    ajax.addEventListener('readystatechange',function(e){
        if(ajax.readyState == 4){
            if(ajax.status == 200){
                loadingText.style.display = 'none';
                if(ajax.responseText){
                    //console.log(ajax.responseText);
                    responce = JSON.parse(ajax.responseText);
                }
                resultContainer.innerHTML = "";
                if(responce.length > 0 && keyword.length > 0){
                    searchPopup.style.display = 'block';
                    for (let i = 0; i < responce.length; i++) {

                        var li = createResultElement({
                            text:responce[i].title,
                            copyLink:"/admin/brands/copy/"+responce[i].id,
                            defaultLink:"/admin/brands/edit/"+responce[i].id,
                            editLink:"/admin/brands/edit/"+responce[i].id,
                            deleteFunction: "ConfirmDelete('brands', " + responce[i].id + " , '" + responce[i].title + "' ,'delete')",
                        });
                        resultContainer.appendChild(li);
                    }
                }else{
                    resultContainer.innerHTML = emptyIcon();
                }
            }
        }
    });
    ajax.open('post', site_url + '/admin/brands/', true);
    ajax.send(form_Data);

    capturePopupClick(element);
}


// Search Team Members
function searchUsers(element){
    let keyword = element.value;
    var ajax = new XMLHttpRequest;

    // Collecting Form Data
    var form_Data = new FormData;
    form_Data.append('keyword',keyword);

    if(keyword.length < 1){
        resultContainer.innerHTML = emptyIcon();
        searchPopup.style.display = 'none';
        return;
    }

    loadingText.style.display = 'block';
    // Capture changes
    ajax.addEventListener('readystatechange',function(e){
        if(ajax.readyState == 4){
            if(ajax.status == 200){
                loadingText.style.display = 'none';
                if(ajax.responseText){
                    responce = JSON.parse(ajax.responseText);
                }
                resultContainer.innerHTML = "";
                if(responce.length > 0 && keyword.length > 0){
                    searchPopup.style.display = 'block';
                    for (let i = 0; i < responce.length; i++) {
                        var li = createResultElement({
                            text:responce[i].full_name,
                            editLink:"/admin/users/edit/"+responce[i].id,
                            defaultLink:"/admin/users/edit/"+responce[i].id,
                            deleteFunction: "ConfirmDelete('users', " + responce[i].id + " , '" + responce[i].full_name + "' ,'delete')",
                        });
                        resultContainer.appendChild(li);
                    }
                }else{
                    resultContainer.innerHTML = emptyIcon();
                    //searchPopup.style.display = 'block';
                }
            }
        }
    });
    ajax.open('post', site_url + '/admin/users/', true);
    ajax.send(form_Data);

    capturePopupClick(element);
}

// Create Result Element
function createResultElement(options){
    // Create li element
    var li = document.createElement("li");
        li.classList.add("d-flex");

    // Create icon Nodes
    var editIcon = document.createElement('i');
        editIcon.classList.add('fa-sharp');
        editIcon.classList.add('fa-regular');
        editIcon.classList.add('fa-pen-to-square');

    var delIcon = document.createElement('i');
        delIcon.classList.add('fa-sharp');
        delIcon.classList.add('fa-regular');
        delIcon.classList.add('fa-trash');

    var viewIcon = document.createElement('i');
        viewIcon.classList.add('fa-sharp');
        viewIcon.classList.add('fa-regular');
        viewIcon.classList.add('fa-eye');

    var copyIcon = document.createElement('i');
        copyIcon.classList.add('fa-sharp');
        copyIcon.classList.add('fa-regular');
        copyIcon.classList.add('fa-copy');


    // Create Link Nodes
    var a = document.createElement('a');
        a.appendChild(document.createTextNode(options.text));
        a.title = options.text;
        options.defaultLink ? a.href = site_url + options.defaultLink:'';
        a.target = options.defaultTarget?options.defaultTarget:'_self';

    var a2 = document.createElement('a');
        a2.appendChild(editIcon);
        a2.title = "Edit";
        a2.href = site_url + options.editLink;
        a2.style.width = 'auto';

    var a3 = document.createElement('a');
        a3.appendChild(delIcon);
        a3.title = "Delete";
        a3.href = options.deleteFunction?'#':site_url + options.deleteLink;
        a3.setAttribute("onclick",options.deleteFunction);
        a3.style.width = 'auto';

    var a4 = document.createElement('a');
        a4.appendChild(viewIcon);
        a4.title = "View";
        a4.href = site_url + options.viewLink;
        a4.style.width = 'auto';
        a4.target = options.viewTarget?options.viewTarget:'_self';

    var a5 = document.createElement('a');
        a5.appendChild(copyIcon);
        a5.title = "Duplicate";
        a5.href = site_url + options.copyLink;
        a5.style.width = 'auto';

    //Append elements
    li.appendChild(a);
    if(options.copyLink){li.appendChild(a5);} // Copy Button
    if(options.editLink){li.appendChild(a2);} // Edit Button
    if(options.deleteLink || options.deleteFunction){li.appendChild(a3);} // Delete Button
    if(options.viewLink){li.appendChild(a4);} // View Button
    

    return li;
}
// Generate Empty Icon
function emptyIcon(){
    return '<div style="color: #b3b3b3;"><i class="fa-sharp fa-regular fa-circle-exclamation text-center d-block w-100 mt-3 mb-2 empty-icon"></i><P class="text-center mb-2 mt-0">No Result</P></div>';
}

function capturePopupClick(element){
    var keyword = element.value;
    
    searchPopup.style.display = 'block';
    // Hide Popup When click outside of popup
    document.addEventListener('click', function handleClickOutsideBox(event) {
        if (!searchPopup.contains(event.target)) {
            //element.value = "";
            searchPopup.style.display = 'none';
        }
        if(element.contains(event.target)){
            if(keyword.length < 1){
                searchPopup.style.display = 'none';
            }else{
                searchPopup.style.display = 'block';
            }
        }
    });
}