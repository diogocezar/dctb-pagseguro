<?php

class PagSeguroConfigWrapper {
    public static function getConfig(){
        $PagSeguroConfig = array();
        $PagSeguroConfig['environment'] = Configs::$configs['pagseguro']['environment'];
        $PagSeguroConfig['credentials'] = array();
        $PagSeguroConfig['credentials']['email']                = Configs::$configs['pagseguro']['email'];
        $PagSeguroConfig['credentials']['token']['production']  = Configs::$configs['pagseguro']['token'];
        $PagSeguroConfig['credentials']['token']['sandbox']     = Configs::$configs['pagseguro']['token'];
        $PagSeguroConfig['credentials']['appId']['production']  = Configs::$configs['pagseguro']['appId'];
        $PagSeguroConfig['credentials']['appId']['sandbox']     = Configs::$configs['pagseguro']['appId'];
        $PagSeguroConfig['credentials']['appKey']['production'] = Configs::$configs['pagseguro']['appKey'];
        $PagSeguroConfig['credentials']['appKey']['sandbox']    = Configs::$configs['pagseguro']['appKey'];
        $PagSeguroConfig['application'] = array();
        $PagSeguroConfig['application']['charset'] = Configs::$configs['pagseguro']['charset'];
        $PagSeguroConfig['log'] = array();
        $PagSeguroConfig['log']['active']       = false;
        $PagSeguroConfig['log']['fileLocation'] = Configs::$configs['pagseguro']['logs'];
        return $PagSeguroConfig;
    }
}

class CreatePaymentRequest {

    public function main(){
        // Instantiate a new payment request
        $paymentRequest = new PagSeguroPaymentRequest();

        // Set the currency
        $paymentRequest->setCurrency("BRL");

        // Add an item for this payment request
        $paymentRequest->addItem('0001', 'Notebook prata', 2, 430.00);

        // Add another item for this payment request
        $paymentRequest->addItem('0002', 'Notebook rosa', 2, 560.00);

        // Set a reference code for this payment request. It is useful to identify this payment
        // in future notifications.
        $paymentRequest->setReference("REF123");

        // Set shipping information for this payment request
        $sedexCode = PagSeguroShippingType::getCodeByType('SEDEX');
        $paymentRequest->setShippingType($sedexCode);
        $paymentRequest->setShippingAddress(
            '01452002',
            'Av. Brig. Faria Lima',
            '1384',
            'apto. 114',
            'Jardim Paulistano',
            'São Paulo',
            'SP',
            'BRA'
        );

        // Set your customer information.
        $paymentRequest->setSender(
            'João Comprador',
            'email@comprador.com.br',
            '11',
            '56273440',
            'CPF',
            '156.009.442-76'
        );

        // Set the url used by PagSeguro to redirect user after checkout process ends
        $paymentRequest->setRedirectUrl(Configs::$configs['pagseguro']['redirect']);

        // Add checkout metadata information
        $paymentRequest->addMetadata('PASSENGER_CPF', '15600944276', 1);
        $paymentRequest->addMetadata('GAME_NAME', 'DOTA');
        $paymentRequest->addMetadata('PASSENGER_PASSPORT', '23456', 1);

        // Another way to set checkout parameters
        $paymentRequest->addParameter('notificationURL', Configs::$configs['pagseguro']['notification']);
        $paymentRequest->addParameter('senderBornDate', '07/05/1981');
        $paymentRequest->addIndexedParameter('itemId', '0003', 3);
        $paymentRequest->addIndexedParameter('itemDescription', 'Notebook Preto', 3);
        $paymentRequest->addIndexedParameter('itemQuantity', '1', 3);
        $paymentRequest->addIndexedParameter('itemAmount', '200.00', 3);

        // Add discount per payment method
        $paymentRequest->addPaymentMethodConfig('CREDIT_CARD', 1.00, 'DISCOUNT_PERCENT');
        $paymentRequest->addPaymentMethodConfig('EFT', 2.90, 'DISCOUNT_PERCENT');
        $paymentRequest->addPaymentMethodConfig('BOLETO', 10.00, 'DISCOUNT_PERCENT');
        $paymentRequest->addPaymentMethodConfig('DEPOSIT', 3.45, 'DISCOUNT_PERCENT');
        $paymentRequest->addPaymentMethodConfig('BALANCE', 0.01, 'DISCOUNT_PERCENT');

        // Add installment without addition per payment method
        $paymentRequest->addPaymentMethodConfig('CREDIT_CARD', 6, 'MAX_INSTALLMENTS_NO_INTEREST');

        // Add installment limit per payment method
        $paymentRequest->addPaymentMethodConfig('CREDIT_CARD', 8, 'MAX_INSTALLMENTS_LIMIT');

        // Add and remove a group and payment methods
        $paymentRequest->acceptPaymentMethodGroup('CREDIT_CARD', 'DEBITO_ITAU');
        $paymentRequest->excludePaymentMethodGroup('BOLETO', 'BOLETO');

        try {

            /*
             * #### Credentials #####
             * Replace the parameters below with your credentials
             * You can also get your credentials from a config file. See an example:
             * $credentials = new PagSeguroAccountCredentials("vendedor@lojamodelo.com.br",
             * "E231B2C9BCC8474DA2E260B6C8CF60D3");
             */

            // seller authentication
            $credentials = PagSeguroConfig::getAccountCredentials();

            // application authentication
            //$credentials = PagSeguroConfig::getApplicationCredentials();

            //$credentials->setAuthorizationCode("E231B2C9BCC8474DA2E260B6C8CF60D3");

            // Register this payment request in PagSeguro to obtain the payment URL to redirect your customer.
            $url = $paymentRequest->register($credentials);

            self::printPaymentUrl($url);

        } catch (PagSeguroServiceException $e) {
            die($e->getMessage());
        }
    }

