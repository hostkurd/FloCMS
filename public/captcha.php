<?php

require dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/session.php';

use FloCMS\Captcha\CaptchaManager;

// Same session as the site (includes/session.php), so the form sees the code
flo_session_start();

$captcha = new CaptchaManager();
$captcha->output();