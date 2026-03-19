<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use FloCMS\Captcha\CaptchaManager;

$captcha = new CaptchaManager();
$captcha->output();