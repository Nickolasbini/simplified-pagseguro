# Simplified Pag Seguro 2022 Library

This is a simplified pag seguro library responsible by handling transactions by the use of the Pag Seguro API (2022 version)
Avaliable at Packagist: [packagist](https://packagist.org/packages/nickolasbini/simplified-pagseguro)

# Usage

## Importing the library

```php
    use NickolasBini\SimplifiedPagSeguro\SimplifiedPagSeguro;
```

## Creating a transaction (checkout)

```php
    $yourPagSeguroEmail = null;
    $yourPagSeguroToken = null;
    $enviromentName = null;     // 'sandbox' or 'production'
    $notificationURL = [];      // urls to send notification in case transaction is payed (works only on production)

    $simplifiedPagSeguro = new SimplifiedPagSeguro($yourPagSeguroEmail, $yourPagSeguroToken, $enviromentName, $notificationURL);
    $referenceId = 'reference id';
    $description = 'description';
    $dueDate     = '2022-10-29';
    $holder = [
        'name'  => 'Buyer name',
        'tax_id' => '49293288109', // CPF number
        'email' => 'buyer_email@email.com'
    ];
    $address = [
        'street'      => 'rua rio de janeiro',
        'number'      => '000',
        'locality'    => 'centro',
        'city'        => 'Rio de Janeiro',
        'region'      => 'Rio de Janeiro',
        'region_code' => 'PR',
        'country'     => 'Brasil',
        'postal_code' => '20020050' 
    ];
    $amount = [
        'value'    => 1000,
        'currency' => 'BRL' 
    ];
    $instructionLines = ['Pagamento processado para DESC Fatura', 'Via PagSeguro']; // information displayed on Boleto

    // only for creditCard
    $metadata = [
        "Exemplo"     => "Aceita qualquer informação",
        "NotaFiscal"  => "123",
        "idComprador" => "123456"
    ];
    $creditCard = [
        "number"        => "1111111111111111",
        "exp_month"     => "01",
        "exp_year"      => "2000",
        "security_code" => "123",
    ];

    $simplifiedPagSeguro->setReferenceId($referenceId);
    $simplifiedPagSeguro->setDescription($description);
    $simplifiedPagSeguro->setDueDate($dueDate);
    $simplifiedPagSeguro->setHolder($holder);
    $simplifiedPagSeguro->setAddress($address);
    $simplifiedPagSeguro->setAmount($amount);
    $simplifiedPagSeguro->setPaymentType(2); // [1 => 'BOLETO', 2 => 'CREDIT_CARD']
    // for Boleto only
    $simplifiedPagSeguro->setInstructionLine($instructionLines);
    // for credit card only
    $simplifiedPagSeguro->setCreditCard($creditCard);
    // optional
    $simplifiedPagSeguro->setMetaData($metadata);
    $simplifiedPagSeguro->setSoft_descriptor("value");


    $result = $simplifiedPagSeguro->checkout();
    if($simplifiedPagSeguro->isCheckoutAuthorized()){
        exit('transaction was payed');
    }else{
        exit('transaction failed');
    }
```

### Response from the checkout creation

```json
    {
        "success": true,
        "content": {
            "id": "CHAR_E9075E12-E3D3-4C9D-948D-6A85E608E73C",
            "reference_id": "reference id",
            "status": "WAITING",
            "created_at": "2022-10-28T23:58:59.240-03:00",
            "description": "description",
            "amount": {
            "value": 1000,
            "currency": "BRL",
            "summary": {
                "total": 1000,
                "paid": 0,
                "refunded": 0
            }
            },
            "payment_response": {
            "code": "20000",
            "message": "SUCESSO"
            },
            "payment_method": {
            "type": "BOLETO",
            "boleto": {
                "id": "14A4DAB9-857C-4C29-A2A0-36E9DDCFD114",
                "barcode": "03399853012970000024227020901016278150000015630",
                "formatted_barcode": "03399.85301 29700.000242 27020.901016 2 78150000015630",
                "due_date": "2022-10-29",
                "instruction_lines": {
                "line_1": "Pagamento processado para DESC Fatura",
                "line_2": "Via PagSeguro"
                },
                "holder": {
                "name": "Buyer name",
                "tax_id": "49293288109",
                "email": "buyer_email@email.com",
                "address": {
                    "region": "Rio de Janeiro",
                    "city": "Rio de Janeiro",
                    "postal_code": "20020050",
                    "street": "rua rio de janeiro",
                    "number": "000",
                    "locality": "centro",
                    "country": "Brasil",
                    "region_code": "PR"
                }
                }
            }
            },
            "notification_urls": [],
            "links": [
            {
                "rel": "SELF",
                "href": "https://url",
                "media": "application\/pdf",
                "type": "GET"
            },
            {
                "rel": "SELF",
                "href": "https://url",
                "media": "image\/png",
                "type": "GET"
            },
            {
                "rel": "SELF",
                "href": "https://url",
                "media": "application\/json",
                "type": "GET"
            }
            ]
        },
        "checkoutId": "CHAR_E9075E12-E3D3-4C9D-948D-6A85E608E73C",
        "referenceId": "reference id"
    }
```

## When searching a transaction use one of the following methods

```php
    $simplifiedPagSeguro->searchCheckoutByReferenceId("555");
```

```php
    $simplifiedPagSeguro->searchCheckoutByCheckoutId("A01234B01234C01234D01234");
```

``
    The return is the same as the checkout creation method
``

## When wanting a refund for a payment use the following method

```php
    /* 
        @param String  the checkout id
        @param Integer the refund value
    */
    $simplifiedPagSeguro->checkoutReimbursementByCheckoutId("A01234B01234C01234D01234", 1000);
```
