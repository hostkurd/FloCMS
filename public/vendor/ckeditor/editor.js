function CreateEditor(Selector, minHeight=300, lang='en'){
    return ClassicEditor
    .create( document.querySelector( Selector ), {
        extraPlugins: [ FloUploadPlugin], // Loads our Plugin
        mediaEmbed: {previewsInData:true}, // For viewing vdeo after Post
        toolbar: {
            items: [
                'heading',
                '|',
                'bold',
                'italic',
                'underline',
                '|',
                'fontColor',
                'fontSize',
                '|',
                'alignment',
                '|',
                'link',
                'bulletedList',
                'numberedList',
                // '|',
                // 'outdent',
                // 'indent',
                '|',
                // 'imageUpload',
                'imageInsert',
                'mediaEmbed',
                'blockQuote',
                'insertTable',
                '|',
                'undo',
                'redo',
                '|',
                'removeFormat',
            ]
        },
        language: lang,
        image: {
            toolbar: [
                'imageTextAlternative',
                '|',
                // 'imageStyle:inline',
                // 'imageStyle:block',
                // 'imageStyle:side',
                'imageStyle:alignBlockLeft',
                'imageStyle:alignCenter',
                'imageStyle:alignBlockRight',
                '|',
                'imageStyle:alignLeft', 
                'imageStyle:alignRight',
                '|',
                'toggleImageCaption'
            ]
        },
        table: {
            contentToolbar: [
                'tableColumn',
                'tableRow',
                'mergeTableCells'
            ]
        } 
        } )
    .then( editor => {
        editor.editing.view.change( writer => {
            writer.setStyle( 'min-height', minHeight+'px', editor.editing.view.document.getRoot() );
        } );
        window.editor = editor;
    } )
    .catch( error => {
        console.error( 'Oops, something went wrong!' );
        console.error( 'Please, report the following error on https://github.com/ckeditor/ckeditor5/issues with the build id and the error stack trace:' );
        console.warn( 'Build id: op16kkqjhxg1-2p8iunlsf07w' );
        console.error( error );
} );

}