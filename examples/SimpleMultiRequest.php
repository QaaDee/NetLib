<?php

require_once '../vendor/autoload.php';

$multiRequest = new \QaaDee\NetLib\MultiRequest();

$multiRequest->setCallbackRequestEnd(function (\QaaDee\NetLib\Request $request, \QaaDee\NetLib\Response $response) {
    $stdObject = $response->getFormatResponse('json');

    echo $stdObject->success . PHP_EOL;
});

for ($i = 0; $i < 5; $i++) {
    $multiRequest->addRequest(
        new \QaaDee\NetLib\Request('GET', 'https://reqbin.com/echo/get/json')
    );
}

$multiRequest->execute();

?>