<?php
session_start();
    function acakCaptcha()
    {
        $alphabet = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

    //untuk menyatakan $pass sebagai array
    $pass = array();

    //masukkan -2 dalam string length
    $panjangAlpha = strlen($alphabet) - 2;
    for ($i = 0; $i < 5; $i++) {
        $n = rand(0, $panjangAlpha);
        $pass[] = $alphabet[$n];
    }

    //rubah array menjadi string
    return implode($pass);
}

//untuk mengacak captcha
$code = acakCaptcha();
$_SESSION["code"] = $code;

//lebar dan tinggi captcha
$wh = imagecreatetruecolor(173, 50);

//background color biru
$bgc = imagecolorallocate($wh, 22, 86, 165);

//text color abu-abu
$fc = imagecolorallocate($wh, 223, 230, 233);

imagefill($wh, 0, 0, $bgc);

//( $image , $fontsize , $string , $fontcolor )
imagestring($wh, 12, 50, 15, $code, $fc);

header('content-type: image/jpg');
imagejpeg($wh);
imagedestroy($wh);
