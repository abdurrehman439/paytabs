<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Paytab extends CI_Controller 
{
    private $profile_id;
    private $server_key;
    private $api_url;
    private $currency;


    function __construct()
    {
        parent::__construct();
        
        // Initialize configuration
        // $this->profile_id   = "159848";
        // $this->server_key   = "S2J9BHDGRM-JKMLRZBD6R-HL6JTDB2HR";
        // $this->api_url      = "https://secure-global.paytabs.com/payment/";

        $this->profile_id = "160317";
        $this->server_key = "SBJ9BJNKZW-JK9GJ26JL9-KH2MW2DHB2";
        $this->api_url    = "https://secure.paytabs.com/payment/";
        $this->currency   = 'AED';


    }


    // //////////////////////initiated create  payment/////////////////////////////////
    public function create_payment_tokenization()
    {
        $cart_amount = 102;
        $order_id    = uniqid('ORD_');

        $data = [
            "profile_id"  => $this->profile_id,
            "tran_type"   => "sale",   // "sale" // "Auth" // "Capture" // "Void" // "Register"
            "tran_class"  => "ecom",
            "cart_id"     => $order_id,
            "cart_description" => "Payment for Order " . $order_id,
            "cart_currency"    => $this->currency,
            "cart_amount"      => $cart_amount, // Replace with actual amount
            'tokenise'         => 2,                 // Request tokenization (2 for tokenize and save)    
            "callback" => base_url('paytab/callback'),
            "return"   => base_url('paytab/payment_response'),
            
            "customer_details" => [
                "name"    =>  'admin '.$order_id,
                "email"   => 'admin@admin.com',
                "phone"   => '1234567890',
                "street1" => '123 Main St',
                "city"    => 'New York',
                "state"   => 'NY',
                "country" => "PK",
                "zip"     => '10001'
            ]
        ];


        $response = $this->create_payment($data);
        if($response->error) 
        {
            echo $response->message ;die;
        }
        redirect($response->data->redirect_url);
    }
    
    private function create_payment($data)
    {
        $url = 'request';
        return $this->send_api_request($url, $data);
    }

    public function payment_response()
    {
         $get      = $this->input->get();
         $tran_ref = $this->input->post();
        // var_dump($get,$tran_ref);die;

         $tran_ref = 'TST2504102220214';
        
         if($tran_ref) {
            $data = [
                "profile_id" => $this->profile_id,
                "tran_ref"   => $tran_ref
            ];
           
            $response = $this->send_api_request('query', $data);
            if($response->error or (isset($response->data->payment_result->response_status) and $response->data->payment_result->response_status !='A')) 
            {
                echo ($response->message != '') ? $response->message : $response->data->payment_result->response_message;die;
            }
            $token    = $response->data->token;
            $tran_ref = $response->data->tran_ref;

            echo "Token    : " . $token . "<br>";
            echo "tran_ref : " . $tran_ref . "<br><br><br>";


            echo json_encode($response);

         }
    }

    // //////////////////////initiated recurring payment/////////////////////////////////

    public function initiated_recurring_payment()
    {
       // For the first initiated payment of a recurring payment, pass this field: "tokenise" => 2,

        $token    = '2C4655BE67A3EC31C6B593FE618B7FB9';
        $tran_ref = 'TST2504102220214';
       
        $data = [
           
            "profile_id"       => $this->profile_id,
            "tran_type"        => "sale",
            "tran_class"       => "recurring",
            "cart_id"          => "cart_55555",
            "cart_currency"    => $this->currency,
            "cart_amount"      => 200,
            "cart_description" => "Description of the items/services",
            "token"            => $token,
            "tran_ref"         => $tran_ref,
            "callback"         => base_url('paytab/callback_return')
        ];

        $response = $this->recurring_payment($data);
        if($response->error or (isset($response->data->payment_result->response_status) and $response->data->payment_result->response_status !='A')) 
        {
            echo ($response->message != '') ? $response->message : $response->data->payment_result->response_message;die;
        }

        echo json_encode($response);
    }

    public function callback_return(){
        print_r($_POST);
        print_r($_GET);
        // work here accourding to response 
        die;
    }

    private function recurring_payment($data)
    {
        $url = 'request';
        return $this->send_api_request($url, $data);
    }

    

    //////////////////////////////////////////////////////////

    public function process_refund_form()
    {
        $this->load->view('paytab/process_refund_form');
    }

    public function process_refund()
    {
        $tran_ref      = $this->input->post('tran_ref');
        $refund_amount = $this->input->post('refund_amount');
		
		$response = $this->verify($tran_ref);
        if($response->error or (isset($response->data->payment_result->response_status) and $response->data->payment_result->response_status !='A'))
        {
            echo  ($response->message != '') ? $response->message : $response->data->payment_result->response_message;die;
        }
       
        $data = [
            "tran_type"         => "refund",
            "tran_class"        => "ecom",
            "cart_id"           => $tran_ref,
            "cart_currency"     => $this->currency,
            "cart_amount"       => number_format($refund_amount, 2),
            "cart_description"  => "Refund for transaction " . $tran_ref,
            "tran_ref"          => $tran_ref
        ];
		
        $response = $this->refund($data);
        if($response->error or (isset($response->data->tran_type) and $response->data->tran_type !='Refund')) 
        {
            echo ($response->message != '') ? $response->message : $response->data->payment_result->response_message;die;
        }

        echo json_encode($response);
    }

    private function refund($data)
    {
        $url = 'request';
        return $this->send_api_request($url, $data);
    }

    private function verify($tran_ref)
    {
        $data = [
            "tran_ref" => $tran_ref
        ];

        $url  = 'query';
        return $this->send_api_request($url, $data, true);
    }

    private function send_api_request($url, $data = [], $is_verify = false)
    {
        $url = $this->api_url . $url;
        $ch  = curl_init($url);

        $default = [
            "profile_id" => $this->profile_id,
        ];

        $data = array_merge($default, $data);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'authorization: ' . $this->server_key,
                'content-type: application/json'
            ],
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
		
        return $this->curl_response($response);
    }

    private function curl_response($result)
    {
        $dt = json_decode($result);
        if(isset($dt->code)) 
        {
            $response = array(
                'error'   => true,
                'message' => $dt->message,
                'data'    => $dt
            );

            return (object)$response;
        }

        $response = array(
            'error'   => false,
            'message' => '',
            'data'    => $dt
        );

        return (object)$response;
    }
}