<?php
namespace Paydock\Sdk;

require_once(__DIR__."/../tools/ServiceHelper.php");
require_once(__DIR__."/../tools/JsonTools.php");

/*
 * This file is part of the Paydock.Sdk package.
 *
 * (c) Paydock
 *
 * For the full copyright and license information, please view
 * the LICENSE file which was distributed with this source code.
 */
final class Customers
{
    private $action;
    private $token;
    private $customerData;
    private $paymentSourceData;
    private $customerId;
    private $customerFilter;
    private $meta;
    private $actionMap = array("create" => "POST", "get" => "GET");
    
    public function create($firstName = "", $lastName = "", $email = "", $phone = "", $reference = "")
    {
        $this->action = "create";
        $this->customerData = ["first_name" => $firstName, "last_name" => $lastName, "email" => $email, "phone" => $phone, "reference" => $reference];
        return $this;
    }
    
    public function get()
    {
        $this->action = "get";
        return $this;
    }
    
    public function withCustomerId($customerId)
    {
        $this->customerId = $customerId;
        return $this;
    }

    public function withToken($token)
    {
        $this->token = $token;
        return $this;
    }
    
    public function withCreditCard($gatewayId, $cardNumber, $expireYear, $expireMonth, $cardHolderName, $ccv)
    {
        $this->paymentSourceData = ["gateway_id" => $gatewayId, "card_number" => $cardNumber, "expire_month" => $expireMonth, "expire_year" => $expireYear, "card_name" => $cardHolderName, "card_ccv" => $ccv];
        return $this;
    }
    
    public function withBankAccount($gatewayId, $accountName, $accountBsb, $accountNumber, $accountHolderType = "", $accountBankName = "")
    {
        $this->paymentSourceData = ["gateway_id" => $gatewayId, "type" => "bank_account", "account_name" => $accountName, "account_bsb" => $accountBsb, "account_number" => $accountNumber, "account_holder_type" => $accountHolderType, "account_bank_name" => $accountBankName];
        return $this;
    }
    
    public function withParameters($filter)
    {
        $this->customerFilter = $filter;
        return $this;
    }
    
    public function includeAddress($addressLine1, $addressLine2, $addressState, $addressCountry, $addressCity, $addressPostcode)
    {
        $this->paymentSourceData += ["address_line1" => $addressLine1, "address_line2" => $addressLine2, "address_state" => $addressState, "address_country" => $addressCountry, "address_city" => $addressCity, "address_postcode" => $addressPostcode];
        return $this;
    }

    // TODO: add: get payment sources, update customer, archive customer

    public function includeMeta($meta)
    {
        $this->meta = $meta;
        return $this;
    }

    private function buildJson()
    {
        switch ($this->action)
        {
            case "create":
                return $this->buildJsonCreate();
        }

        return "";
    }

    private function buildJsonCreate()
    {
        $arrayData = $this->customerData;

        if (!empty($this->token)) {
            $arrayData += ["token" => $this->token];
        } else if (!empty($this->paymentSourceData)) {
            $arrayData["payment_source"] = $this->paymentSourceData;
        }

        if (!empty($this->meta)) {
            $arrayData += ["meta" => $this->meta];
        }

        $jsonTools = new JsonTools();
        $arrayData = $jsonTools->CleanArray($arrayData);
        
        return json_encode($arrayData);
    }

    private function buildUrl()
    {
        switch ($this->action)
        {
            case "get":
                return $this->buildGetUrl();
        }
        return "customers";
    }

    private function buildGetUrl()
    {
        $url = "customers";
        if (!empty($this->customerId)) {
            $url .= "/" . urlencode($this->customerId);
        } else if (!empty($this->customerFilter)) {
            $url .= "?";
            foreach ($this->customerFilter as $key => $value) {
                $url .= urlencode($key) . "=" . urlencode($value);
            }
        }
        return $url;
    }

    public function call()
    {
        $data = $this->buildJson();
        $url = $this->buildUrl();

        return ServiceHelper::privateApiCall($this->actionMap[$this->action], $url, $data);
    }
}
?>