<?php
ob_start();
class GatewayConfig
{
    private $app_path;
    private $client_config;

    public function __construct($app_path, $config)
    {
        date_default_timezone_set('Asia/Colombo');
        $this->app_path = $app_path;
        $this->client_config = $config;
    }

    /**
     * Initialize client connection with the gateway
     * @param array ('extra_data' => array('key1'=> 'value1'),
     *                'transaction_data' => array('total_amount' => 0,
     *                                            'service_fee' => 0,
     *                                            'payment_amount' => 0,
     *                                            'currency_code' => 'USD'
     *                                            ),
     *                'config_redirect' => array('url' => 'http://...', 'method' => 'POST/GET')
     *              'client_reference' => 'merchant_reference'
     *                )
     */
    public function initialize($data)
    {
        # Include paymanet initialize
        include $this->app_path . 'classes/paycorp-client-php/au_com_gateway_IT/pcw_payment-init_UT.php';

        # Config Client
        $Client = $this->clientConfig();

        # Build PaymentInitRequest object
        $initRequest = new PaymentInitRequest();
        $initRequest->setClientId($this->client_config['client_id']);
        $initRequest->setTransactionType(TransactionType::$PURCHASE);
        $initRequest->setClientRef($data['client_reference']);
        $initRequest->setComment("");
        $initRequest->setTokenize(FALSE); # Set to false if you dant have SSL Cerification
        $initRequest->setExtraData($data['extra_data']); # PARAM

        # sets transaction-amounts details (all amounts are in cents)
        $transactionAmount = new TransactionAmount();
        $transactionAmount->setTotalAmount($data['transaction_data']['total_amount']); # PARAM
        $transactionAmount->setServiceFeeAmount($data['transaction_data']['service_fee']); # PARAM
        $transactionAmount->setPaymentAmount($data['transaction_data']['payment_amount']); # PARAM
        $transactionAmount->setCurrency($data['transaction_data']['currency_code']); # PARAM
        $initRequest->setTransactionAmount($transactionAmount);

        # sets redirect settings
        $redirect = new Redirect();
        $redirect->setReturnUrl($data['config_redirect']['url']);
        $redirect->setReturnMethod($data['config_redirect']['method']);
        $initRequest->setRedirect($redirect);
        $initResponse = $Client->payment()->init($initRequest);

        return $initResponse->responseData->paymentPageUrl;

    }

    /*
     * Complete the payment and get the response
     * @param array('reqid')
     */
    public function completePayment($data)
    {
        # Include payment complete file
        include $this->app_path . 'classes/paycorp-client-php/au_com_gateway_IT/pcw_payment-complete_UT.php';

        # Config client
        $Client = $this->clientConfig();

        # Build PaymentCompleteRequest object
        $completeRequest = new PaymentCompleteRequest();
        $completeRequest->setClientId($this->client_config['client_id']);
        $completeRequest->setReqid($data['reqid']);

        # Process PaymentCompleteRequest object
        $completeResponse = $Client->payment()->complete($completeRequest);

        $return_data = array();

        $return_data['card_type'] = isset($completeResponse->responseData->creditCard->type) ? $completeResponse->responseData->creditCard->type : '';
        $return_data['card_holder'] = isset($completeResponse->responseData->creditCard->holderName) ? $completeResponse->responseData->creditCard->holderName : '';
        $return_data['card_number'] = isset($completeResponse->responseData->creditCard->number) ? $completeResponse->responseData->creditCard->number : '';
        $return_data['currency'] = isset($completeResponse->responseData->transactionAmount->currency) ? $completeResponse->responseData->transactionAmount->currency : '';
        $return_data['settled_date'] = isset($completeResponse->responseData->settlementDate) ? $completeResponse->responseData->settlementDate : '';
        
        $return_data['payment_amount'] = isset($completeResponse->responseData->paymentAmount) ? $return_data['payment_amount'] : '';
       
        $return_data['txn_reference'] = isset($completeResponse->responseData->txnReference) ? $completeResponse->responseData->txnReference : '';
        
        $return_data['response_text'] = isset($completeResponse->responseData->responseText) ? $completeResponse->responseData->responseText : '';

        return $completeResponse;
        
    }

    public function clientConfig()
    {
        $ClientConfig = new ClientConfig();
        $ClientConfig->setServiceEndpoint($this->client_config['end_point']);
        $ClientConfig->setAuthToken($this->client_config['auth_token']);
        $ClientConfig->setHmacSecret($this->client_config['hmac_secret']);
        $ClientConfig->setValidateOnly(FALSE);
        $Client = new GatewayClient($ClientConfig);
        return $Client;
    }
}

ob_flush();