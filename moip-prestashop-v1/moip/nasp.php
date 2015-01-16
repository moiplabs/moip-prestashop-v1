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


if ($key == Configuration::get('MOIP_NASP_KEY')) {
$id_transacao = substr($id_transacao, strpos($id_transacao, "_") + 1);
    $valor = $moip->getValueMoip(false, $valor);
    $postParams = array('idTransaction' => $id_transacao,
        'paymentCode' => $cod_moip,
        'paymentStatus' => $status_pagamento,
        'paymentValue' => $valor,
        'paymentForm' => $tipo_pagamento,
        'type' => 'update');

    $idOrder = $moip->getOrder($id_transacao);
    extract($idOrder);
    $orderUpdateMoip = $moip->addOrder($postParams);
    $log->write("GET Order update[" . $orderUpdateMoip . "]", $id_order);

    if ($id_order > 0) {
        $order = new Order(intval($id_order));
        if($order->current_state < 2 || $order->current_state > 5)
        {
            $moip->newHistory($id_transacao, $status_pagamento);
            $log->write('NASP Accepted');
        }
        header("HTTP/1.0 202 Accepted");
        echo "Accepted";
    } else {
        $log->write('Transaction not found');
        
        header("HTTP/1.0 404 Not Found");
        echo "Transaction not found";        
    }

    exit;
} else {

    if ($_POST) {
        $params = $_POST;
    } else if ($_GET) {
        $params = $_GET;
    } else {
        $params = null;
    }

    $log->write("Acesso inesperado", $params);

    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    header("HTTP/1.0 301 Moved Permanently");
    header("Location: ../");
    exit;
}
?>