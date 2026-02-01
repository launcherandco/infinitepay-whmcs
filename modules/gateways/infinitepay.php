<?php
/**
 * Módulo InfinitePay API para WHMCS
 * Desenvolvido por Launcher & Co.
 * launcher.com.br - licencas.digital
 * Baseado na documentação oficial de Checkout v2
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function infinitepay_MetaData()
{
    return array(
        'DisplayName' => 'InfinitePay API (Pix/Cartão)',
        'APIVersion' => '1.1',
        'Description' => 'Integração via API Checkout com baixa automática via Webhook.',
    );
}

function infinitepay_config()
{
    // Monta a URL do Webhook automaticamente para exibir ao usuário
    $systemUrl = \WHMCS\Config\Setting::getValue('SystemURL');
    $webhookUrl = rtrim($systemUrl, '/') . '/modules/gateways/callback/infinitepay.php';

    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'InfinitePay API',
        ),
        'infiniteHandle' => array(
            'FriendlyName' => 'Sua InfiniteTag (Handle)',
            'Type' => 'text',
            'Size' => '30',
            'Description' => 'Seu usuário no app InfinitePay (sem o @ ou $). Ex: minhaloja',
        ),
        'instructions' => array(
            'FriendlyName' => 'Instruções',
            'Type' => 'textarea',
            'Rows' => '3',
            'Default' => 'Clique no botão abaixo para pagar com segurança via InfinitePay.',
        ),
        // Exibe a URL do Webhook apenas como informação
        'webhookInfo' => array(
            'FriendlyName' => 'Webhook URL (Automático)',
            'Type' => 'text',
            'Description' => '
                <script>jQuery("input[name=\'field[webhookInfo]\']").hide();</script>
                <div class="alert alert-info" style="margin: 5px 0;">
                    O módulo enviará automaticamente esta URL para a InfinitePay:<br>
                    <strong>' . $webhookUrl . '</strong>
                </div>',
        ),
    );
}

function infinitepay_link($params)
{
    // 1. Dados Básicos
    $handle = preg_replace('/[^a-zA-Z0-9_]/', '', $params['infiniteHandle']); // Remove caracteres especiais
    $invoiceId = $params['invoiceid'];
    
    // Valor em centavos (Regra da documentação: R$ 10,00 = 1000)
    $amountCents = (int) (round($params['amount'], 2) * 100);

    // URLs
    $systemUrl = $params['systemurl'];
    $returnUrl = $systemUrl . 'viewinvoice.php?id=' . $invoiceId;
    $webhookUrl = $systemUrl . 'modules/gateways/callback/infinitepay.php';

    // 2. Dados do Cliente (Opcional, mas recomendado na doc)
    $clientName = $params['clientdetails']['firstname'] . ' ' . $params['clientdetails']['lastname'];
    $clientEmail = $params['clientdetails']['email'];
    $clientPhone = str_replace([' ', '-', '(', ')'], '', $params['clientdetails']['phonenumber']);
    
    // Formata telefone para +55... se necessário
    if (substr($clientPhone, 0, 1) != '+') {
        $clientPhone = '+55' . $clientPhone;
    }

    // 3. Monta o Payload JSON
    // Endpoint: https://api.infinitepay.io/invoices/public/checkout/links
    $payload = array(
        "handle" => $handle,
        "redirect_url" => $returnUrl,
        "webhook_url" => $webhookUrl,
        "order_nsu" => (string) $invoiceId, // O ID da fatura será nosso rastreador
        "items" => array(
            array(
                "quantity" => 1,
                "price" => $amountCents,
                "description" => "Fatura #" . $invoiceId
            )
        ),
        "customer" => array(
            "name" => $clientName,
            "email" => $clientEmail,
            "phone_number" => $clientPhone
        )
    );

    // 4. Envia para a API da InfinitePay
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.infinitepay.io/invoices/public/checkout/links");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $jsonResponse = json_decode($response);

    // 5. Trata a resposta
    if ($httpCode == 201 || ($httpCode == 200 && isset($jsonResponse->url))) {
        $checkoutUrl = $jsonResponse->url;
        
        return '
        <div style="text-align:center;">
            <p>' . nl2br($params['instructions']) . '</p>
            <a href="' . $checkoutUrl . '" class="btn btn-success btn-lg" target="_blank">
                Pagar Agora com InfinitePay
            </a>
            <br><br>
            <small>Pix ou Cartão de Crédito</small>
        </div>';
    } else {
        // Loga o erro para debug
        logTransaction($params['name'], $response, 'Error Generating Link');
        return '<div class="alert alert-danger">Erro ao gerar link InfinitePay. Tente novamente mais tarde.</div>';
    }
}
?>