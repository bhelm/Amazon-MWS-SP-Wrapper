<?php
if(isset($_POST['Action'])) {
    $action = $_POST['Action'];
    $action = basename($action);
    if(is_file($action.'.php')) {
        $_SERVER['REQUEST_URI'] = '/?'.http_build_query($_POST);
        include ($action.'.php');
    }
}
