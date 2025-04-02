<?php
// Global Configuration File
// Please Edit this file before installing the Script
// Copyright c2024 HostKurd Company

Define('SITE_URI','http://localhost/flocms');
Define('API_URI','https://flocms.com/api/');

Define('DS',DIRECTORY_SEPARATOR);
Define('ROOT',dirname(dirname(__FILE__)));

Define('VIEWS_PATH',ROOT.DS.'views');
//Define('SITE_URI','https://shanashil.co');

// Main Directories
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