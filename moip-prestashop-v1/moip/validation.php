<?php

/* SSL Management */

$useSSL = true;
include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/moip.php');
include(dirname(__FILE__) . '/moipapi.php');
include(dirname(__FILE__) . '/log.php');

extract($_GET);
extract($_POST);

$moip = new Moip();
$moipApi = new moipapi();
$cart = new Cart(intval($id_cart));

$log = new log(Configuration::get('MOIP_LOG_ACTIVE'));
$log->setLogDir(Configuration::get('MOIP_NASP_KEY'));


if ($type == 'log')
   $log->write("Falha na transação", $_POST);


if ($type == 'redirect') {
    if ($paymentForm == "CartaoCredito" && $paymentStatus != "Cancelado") {

        $paymentStatus = $moip->getStatusMoipArray($paymentStatus);

        $moip->validateOrder($cart->id, $paymentStatus['configId'], $paymentValue, $moip->getL('adminMessage', $_POST));
        $order = new Order($moip->currentOrder);

        $log->write("Order id [CartaoCredito]: " . $moip->currentOrder . "\nStatus: " . $paymentStatus['configId']);
    } else if ($paymentForm == "DebitoBancario" || $paymentForm == "BoletoBancario") {

        $paymentStatus = Configuration::get('MoIP_STATUS_1');

        $moip->validateOrder($cart->id, $paymentStatus, $paymentValue, $moip->getL('adminMessage', $_POST));
        $order = new Order($moip->currentOrder);

        $log->write("Order id [DebitoBancario ou BoletoBancario]: " . $moip->currentOrder . "\nStatus: " . $paymentStatus);
    }

    $addOrderTransaction = $moip->addOrder($_POST);
    $log->write("Foi Adicionado: " . $addOrderTransaction);

    $person_info = array(
        "redirect" => "modules/moip/payment.php?id_order=" . $moip->currentOrder . "&key=" . $order->secure_key,
        "classification" => $paymentClassification
    );

    $json = json_encode($person_info);
    //$json = json_encode('{"erro":"1", "text": "Ocorreu um erro inesperado"}');

    echo($json);
    exit;
}

include(dirname(__FILE__).'/../../header.php');
if (!$cookie->isLogged())
    Tools::redirect('authentication.php?back=order.php');

$clearToCart = $moipApi->getKeyToolsMoip(Configuration::get('MOIP_LOGIN'));

if($clear == $clearToCart){

    $invoiceAddress = new Address(intval($cart->id_address_invoice));
    $customer = array('customerName' => $invoiceAddress->firstname . " " . $invoiceAddress->lastname);

    $moip->validateOrder($cart->id, false, $paymentValue, $moip->getL('adminMessageFail', $customer));
    $order = new Order($moip->currentOrder);


    $log->write("Zerando carrinho: Order[" . $moip->currentOrder . "]");

    $moipOrder = $moip->getOrder($moip->getUniqueMoipId());
    if($moipOrder['token_transaction'])
       $tokenTransactionOrder = $moipOrder['token_transaction'];
    else
       $tokenTransactionOrder = 'TOKEN_NOT_DEFINED';

$total = (float)($cart->getOrderTotal(true, Cart::BOTH));
    $postVars = array('idTransaction' => $moip->getUniqueMoipId(),
        'tokenTransaction' => $tokenTransactionOrder,
        'paymentURL' => null,
        'paymentForm' => 'Carrinho zerado',
        'paymentFormInstitution' => 'PrestaShop',
        'paymentCode' => null,
        'paymentStatus' => 5,
        'paymentValue' =>$total);

    $addOrderTransaction = $moip->addOrder($postVars);
    $log->write("update[" . $addOrderTransaction ."]");

    Tools::redirect('authentication.php?back=order.php');
    exit;
}else{

    if($_POST){
        $params = $_POST;
    }else if($_GET){
        $params = $_GET;        
    }else{
        $params = null;        
    }

    $log->write("Acesso inesperado", $params);
    Tools::redirect('authentication.php?back=order.php');
    exit;

}

include_once(dirname(__FILE__).'/../../footer.php');
?>