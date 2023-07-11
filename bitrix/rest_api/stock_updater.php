<?php
set_time_limit(0);

use Store;
use StoreFactory;
use Auth;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

$params  = require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

if ($request->isPost() && (new Auth($request, [$params['user'], $params['password']]))->isAuthorized()) {
    try {
        $data   = parseDataFromRequest($request);
        $errors = [];

        foreach ($data as $stock) {
            $store = StoreFactory::makeStore($stock->uuid); // получаем искомый, либо дефолтный склад
            foreach ($stock['stocks'] as $key => $value) {
                try {
                    $store->setQuantity($value['uuid'], $value['quantity']); // устанавливаем остатки
                } catch (\Throwable $th) {
                    array_push($errors, $th->getMessage());
                }
            }
        }

        if (count($errors) > 0) {
            return new \Bitrix\Main\Engine\Response\Json([
                'success' => "false",
                'errors'  => $errors
            ]);
        } else {
            return new \Bitrix\Main\Engine\Response\Json([
                'success' => "true"
            ]);
        }
    } catch (\Throwable $th) {
        $error = $th->getMessage();
        return new \Bitrix\Main\Engine\Response\Json([
            'success' => "false",
            'errors'  => [$error]
        ]);
    }
}

function parseDataFromRequest(Request $request): array {
    return [];
}