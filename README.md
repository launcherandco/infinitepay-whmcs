# Módulo de Pagamento InfinitePay API para WHMCS

Este módulo integra o WHMCS ao Checkout da InfinitePay, permitindo receber pagamentos via Pix e Cartão de Crédito com as melhores taxas do mercado.

O módulo utiliza a API v2 para gerar links dinâmicos e processar o retorno automático (Webhook) sem complicações.

## 🚀 Funcionalidades Principais

* **Checkout Transparente (Redirecionamento):** O cliente é levado para um ambiente seguro da InfinitePay para concluir o pagamento.
* **Baixa Automática (Webhook Dinâmico):** O módulo envia a URL de retorno automaticamente a cada transação. Não é necessário configurar Webhooks manualmente no painel da InfinitePay.
* **Validação de Segurança (Double Check):** Após receber o aviso de pagamento, o módulo consulta a API da InfinitePay novamente para garantir que a transação é legítima e o valor está correto.
* **Rastreamento por NSU:** Utiliza o ID da Fatura como `order_nsu` para conciliação precisa entre WHMCS e InfinitePay.
* **Instruções Personalizáveis:** Campo para adicionar textos de orientação ao cliente antes do clique no botão de pagamento.

## ⚙️ Requisitos

* WHMCS 8.x
* PHP 7.4 ou superior
* **Conta InfinitePay:** É necessário ter uma conta ativa e sua "InfiniteTag" (Handle).
  > [**Clique aqui para conhecer a InfinitePay e criar sua conta**](https://www.infinitepay.io/)
* Certificado SSL (HTTPS) ativo no seu domínio.

## 📋 Instruções

### 1. Instalação dos Arquivos
Este módulo é composto por **dois arquivos**. Faça o upload deles para as respectivas pastas na raiz da sua instalação do WHMCS:

1.  **Arquivo do Módulo (`infinitepay.php`):**
    * Caminho de upload: `/modules/gateways/`
2.  **Arquivo de Retorno/Callback (`infinitepay.php`):**
    * Caminho de upload: `/modules/gateways/callback/`

### 2. Configuração no WHMCS
1.  Acesse o painel administrativo do WHMCS.
2.  Navegue até **Opções (Ícone de Engrenagem) > Pagamentos > Portais de Pagamento**.
3.  Na aba **All Payment Gateways**, localize o módulo **InfinitePay API (Pix/Cartão)** e clique para ativar.
4.  Configure os campos:
    * **Sua InfiniteTag (Handle):** Insira seu nome de usuário da InfinitePay (sem o `@` ou `$`). Exemplo: se seu link é `infinitepay.io/l/minhaloja`, coloque apenas `minhaloja`.
    * **Instruções:** Texto de orientação que aparecerá na fatura.
5.  Clique em **Salvar Alterações**.

### 3. Sobre o Webhook (Automático)
Diferente de outros gateways, **não é necessário** configurar uma URL de Webhook fixa no painel da InfinitePay.

Este módulo utiliza o recurso de `webhook_url` dinâmico da API. A cada cobrança gerada, o WHMCS informa à InfinitePay para onde deve ser enviada a confirmação daquela transação específica.

---

## 💎 Recomendado para seu WHMCS

> **TENHA SEU WHMCS VERIFICADO**
>
> Garanta mais credibilidade e segurança para o seu sistema por apenas **R$ 250,00 anuais**.
>
> [**👉 CLIQUE AQUI PARA CONTRATAR AGORA**](https://licencas.digital/store/whmcs/whmcs-verificado)
