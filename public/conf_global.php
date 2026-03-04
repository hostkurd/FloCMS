<?php
// Global Configuration File
// Please Edit this file before installing the Script
// Copyright c2024 HostKurd Company

use HostKurd\Flocms\Lib\App;
use HostKurd\Flocms\Lib\Config;

// Get the ONE router used by the framework (created in App::Run())
$router = App::getRouter();

Define('CURRENT_CONTROLLER',$router->getController());
Define('CURRENT_ACTION',$router->getAction());
Define('IS_INDEX_PAGE',(CURRENT_CONTROLLER == 'pages' && CURRENT_ACTION=='index' ? true : false));

/**
 * Important!
 * Do not Edit the Following Settings
 */
Define('VIEWS_PATH',ROOT.DS.'views');
Define('assetPath', SITE_URI.'/assets');
Define('cssPath', SITE_URI.'/assets/css');
Define('jsPath', SITE_URI.'/assets/js');
Define('imgPath', SITE_URI.'/assets/img');
Define('fontPath', SITE_URI.'/assets/fonts');
Define('vendorPath', SITE_URI.'/vendor');
Define('iconPath', imgPath.'/icons');

Define('formsJs',jsPath.'/admin/forms.js');

// Editor Path
Define('editorPath', SITE_URI.'/vendor/ckeditor/build/ckeditor.js');
Define('uploadAdapterPath', SITE_URI.'/vendor/ckeditor/uploadadapter.js');
Define('editorConfig', SITE_URI.'/vendor/ckeditor/editor.js');

// Helpers
Define('uploadHelper', jsPath.'/admin/helpers/uploadHelper.js');
Define('multiUploadHelper', jsPath.'/admin/helpers/multiUploadHelper.js');
Define('layoutHelper', jsPath.'/admin/helpers/layoutHelper.js');

Define('ACTIVE_LANG',$router->getLanguage());
Define('EnglishPath',$router->changeLang('en'));

Define('LANG_SUFFIX', ACTIVE_LANG != 'en'?'_'.ACTIVE_LANG:'');

//Active Language Definition
IF ($router->getLanguage() == Config::get('default_language')){
    Define('ACTIVE_LANG_PATH','');
}else{
    Define('ACTIVE_LANG_PATH',ACTIVE_LANG.'/');
}

// Check if is Admin or not
if ($router->getMethodPrefix() =='admin_'){
    Define('IS_ADMIN',true);
}else{
    Define('IS_ADMIN',false);
}

