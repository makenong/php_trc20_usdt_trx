<?php


include_once '../vendor/autoload.php';

$fullNode = new \IEXBase\TronAPI\Provider\HttpProvider('https://api.trongrid.io');
$solidityNode = new \IEXBase\TronAPI\Provider\HttpProvider('https://api.trongrid.io');
$eventServer = new \IEXBase\TronAPI\Provider\HttpProvider('https://api.trongrid.io');

try {
    $tron = new \IEXBase\TronAPI\Tron($fullNode, $solidityNode, $eventServer);
} catch (\IEXBase\TronAPI\Exception\TronException $e) {
    exit($e->getMessage());
}


$address = '自己的USDT地址';

$tron->setAddress($address);
$contract = $tron->contract('TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'); // Tether USDT，这里不能修改
$usdt_balance = $contract->balanceOf();
$trx_balance = $tron->getBalance(null, true);


header('Content-Type: application/json');

$response = [
	'my_address' => $address,
	'usdt' => $usdt_balance,
	'trx' => $trx_balance,
];

echo json_encode($response);

