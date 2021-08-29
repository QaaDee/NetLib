<?php

require_once '../vendor/autoload.php';


$request = new \QaaDee\NetLib\Request('GET', 'https://reqbin.com/echo/get/json');

$response = $request->execute();


$stdObject = $response->getFormatResponse('json');

echo $stdObject->success;

?>