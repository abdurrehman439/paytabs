<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Paytabs extends CI_Controller 
{
    private $profile_id;
    private $server_key;
    private $api_url;

    function __construct()
    {
        parent::__construct();
        
        // Initialize configuration
        $this->profile_id = "159848";
        $this->server_key = "S2J9BHDGRM-JKMLRZBD6R-HL6JTDB2HR";
        $this->api_url = "https://secure-global.paytabs.com/payment/request";
    }

    public function create_payment()
    {
        $order_id = uniqid('ORD_');
        
        $data = [
            "profile_id" => $this->profile_id,
            "tran_type" => "sale",   // "sale" // "Auth" // "Capture" // "Void" // "Register"

            "tran_class" => "ecom",
            "cart_id" => $order_id,
            "cart_description" => "Payment for Order " . $order_id,
            "cart_currency" => "PKR",
            "cart_amount" => 100.00, // Replace with actual amount
            "callback" => base_url('paytabs/callback'),
            "return" => base_url('paytabs/payment_response'),
            
            "customer_details" => [
                "name" => 'admin',
                "email" => 'admin@admin.com',
                "phone" => '1234567890',
                "street1" => '123 Main St',
                "city" => 'New York',
                "state" => 'NY',
                "country" => "PK",
                "zip" => '10001'
            ]
        ];

        $response = $this->send_api_request($data);
        
        if(isset($response['redirect_url'])) {
            redirect($response['redirect_url']);
        } else {
            $this->session->set_flashdata('error', 'Payment initialization failed');
            redirect('checkout');
        }
    }

    public function payment_response()
    {
        print_r($this->input->get());
        print_r($this->input->post()); die;
         $tran_ref = 'TST2502902213492';
        
         if($tran_ref) {
            $verify_data = [
                "profile_id" => $this->profile_id,
                "tran_ref" => $tran_ref
            ];
            
            $verification = $this->verify_payment($verify_data);
           
            if($verification['payment_result']['response_status'] === 'A') {
                // Payment successful
                $this->session->set_flashdata('success', 'Payment completed successfully');
                redirect('order/success');
            } else {
                // Payment failed
                $this->session->set_flashdata('error', 'Payment failed');
                redirect('order/failed');
            }
         }
    }

    public function process_refund()
    {
        $tran_ref = 'TST2502902213492';
        $refund_amount = 100.00;

        $refund_data = [
            "profile_id" => $this->profile_id,
            "tran_type" => "refund",
            "tran_class" => "ecom",
            "cart_id" => uniqid('REF_'),
            "cart_currency" => "PKR",
            "cart_amount" => $refund_amount,
            "cart_description" => "Refund for transaction" . $tran_ref,
            "tran_ref" => $tran_ref
        ];

        $response = $this->send_api_request($refund_data);
       
        if($response['payment_result']['response_status'] === 'A') {
            $result = [
                'status' => 'success',
                'message' => 'Refund processed successfully',
                'refund_ref' => $response['tran_ref']
            ];
        } else {
            $result = [
                'status' => 'error',
                'message' => $response['payment_result']['response_message']
            ];
        }

        echo json_encode($result);
    }

    private function send_api_request($data)
    {
        $ch = curl_init($this->api_url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'authorization: ' . $this->server_key,
            'content-type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        // print_r($response);die;
        curl_close($ch);

        return json_decode($response, true);
    }

    private function verify_payment($data)
    {
        $verify_url = "https://secure-global.paytabs.com/payment/query";
        
        $ch = curl_init($verify_url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'authorization: ' . $this->server_key,
            'content-type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}
