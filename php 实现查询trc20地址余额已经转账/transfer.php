<?php


//购买能量配置
$energyArray = [
    'trx_1' => [
        'nengliang_address' => 'TYHJUgkSKZP1g7fmLQYFsRu477bz87tNQW',
        'nengliang_trx' => 1
    ],
    'trx_1_3' => [
        'nengliang_address' => 'TDTjZhZhEDkC3PyCbf13JqT9bQaLCj7Y7j',
        'nengliang_trx' => 1.3
    ]
];




function isTrc20Address($address) {
    return preg_match('/^T[a-zA-Z1-9]{33}$/', $address) > 0;
}


$data = json_decode(file_get_contents('php://input'), true);
$to_address = $data['address'];
$to_amount = $data['amount'];
$energyOption = $data['energyOption'];

$to_address = trim($to_address);
$to_amount = trim($to_amount);
$energyOption = trim($energyOption);


if (!isTrc20Address($to_address)) {
	echo $to_address." 不是一条有效的TRC20地址";
	exit();
}


include_once '../vendor/autoload.php';

$fullNode = new \IEXBase\TronAPI\Provider\HttpProvider('https://api.trongrid.io');
$solidityNode = new \IEXBase\TronAPI\Provider\HttpProvider('https://api.trongrid.io');
$eventServer = new \IEXBase\TronAPI\Provider\HttpProvider('https://api.trongrid.io');

try {
    $tron = new \IEXBase\TronAPI\Tron($fullNode, $solidityNode, $eventServer);
} catch (\IEXBase\TronAPI\Exception\TronException $e) {
    exit($e->getMessage());
}

//购买能量平台获取余额
function get_nengliang_yue($address) {
    try {
        $url = "https://apilist.tronscanapi.com/api/accountv2?address={$address}";
        $response = file_get_contents($url);

        if ($response !== false) {
            $data = json_decode($response, true);
            if (isset($data['bandwidth']['energyRemaining'])) {
                $energy_remaining = $data['bandwidth']['energyRemaining'];
            } else {
                echo 'Failed to retrieve energy data';
                $energy_remaining = null;
            }
        } else {
            echo 'Failed to retrieve energy data';
            $energy_remaining = null;
        }
        return $energy_remaining;
    } catch (Exception $e) {
        echo "获取能量余额时发生错误：{$e->getMessage()}";
        return null;
    }
}


$nengliang_address = $energyArray[$energyOption]['nengliang_address'];
$nengliang_trx = $energyArray[$energyOption]['nengliang_trx'];


//获取接收方地址USDT余额
$tron->setAddress($to_address);
$contract = $tron->contract('TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'); // Tether USDT，这里不能修改
$to_usdt_balance = $contract->balanceOf();
echo "接收方USDT: ".$to_usdt_balance."<br>";



// 获取自己USDT余额 和 TRX余额
$address = '自己的USDT地址';
$my_key = '自己USDT地址密钥';


$tron->setAddress($address);
$tron->setPrivateKey($my_key);
$contract = $tron->contract('TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'); // Tether USDT，这里不能修改
$usdt_balance = $contract->balanceOf();
$trx_balance = $tron->getBalance(null, true);


echo "USDT: ".$usdt_balance."<br>"."TRX:".$trx_balance."<br>";


if ($usdt_balance < $to_amount) {
	echo "当前USDT余额为：".$usdt_balance." ，不足以进行转账，请先充值"."<br>";
	exit(1);
}

if ($trx_balance < 10) {
	echo "当前TRX余额为：".$trx_balance." ，不足以进行转账，请先充值"."<br>";
	exit(1);
}


$energy = get_nengliang_yue($address);
echo "当前能量: ".$energy."<br>";


$nengliang_amount = $nengliang_trx;
if ($to_usdt_balance < 1) {
	echo "对方地址U太少，需要双份能量"."<br>";
	$nengliang_amount = $nengliang_amount * 2;	
}

//判断能量是否购买成功
if ($to_usdt_balance < 1) {
	if ($energy < 64000) {
		echo "能量不足，正在进行购买能量！"."<br>";
		$result = $tron->sendTrx($nengliang_address, $nengliang_amount);

		//print_r(result)
		if ($result['result'] != 1) {
			echo "能量支付失败"."<br>";
			exit(1);
		} else {
			echo "能量支付成功"."<br>";
		}

		sleep(5);
	}
} else {
	if ($energy < 31000) {
		echo "能量不足，正在进行购买能量！"."<br>";
		$result = $tron->sendTrx($nengliang_address, $nengliang_amount);

		//print_r(result)
		if ($result['result'] != 1) {
			echo "能量支付失败"."<br>";
			exit(1);
		} else {
			echo "能量支付成功"."<br>";
		}

		sleep(5);
	}
}



$energy = get_nengliang_yue($address);
echo "能量: ".$energy."<br>";

//判断能量是否购买成功
if ($to_usdt_balance < 1) {
	if ($energy < 64000) {
		echo "能量为 ".$energy." ，可能是前面购买能量失败，请重新进行"."<br>";
		exit(1);
	}
} else {
	if ($energy < 31000) {
		echo "能量为 ".$energy." ，可能是前面购买能量失败，请重新进行"."<br>";
		exit(1);
	}
}

$result = $contract->transfer($to_address, $to_amount);
if ($result['result'] == 1) {
	echo "转账成功"."<br>";
} else {
	echo "转账失败"."<br>";
}


