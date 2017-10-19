<?php
namespace Paydock\Sdk;

require_once(__DIR__."/../tools/ServiceHelper.php");
require_once(__DIR__."/../tools/JsonTools.php");

use Paydock\Sdk\serviceHelper;
/*
 * This file is part of the Paydock.Sdk package.
 *
 * (c) Paydock
 *
 * For the full copyright and license information, please view
 * the LICENSE file which was distributed with this source code.
 */
final class Charges
{
    private $chargeData;
    private $token;
    private $customerId;
    private $paymentSourceData;
    private $customerData = array();
    private $action;
    
    public function create($amount, $currency, $description = "", $reference = "")
    {
        $this->chargeData = array("amount" => $amount, "currency"=>$currency, "description"=>$description, "reference" => $reference);
        $this->action = "create";
        return $this;
    }

    public function withToken($token)
    {
        $this->token = $token;
        return $this;
    }

    public function withCreditCard($gatewayId, $cardNumber, $expireYear, $expireMonth, $cardHolderName, $ccv)
    {
        $this->paymentSourceData = array("gateway_id" => $gatewayId, "card_number" => $cardNumber, "expire_month" => $expireMonth, "expire_year" => $expireYear, "card_name" => $cardHolderName, "card_ccv" => $ccv);
        return $this;
    }
    
    public function withBankAccount($gatewayId, $accountName, $accountBsb, $accountNumber, $accountHolderType = "", $accountBankName = "")
    {
        $this->paymentSourceData = array("gateway_id" => $gatewayId, "type" => "bank_account", "account_name" => $accountName, "account_bsb" => $accountBsb, "account_number" => $accountNumber, "account_holder_type" => $accountHolderType, "account_bank_name" => $accountBankName);
        return $this;
    }

    public function withCustomerId($customerId, $paymentSourceId = "")
    {
        $this->customerId = $customerId;
        if (!empty($paymentSourceId)) {
            $this->customerData["payment_source_id"] = $paymentSourceId;
        }
        return $this;
    }

    public function includeCustomerDetails($firstName, $lastName, $email, $phone)
    {
        $this->customerData += array("first_name" => $firstName, "last_name" => $lastName, "email" => $email, "phone" => $phone);
        return $this;
    }

    // TODO: add: includeAddress, includeMeta

    // TODO: add: get charges, refund, archived

    private function buildJson()
    {
        switch ($this->action) {
            case "create":
                return $this->buildCreateJson();
        }
    }

    private function buildCreateJson()
    {
        // TODO: add validation that at least one payment option has been provided (eg token, customer etc)

        $arrayData = [
            'amount'      => $this->chargeData["amount"],
            'currency'    => $this->chargeData["currency"],
        ];

        if (!empty($this->chargeData["reference"])){
            $arrayData += ['reference' => $this->chargeData["reference"]];
        }

        if (!empty($this->chargeData["description"])){
            $arrayData += ['description' => $this->chargeData["description"]];
        }

        if (!empty($this->token)) {
            $arrayData += ["token" => $this->token];

        } else if (!empty($this->customerId)) {
            $arrayData += ["customer_id" => $this->customerId];
            if (!empty($this->customerData)) {
                $arrayData += ["customer" => $customer];
            }
            
        } else if (!empty($this->paymentSourceData)) {
            if (empty($this->customerData)) {
                $arrayData += ["customer" => ["payment_source" => $this->paymentSourceData]];
            } else {
                $customer = $this->customerData + ["payment_source" => $this->paymentSourceData];
                $arrayData += ["customer" => $customer];
            }
        }

        $jsonTools = new JsonTools();
        $arrayData = $jsonTools->CleanArray($arrayData);

        return json_encode($arrayData);
    }

    public function call()
    {
        $data = $this->buildJson();

        return ServiceHelper::privateApiCall("POST", "charges", $data);
    }
}
?>