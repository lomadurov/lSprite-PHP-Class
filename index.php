<?php
require('sprite_lib.php');

$config = array(
    'folder' => 'imgs/',
    'prefix' => 'ico_',
    'demo' => TRUE,
    'scroll' => 0
);
$sprite = new sprite($config);
$sprite->generate('sprite_'.time(),'output/');