<?php

/* SSL Management */
$useSSL = true;

include(dirname(__FILE__) . '/../../config/config.inc.php');
#include(dirname(__FILE__) . '/../../header.php');
include(dirname(__FILE__) . '/moip.php');
include(dirname(__FILE__) . '/log.php');
#include(dirname(__FILE__) . '/payment_return.tpl');

 // hides left column

	$controller = new FrontController();
	$controller->init();
	$controller->setMedia();	

Tools::displayFileAsDeprecated();
$controller->displayHeader();

if (!$cookie->isLogged())
    Tools::redirect('authentication.php?back=order.php');

$moip = new Moip();
$log = new log(Configuration::get('MOIP_LOG_ACTIVE'));
$log->setLogDir(Configuration::get('MOIP_NASP_KEY'));

extract($_GET);

$orderDataFromMoip = $moip->getOrderFromMoip($id_order);

if ($key == $orderDataFromMoip['secure_key']) {

    $moipPaymentData = $moip->getOrderData($id_order);

    $log->write("Exibindo pagamento [order]: " . $id_order);

    echo($moip->hookPaymentReturn($moipPaymentData));

} else {
    $log->write("Secure-Key invalido [order]: " . $id_order);
    Tools::redirectLink(__PS_BASE_URI__ . 'history.php');
}

include_once(dirname(__FILE__) . '/../../footer.php');
?>