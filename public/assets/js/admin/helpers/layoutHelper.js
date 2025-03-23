class LayoutHelper{
    constructor(options){

        this.translatableClass = document.querySelectorAll('[translatable]');
        this.ckEditor = options?options.ckEditor:null;
        // {ckEditor:'#content'}

        // Creating Required Styles
        this.createStyles();
    }

    changeDirection(e){
        e.style = 'color: white;background: #00a4e5;box-shadow: rgb(79 118 127 / 72%) 0px 0px 10px inset;';

        if(!e.getAttribute('data-rtl')){

            for (var i = 0; i < this.translatableClass.length; i++ ) {
                this.translatableClass[i].style.direction = 'rtl';
                this.translatableClass[i].setAttribute('dir','rtl');
                this.translatableClass[i].style.fontFamily = 'HostKurdWeb';
            }

            if(this.ckEditor){
                // Destroy old ckeditor
                document.querySelector('.ck-editor__editable').ckeditorInstance.destroy();
                // create new ckeditor
                CreateEditor(this.ckEditor,320,'ku');
            }

            this.styleSelectInputs('left');
            e.setAttribute('data-rtl','true');

            
        }else{

            for (var i = 0; i < this.translatableClass.length; i++ ) {
                this.translatableClass[i].removeAttribute('style');
                this.translatableClass[i].removeAttribute('dir');
            }

            if(this.ckEditor){
                // Destroy old ckeditor
                document.querySelector('.ck-editor__editable').ckeditorInstance.destroy();
                // create new ckeditor
                CreateEditor(this.ckEditor,320,'en');
            }

            e.removeAttribute('data-rtl');
            e.removeAttribute('style');
            this.styleSelectInputs('right');
        }
    }

    styleSelectInputs(align){
        var selects = document.querySelectorAll('select');
        if(selects.length>0){
            for(var i=0; i < selects.length; i++){
                selects[i].style.backgroundPosition  = align+' 1rem center';
           }
        }
    }

    createStyles(){
        var style = document.createElement('style');
        style.textContent = `[translatable][dir="rtl"] .me-2 {margin-left: 0.5rem !important;margin-right:unset !important;}`;
        document.head.appendChild(style);
    }

}