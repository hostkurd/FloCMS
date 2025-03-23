function populateMenu(options){
    const tableRows = document.querySelectorAll('[data="has-menu"]');
    const contextMenu = document.querySelector('.context-menu');

    for (let i = 0; i < tableRows.length; i++) {
        tableRows[i].addEventListener("contextmenu", e =>{
            
            e.preventDefault();
            e.stopPropagation();
            
            let x = e.pageX, y = e.pageY;
            let targetId = e.target.closest("tr").dataset.id;

            let winWidth = window.innerWidth,
            cmWidth = contextMenu.offsetWidth + 40,
            winHeight = window.innerHeight,
            cmHeight = contextMenu.offsetHeight;

            x = x > winWidth - cmWidth ? winWidth - cmWidth : x;
            //y = y > winHeight - cmHeight ? winHeight - cmHeight : y;

            contextMenu.style.left = `${x}px`;
            contextMenu.style.top = `${y}px`;

            contextMenu.style.visibility = 'visible';

            if(options.addLink){
                const editMenu = document.getElementById('add-menu');
                editMenu.addEventListener('click', function(){
                    menuLink({baseUrl: options.addLink,
                    });
                });
            }

            if(options.editLink){
                const editMenu = document.getElementById('edit-menu');
                editMenu.addEventListener('click', function(){
                    menuLink({baseUrl: options.editLink,
                        id: targetId
                    });
                });
            }
            if(options.copyLink){
                const copyMenu = document.getElementById('copy-menu');
                copyMenu.addEventListener('click', function(){
                    menuLink({baseUrl: options.copyLink,
                        id: targetId
                    });
                });
            }  
            if(options.deleteLink){
                const deleteMenu = document.getElementById('delete-menu');
                deleteMenu.addEventListener('click', function(){
                    ConfirmDelete(options.deleteLink, targetId ,options.delAction);
                });
            }
            if(options.viewLink){
                const viewMenu = document.getElementById('view-menu');
                viewMenu.addEventListener('click', function(){
                    menuLink({baseUrl: options.copyLink,
                        id: targetId
                    });
                });
            }
            if(options.reloadLink){
                const reloadMenu = document.getElementById('reload-menu');
                reloadMenu.addEventListener('click', function(){
                    if(options.reloadLink == 'self'){
                        location.reload();
                    }else{
                        menuLink({baseUrl: options.reloadLink});
                    }
                });
            }

        });
    }
    // Hide Context Menu
    document.addEventListener("click",()=>{contextMenu.style.visibility = 'hidden';});
}





function menuLink(options){
    const waiterDiv = document.querySelector('.waiter');
    waiterDiv.style.visibility = 'visible';
    let url = site_url + options.baseUrl;
    
    if (options.id){
        url = site_url + options.baseUrl + '/' + options.id;  
    }
    window.location.href = url;  
}