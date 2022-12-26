<?php

/**
 * Базовая конфигурация
 */
// * Апи ключ вашего акканута
$apiKey = 'fb199fa94d7e53aab36d26b6e276a67c';
// * Домен проекта на который происходит отправка заказов
$domain = 'shakes.pro';
// Урл оригинального лендинга, необходим для корректого расчета Вашей статистики
$landingUrl = 'http://dz4.visiolifesale.com';
// * Идентификатор оффера на который вы льете
$offerId = '9513';
// Код потока заведенного в системе, если указан, статистика будет записываться на данный поток
$streamCode = '';
// Страница, отдаваемая при успешном заказе
$successPage = 'success.html';
// Страница, отдаваемая в случае ошибки
$errorPage = 'index.html';
/**
 * Формирование отправляемого заказа
 */
$url = "http://$domain?r=/api/order/in&key=$apiKey";
$order = [
    'countryCode' => (!empty($_POST['country']) ? $_POST['country'] : ($_GET['country'] ? $_GET['country'] : 'RU')),
    'comment' => (!empty($_POST['comment']) ? $_POST['comment'] : ($_GET['comment'] ? $_GET['comment'] : '')),
    'createdAt' => date('Y-m-d H:i:s'),
    'ip' => (!empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null), // ip пользователя
    'landingUrl' => $landingUrl,
    'name' => (!empty($_POST['name']) ? $_POST['name'] : ($_GET['name'] ? $_GET['name'] : '')),
    'offerId' => $offerId,
    'phone' => (!empty($_POST['phone']) ? $_POST['phone'] : ($_GET['phone'] ? $_GET['phone'] : '')),
    'referrer' => (!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null),
    'streamCode' => $streamCode,
    'sub1' => (!empty($_GET['sub1']) ? $_GET['sub1'] : ''),
    'sub2' => (!empty($_GET['sub2']) ? $_GET['sub2'] : ''),
    'sub3' => (!empty($_GET['sub3']) ? $_GET['sub3'] : ''),
    'sub4' => (!empty($_GET['sub4']) ? $_GET['sub4'] : ''),
    'userAgent' => (!empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '-'),
];

/**
 * Отправка заказа
 */
/**
 * @see http://php.net/manual/ru/book.curl.php
 */
$curl = curl_init();
/**
 * @see http://php.net/manual/ru/function.curl-setopt.php
 */
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $order);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_USERAGENT, 'curl/' . (curl_version()['version'] ?? '7'));
/**
 * @see http://php.net/manual/ru/language.exceptions.php
 */
try {
    $responseBody = curl_exec($curl);
    /**
     * Логгируем все ответы сервера.
     * Если заказ не отправляется, данный файл может быть затребован саппортом
     * для диагностики проблемы.
     * @see http://php.net/manual/ru/function.file-put-contents.php
     */
    @file_put_contents(
        __DIR__ . '/shakes.response.log',
        date('Y.m.d H:i:s') . PHP_EOL . $responseBody,
        FILE_APPEND
    );

    // тело оказалось пустым
    if (empty($responseBody)) {
        throw new Exception('Error: Empty response for order. ' . var_export($order, true));
    }
    /**
     * @var StdClass $response
     */
    $response = json_decode($responseBody, true);
    // возможно пришел некорректный формат
    if (empty($response)) {
        throw new Exception('Error: Broken json format for order. ' . PHP_EOL . var_export($order, true));
    }
    // заказ не принят API
    if ($response['status'] !== 'ok') {
        throw new Exception('Success: Order is accepted. '
            . PHP_EOL . 'Order: ' . var_export($order, true)
            . PHP_EOL . 'Response: ' . var_export($response, true)
        );
    }
    /**
     * логируем данные об обработке заказа
     * @see http://php.net/manual/ru/function.file-put-contents.php
     */
    @file_put_contents(
        __DIR__ . '/order.success.log',
        date('Y.m.d H:i:s') . ' ' . $responseBody,
        FILE_APPEND
    );
    curl_close($curl);

    if(!empty($successPage) && is_file(__DIR__ . '/' . $successPage)) {
        include __DIR__ . '/' . $successPage;
    }
} catch (Exception $e) {
    /**
     * логируем ошибку
     * @see http://php.net/manual/ru/function.file-put-contents.php
     */
    @file_put_contents(
        __DIR__ . '/order.error.log',
        date('Y.m.d H:i:s') . ' ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString(),
        FILE_APPEND
    );

    if(!empty($errorPage) && is_file(__DIR__ . '/' . $errorPage)) {
        include __DIR__ . '/' . $errorPage;
    }
}