    public static function printPaymentUrl($url)
    {
        if ($url) {
            echo "<h2>Criando requisi&ccedil;&atilde;o de pagamento</h2>";
            echo "<p>URL do pagamento: <strong>$url</strong></p>";
            echo "<p><a title=\"URL do pagamento\" href=\"$url\">Ir para URL do pagamento.</a></p>";
        }
    }
}

class NotificationListener {
    public static function main(){
        $code = (isset($_POST['notificationCode']) && trim($_POST['notificationCode']) !== "" ?
            trim($_POST['notificationCode']) : null);
        $type = (isset($_POST['notificationType']) && trim($_POST['notificationType']) !== "" ?
            trim($_POST['notificationType']) : null);
        if ($code && $type) {
            $notificationType = new PagSeguroNotificationType($type);
            $strType = $notificationType->getTypeFromValue();
            switch ($strType) {
                case 'TRANSACTION':
                    self::transactionNotification($code);
                    break;
                case 'APPLICATION_AUTHORIZATION':
                    self::authorizationNotification($code);
                    break;
                case 'PRE_APPROVAL':
                    self::preApprovalNotification($code);
                    break;
                default:
                    LogPagSeguro::error("Unknown notification type [" . $notificationType->getValue() . "]");
            }
            self::printLog($strType);
        } else {
            LogPagSeguro::error("Invalid notification parameters.");
            self::printLog();
        }

    }

    private static function transactionNotification($notificationCode)    {
        $credentials = PagSeguroConfig::getAccountCredentials();
        try {
            $transaction = PagSeguroNotificationService::checkTransaction($credentials, $notificationCode);
            print_r($transaction);
        } catch (PagSeguroServiceException $e) {
            die($e->getMessage());
        }
    }
    private static function authorizationNotification($notificationCode){
        $credentials = PagSeguroConfig::getApplicationCredentials();
        try {
            $authorization = PagSeguroNotificationService::checkAuthorization($credentials, $notificationCode);
            // Do something with $authorization
        } catch (PagSeguroServiceException $e) {
            die($e->getMessage());
        }
    }

    private static function preApprovalNotification($preApprovalCode){
        $credentials = PagSeguroConfig::getAccountCredentials();
        try {
            $preApproval = PagSeguroNotificationService::checkPreApproval($credentials, $preApprovalCode);
            // Do something with $preApproval
        } catch (PagSeguroServiceException $e) {
            die($e->getMessage());
        }
    }

    private static function printLog($strType = null){
        $count = 4;
        echo "<h2>Receive notifications</h2>";
        if ($strType) {
            echo "<h4>notifcationType: $strType</h4>";
        }
        echo "<p>Last <strong>$count</strong> items in <strong>log file:</strong></p><hr>";
        echo LogPagSeguro::getHtml($count);
    }
}
