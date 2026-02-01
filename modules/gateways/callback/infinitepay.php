<?php
/**
 * InfinitePay Callback para WHMCS
 * Recebe notificação, verifica autenticidade na API e dá baixa.
 */

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

$gatewayModuleName = 'infinitepay';
$gatewayParams = getGatewayVariables($gatewayModuleName);

if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// 1. Recebe o JSON do Webhook
$jsonPayload = file_get_contents('php://input');
$data = json_decode($jsonPayload);

// Log inicial (opcional, bom para debug)
// logTransaction($gatewayParams['name'], $jsonPayload, 'Webhook Received');

// Validação básica
if (!isset($data->order_nsu) || !isset($data->transaction_nsu)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid Data']);
    exit;
}

$invoiceId = $data->order_nsu; // ID da Fatura
$transactionId = $data->transaction_nsu;
$slug = $data->invoice_slug; // Necessário para a conferência
$handle = $gatewayParams['infiniteHandle'];

// Verifica se fatura existe e se já não foi paga
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);
checkCbTransID($transactionId);

// ---------------------------------------------------------
// SEGURANÇA: Conferência Dupla (Payment Check)
// A documentação recomenda verificar o status via API para garantir.
// Endpoint: POST https://api.infinitepay.io/invoices/public/checkout/payment_check
// ---------------------------------------------------------

$checkPayload = array(
    "handle" => $handle,
    "order_nsu" => (string) $invoiceId,
    "transaction_nsu" => $transactionId,
    "slug" => $slug
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.infinitepay.io/invoices/public/checkout/payment_check");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($checkPayload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$checkData = json_decode($response);

// Verifica se a API confirmou que está "paid": true
if (isset($checkData->paid) && $checkData->paid === true) {
    
    // Valor pago em reais (a API devolve em centavos no paid_amount? 
    // A doc diz "paid_amount": 1510 para R$ 15,10. Então dividimos por 100)
    $paidAmount = $checkData->paid_amount / 100;

    // Aplica o Pagamento no WHMCS
    addInvoicePayment(
        $invoiceId,
        $transactionId,
        $paidAmount,
        0, // Taxas (a API não informa a taxa explicitamente no check, deixamos 0)
        $gatewayModuleName
    );
    
    logTransaction($gatewayParams['name'], $jsonPayload, 'Successful Payment');
    
    // Resposta obrigatória 200 OK
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => null]);

} else {
    // Pagamento não confirmado pela API de checagem
    logTransaction($gatewayParams['name'], $response, 'Validation Failed');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payment validation failed']);
}
?>