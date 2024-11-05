<?php
// возможно полезные ссылки
//  https://zzizz.su/vse-kljuchi-kriptopro-5-0-s-interneta/   ключи крипто про
class CPSABG
{
    private $urlNK = 'https://xn--80aqu.xn----7sbabas4ajkhfocclk9d3cvfsa.xn--p1ai';
    private $urlV3 = 'https://markirovka.crpt.ru/api/v3/';
    private $urlV4 = 'https://markirovka.crpt.ru/api/v4/';
    public $apikey; //  получаем из админки https://xn--j1ab.xn----7sbabas4ajkhfocclk9d3cvfsa.xn--p1ai/profile  ( ffffffffffffffff примерно такой вид)
    private $SHA1; //  получаем из админки  ай ди токена криптопро нужно чтобы все было установлено на сервере ( ffffffffffffffffffffffffffffffffffffffff примерно такой вид)
    private $token; // вычисляеться каждые 5 часов
    private $omsId; // получаем из админки OMS ID  https://suzgrid.crpt.ru/management/devices ( OMS ID: ffffffff-3333-3333-ffff-ffffffffffff примерно такой вид)
    private $ConnectionID; // получаем из админки Идентификатор соединения устройства  если его нет то просто создать  https://suzgrid.crpt.ru/management/devices (
    private $debug_cps = false;
    private $XSignature = '';
    private $inn; // inn  организации примерно такой вид  2308288240  или 920151472228
    private $SHAUSER; // пользователь который работает с предприятиями  ( ffffffffffffffffffffffffffffffffffffffff примерно такой вид)
    private $limit_GetProductListGtin = 25; // не больше 25
    private $gtin;
    public $statusKIZ = ['EMITTED' => 'ЭМИТИРОВАН', 'APPLIED' => 'НАНЕСЁН',
        'INTRODUCED' => 'В ОБОРОТЕ', 'WRITTEN_OFF' => 'СПИСАН', 'RETIRED' => 'ВЫБЫЛ',
        'DISAGGREGATION' => 'РАСФОРМИРОВАН', 'WITHDRAWN' => 'ВЫБЫЛ', ];
    public function __construct($set)
    {
        $this->apikey = $set->apikey;
        $this->SHA1 = $set->SHA1;
        $this->omsId = $set->omsId;
        $this->inn = '';
        if (isset($set->inn)) {
            $this->inn = $set->inn;
        }
        $this->SHAUSER = '';
        if (isset($set->SHAUSER)) {
            $this->SHAUSER = $set->SHAUSER;
        }

        $this->owner_inn = '';
        if (isset($set->owner_inn)) {
            $this->owner_inn = $set->owner_inn;
        }
        $this->participant_inn = ''; // ето собствнное производство
        if (isset($set->participant_inn)) {
            $this->participant_inn = $set->participant_inn;
        }
        $this->producer_inn = ''; // ето контрактное производство
        if (isset($set->producer_inn)) {
            $this->producer_inn = $set->producer_inn;
        }

        $this->ConnectionID = $set->ConnectionID;
        $this->createMethodType = $set->createMethodType;
        $this->GetAuxChzToken();
    }

    public function CisesHistory($cis)
    {
        $url = 'https://markirovka.crpt.ru/api/v3/true-api/cises/list?values=' .
            urlencode($cis);
        //print_r($url);

        //cises/history?cis   /cises/list?values
        $headers = ['Authorization' => 'Bearer ' . $this->GetTokenGis(), 'accept' =>
            '*/*', 'Content-Type' => 'application/json'];
        $request = wp_remote_post($url, array('headers' => $headers, ));
        return (wp_remote_retrieve_body($request));
        ;
    }
    public function СodesСheck($codes)
    {
        //  url -X POST "<url стенда>/cises/history?cis=0104600266012258215n4Jh5D"
        $url = 'https://markirovka.crpt.ru/api/v4/true-api/codes/check';
        $json = json_encode(['codes' => $codes, 'inn' => $this->inn]);
        $token = $this->token;
        $headers = ['Authorization' => 'X-Api-Key ' . $token, 'Content-Type' =>
            'application/json'];
        $request = wp_remote_post($url, ['body' => $json, 'headers' => $headers, ]);
        return (wp_remote_retrieve_body($request));
    }

    public function WithdrawalFromCirculation($ItemSign, $pg = 'lp', $type= 'LK_RECEIPT')
    { // pg string + Товарная группа Cм. "Справочник «Список поддерживаемых товарных  lp Предметы одежды, бельё постельное, столовое, туалетное и кухонное
        $document_format = 'MANUAL';
       //  $type = 'LK_RECEIPT'; // Вывод из оборота JSON    Код типа документа
        $url = 'https://markirovka.crpt.ru/api/v3/true-api/lk/documents/create?pg=' . $pg;
        /*
        POST <url стенда>/lk/documents/c
        Authorization: Bearer <ТОКЕН>
        Content-Type: application/json
        {
        "document_format":"string",  MANUAL – формат * .json; XML – формат * .xml; CSV – формат * .csv
        "product_document":"<Документ в формате base64>",
        "type":"string",
        "signature":"<Открепленная УКЭП формата Base64>"
        }
        */
        $ItemSignJson = json_encode($ItemSign);
        $product_document = base64_encode($ItemSignJson);
        $file_name = 'sig-' . md5($ItemSignJson) . '.txt';
        $file_path = WBPRIME_SIGN_PATH . $file_name; // имя временного файла
        // через консоль формируем подпись
        file_put_contents($file_path, $ItemSignJson);
        $cmd = '/opt/cprocsp/bin/amd64/cryptcp -sign -thumbprint ' . $this->SHAUSER .
            ' -strict "' . $file_path . '" "' . $file_path . '.p7s"';
        $output = null;
        $return_var = null;
        exec($cmd, $output, $return_var);
        $signature = file_get_contents($file_path . '.p7s');
        @unlink($file_path);
        @unlink($file_path . '.p7s');
		$inn = $inn = $this->producer_inn;
		if ($type == 'LK_RECEIPT'){
			$inn = $this->inn;
		}
        $token = $this->GetTokenGis($inn);
        $headers = ['Authorization' => 'Bearer ' . $token, 'Content-Type' =>
            'application/json'];
        $body = ["document_format" => $document_format, "product_document" => $product_document,
            "type" => $type, "signature" => $signature, ];
        $json = json_encode($body);
        $request = wp_remote_post($url, ['body' => $json, 'headers' => $headers, ]);
        // тут возможно как то  выдать ответ   в боди есть номер задания от ЧЗ
        //$res = json_decode(wp_remote_retrieve_body($request));
        return $request;
    }
    public function GetTokenGis($inn='')
    {
        $url = 'https://markirovka.crpt.ru/api/v3/true-api/auth/key';
        $request = wp_remote_get($url, [ //  'body' => $json,
            ]);
        $res = wp_remote_retrieve_body($request);
        $authkey = json_decode($res);
        $content = $authkey->data;
        $cert = $this->SetupCertificate(CURRENT_USER_STORE, "My", STORE_OPEN_READ_ONLY,
            0, $this->SHAUSER, 0, 1);
        $signer = new CPSigner();
        $signer->set_Certificate($cert);
        $sd = new CPSignedData();
        $sd->set_Content($content);
        $sd->set_ContentEncoding(1);
        $sm = $sd->SignCades($signer, CADES_BES, false, ENCODE_BASE64);
        $sm = preg_replace("/[\r\n]/", "", $sm);
        $json = ['uuid' => $authkey->uuid, 'data' => $sm, ];
        $client = new GuzzleHttp\Client();
		if (!$inn){
			$inn = $this->inn;
		}
        $json = ['uuid' => $authkey->uuid, 'data' => $sm, 'inn' => $inn];
        $url = $this->urlV3 . 'true-api/auth/simpleSignIn/'; // Получение аутентификационного токена
        $rez = $client->request('POST', $url, ['debug' => $this->debug_cps, 'curl' => [CURLOPT_SSL_CIPHER_LIST =>
            'LEGACY-GOST2012-GOST8912-GOST8912', ], 'verify' => WBPRIME_PLUGIN_PATH .
            'guc2022.crt', 'json' => ($json), ]);
        $token = json_decode($rez->getBody()->__toString())->token;
        return $token;
    }
    public function CisesSearchGtins($gtins)
    {
        $token = $this->GetTokenGis();
        //print_r($res); die;
        $url = 'https://markirovka.crpt.ru/api/v4/true-api/cises/search';
        $to_date = (new \DateTime('now + 10000 days'))->format('c');
        $from_date = (new \DateTime('now - ' . CHZ_DAY_MINUS . ' days'))->format(DateTime::
            ISO8601);
        $headers = ['Authorization' => 'Bearer ' . $token, 'Content-Type' =>
            'application/json'];
        // Списан выключаем.7
        $jsonArray = ["filter" => ['gtin' => $gtins, 'productGroups' => ['lp']]];
        $json = json_encode($jsonArray);
        //	print_r($gtins);
        $request = wp_remote_post($url, ['body' => $json, 'headers' => $headers, ]);
        $res = json_decode(wp_remote_retrieve_body($request));
        $out = $res->result;
        while (!$res->isLastPage) {
            $sgtin = end($out);
            $jsonArray = ["filter" => ['gtin' => $gtins, 'productGroups' => ['lp']],
                "pagination" => ["lastEmissionDate" => $sgtin->emissionDate, "sgtin" => $sgtin->
                sgtin]];
            $json = json_encode($jsonArray);
            $request = wp_remote_post($url, ['body' => $json, 'headers' => $headers, ]);
            $res = json_decode(wp_remote_retrieve_body($request));
            $out = array_merge($out, $res->result);
        }
        return $out;
    }
    public function CisesSearch()
    {
        $token = $this->GetTokenGis();
        //print_r($res); die;
        $url = 'https://markirovka.crpt.ru/api/v4/true-api/cises/search';
        $to_date = (new \DateTime('now + 10000 days'))->format('c');
        $from_date = (new \DateTime('now - ' . CHZ_DAY_MINUS . ' days'))->format(DateTime::
            ISO8601);
        $headers = ['Authorization' => 'Bearer ' . $token, 'Content-Type' =>
            'application/json'];
        // Списан выключаем.7
        $jsonArray = ["filter" => ['emissionDatePeriod' => ["from" => $from_date, "to" =>
            $to_date], 'productGroups' => ['lp']]];
        $json = json_encode($jsonArray);
        //	print_r($headers); die;
        $request = wp_remote_post($url, ['body' => $json, 'headers' => $headers, ]);
        $logger = wc_get_logger();
        if (is_wp_error($request)) {
            // TODO: validate the error.
            $logger->info(wc_print_r($request, true), array('source' => 'CPSABG'));
            return false;
        }
        $res = json_decode(wp_remote_retrieve_body($request));
        $out = $res->result;
        $perPage = 1;
        while (!$res->isLastPage) {
            $sgtin = end($out);
            $jsonArray = ["filter" => ['emissionDatePeriod' => ["from" => $from_date, "to" =>
                $to_date], 'productGroups' => ['lp']], "pagination" => ["lastEmissionDate" => $sgtin->
                emissionDate, "sgtin" => $sgtin->sgtin]];
            $perPage++;
            $json = json_encode($jsonArray);
            $request = wp_remote_post($url, ['body' => $json, 'headers' => $headers, ]);
            $res = json_decode(wp_remote_retrieve_body($request));
            $out = array_merge($out, $res->result);
        }
        return $out;
    }
    public function CodesCheck($codes, $inn)
    {
        // $headers = [ 'Authorization' => 'clientToken ' .$this->token];
        $headers = ['clientToken' => $this->token, 'Content-Type' => 'application/json'];
        $client = new GuzzleHttp\Client(['headers' => $headers]);
        $json = ["codes" => $codes, "inn" => $inn];
        $orderId = '13b8b729-e353-43c6-a58e-13d3816290c3';
        $gtin = '04640255271761';
        $url = 'https://suzgrid.crpt.ru/api/v3/order/status?orderId=' . $orderId .
            '&omsId=' . $this->omsId . '&gtin=' . $gtin;
        $url = 'https://suzgrid.crpt.ru//api/v3/codes?orderId=' . $orderId . '&omsId=' .
            $this->omsId . '&gtin=' . $gtin;
        $url = 'https://suzgrid.crpt.ru/api/v3/order/codes/blocks?omsId=' . $this->
            omsId . '&orderId=' . $orderId . '&gtin=' . $gtin;
        /*
        https://suzgrid.crpt.ru/webapi/v1/deferredPrint/c30cb9c4-e7a2-44d9-920c-16b71c0e5a95?orderId=754cacb3-3c44-4eb7-9903-c528563f3430&blockId=de522e50-b0c5-451a-8b56-1b063b567397&gtin=04640255272096&quantity=1&fileFormat=PDF&stickerId=bb7f9e66-9c71-4539-a9bb-5dc5764bbcfa&printType=THERM
        
        
        GET /api/v3/order/codes/retry?omsId=a024ae09-ef7c-449e-b461-05d8eb116c90&blockId=a024ae09-ef7c-449e-b461-05d8eb116c90 HTTP/1.1
        Accept: application/json
        */
        //$url = 'https://suzgrid.crpt.ru/api/v3/order/codes/retry?omsId='.$this->omsId.'&blockId=a024ae09-ef7c-449e-b461-05d8eb116c90';
        $json = ["filter" => ["orderIds" => ['13b8b729-e353-43c6-a58e-13d3816290c3'],
            'workflowTypes' => ['GET_CODES']], 'limit' => 100];
        $url = 'https://suzgrid.crpt.ru/api/v3/receipts/receipt/search?omsId=' . $this->
            omsId;
        $res = $client->request('POST', $url, ['debug' => 0, 'curl' => [CURLOPT_SSL_CIPHER_LIST =>
            'LEGACY-GOST2012-GOST8912-GOST8912', ], 'json' => $json, 'verify' =>
            WBPRIME_PLUGIN_PATH . 'guc2022.crt', ]);
        echo '888';
        // print_r($res->getBody()->__toString());
        $bbb = json_decode($res->getBody()->__toString());
        print_r($bbb);
        echo '99999';
        die;
    }
    public function LpOrdersNotSign($products, $productionOrderId)
    {
        $jsonArray = ["productGroup" => "lp", "products" => $products,
            'releaseMethodType' => 'PRODUCTION', 'createMethodType' => $this->
            createMethodType, "productionOrderId" => $productionOrderId];
        $url = 'https://suzgrid.crpt.ru/api/v2/lp/orders?omsId=' . $this->omsId;
        $json = json_encode($jsonArray);
        $headers = ['Accept' => 'application/json', 'clientToken' => $this->token,
            'Content-Type' => 'application/json'];
        $request = wp_remote_post($url, array(
            'body' => $json,
            'headers' => $headers,
            ));
        $logger = wc_get_logger();
        if (is_wp_error($request)) {
            // TODO: validate the error.
            $logger->info(wc_print_r($request, true), array('source' => 'CPSABG'));
            return false;
        }
        return json_decode(wp_remote_retrieve_body($request));
    }
    public function LpOrdersSign($products, $productionOrderId)
    {
        $jsonArray = (object)["productGroup" => "lp", "products" => $products,
            'attributes' => ['releaseMethodType' => 'PRODUCTION', 'createMethodType' => $this->
            createMethodType, "productionOrderId" => $productionOrderId]];
        /*
        POST /api/v3/order?omsId=CDF12109-10D3-11E6-8B6F-0050569977A1 HTTP/1.1
        Accept: application/json
        clientToken: 1cecc8fb-fb47-4c8a-af3d-d34c1ead8c4f
        Content-Type: application/json
        X-Signature: {подпись}
        {
        "productGroup":"lp",
        "products":[{
        "gtin":"01334567894339",
        "quantity":2,
        "serialNumberType":"SELF_MADE",
        "serialNumbers":["QIQ8BQCXmSJJ","GLTP9kqZn5QR"],
        "templateId": 10,
        "cisType": "UNIT",
        }],
        "serviceProviderId":"c5fe527a-564a-4075-b7dd-72f08cb9a8b1", 
        "attributes": {
        "contactPerson":"Иванов П.А.", 
        "releaseMethodType": "IMPORT",
        "createMethodType": "CM",
        "productionOrderId": "08528091-808a-41ba-a55d-d6230c64b332"
        }   
        }
        */


        $url = 'https://suzgrid.crpt.ru/api/v3/order?omsId=' . $this->omsId;
        $json = json_encode($jsonArray);
        $file_name = 'sig-' . md5($json) . '.txt';
        $file_path = WBPRIME_SIGN_PATH . $file_name; // имя временного файла
        // через консоль формируем подпись
        file_put_contents($file_path, $json);
        $cmd = '/opt/cprocsp/bin/amd64/cryptcp -sign -thumbprint ' . $this->SHAUSER .
            ' -detached -strict "' . $file_path . '" "' . $file_path . '.p7s"';
        $output = null;
        $return_var = null;
        exec($cmd, $output, $return_var);
        //     print_r($output);
        //     print_r($return_var);
        $signature = file_get_contents($file_path . '.p7s');
        $signature = preg_replace("/[\r\n]/", "", $signature);
        //	die;
        $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json',
            'clientToken' => $this->token, 'X-Signature' => $signature];
        /*      print_r($url);
        print_r("\n");
        print_r($json);
        print_r("\n");
        print_r($headers);
        print_r("\n");	*/
        $request = wp_remote_post($url, ['body' => $json, 'headers' => $headers]);
        /*    print_r($request);
        die;
        */
        $logger = wc_get_logger();
        if (is_wp_error($request)) {
            // TODO: validate the error.
            $logger->info(wc_print_r($request, true), array('source' => 'CPSABG'));
            return false;
        }
        return json_decode(wp_remote_retrieve_body($request));
    }
    public function LpOrders($products)
    { // делаем заказ ордера 01.01.00.00 Создать заказ на эмиссию КМ».
        $jsonArray = ["productGroup" => "lp", "products" => $products,
            'releaseMethodType' => 'PRODUCTION', 'createMethodType' => 'SELF_MADE'];
        $url = 'https://suzgrid.crpt.ru/api/v2/lp/orders?omsId=' . $this->omsId;
        //  $headers = ['clientToken' => $this->token,  'Content-Type' => 'application/json'];
        $json = json_encode($jsonArray);
        $content_format = preg_replace('/(\n|\r|\t|\f)/m', '', $json);
        print_r($content_format);
        print_r("\n");
        $cert = $this->SetupCertificate(CURRENT_USER_STORE, "My", STORE_OPEN_READ_ONLY,
            0, $this->SHA1, 0, 1);
        $signer = new CPSigner();
        $signer->set_Certificate($cert);
        $sd = new CPSignedData();
        $sd->set_Content(utf8_encode('' . $json));
        //  $sd->set_ContentEncoding(1);
        // $sm = $sd->SignCades($signer, CADES_BES, true, ENCODE_BASE64);
        $sm = base64_encode($sd->SignCades($signer, CADES_BES, true, ENCODE_BINARY));
        print_r($sm);
        print_r("\n");
        //die;
        $this->XSignature = $sm;
        // $this->XSignature = preg_replace("/[\r\n]/", "", $sm);
        // print_r($sm); //print_r("\n");
        //die;
        $headers = ['Accept' => 'application/json', 'clientToken' => $this->token,
            'Content-Type' => 'application/json', 'X-Signature' => $this->XSignature];
        print_r($headers);
        print_r("\n");
        //  $headers = [ 'clientToken' => $this->token,    'Content-Type' => 'application/json'];
        //  $headers = ['X-Signature' => $this->XSignature, 'clientToken' => $this->token,        ];
        /*
        $client = new GuzzleHttp\Client(['headers' => $headers]);
        $request = $client->request('POST', $url, ['debug' => $this->debug_cps,  'json' => $jsonArray, ]);
        $result = json_decode($request->getBody()->__toString());
        print_r($result);
        die;
        */
        // print_r($headers);
        //die;
        // !!!!!!!
        die;
        $request = wp_remote_post($url, array(
            'body' => $json,
            'headers' => $headers,
            ));
        // print_r($request);
        /*
        [body] =>    [omsId] => 39bd714b-6838-4292-89c8-2c675f6121af
        [orderId] => 73789e2b-66ba-4061-a60b-2b7305ed950c
        [expectedCompleteTimestamp] => 120000
        )
        
        
        
        
        дальше надо как то сохранить етот заказ и его подписать в соответчтвии с чем то
        
        */
        $logger = wc_get_logger();
        if (is_wp_error($request)) {
            // TODO: validate the error.
            $logger->info(wc_print_r($request, true), array('source' => 'CPSABG'));
            return false;
        }
        /* return json_decode('{"omsId":"39bd714b-6838-4292-89c8-2c675f6121af","orderId":"cdf2ddd5-a307-4a77-b87a-e3c3e52339d6","expectedCompleteTimestamp":120000}');
        */
        return json_decode(wp_remote_retrieve_body($request));
        print_r($response);
        die;
    }
    // ХЗЗЗЗЗ
    public function GetCodesAbg($orderId, $gtin, $quantity)
    {
        $headers = ['clientToken' => $this->token, 'Content-Type' => 'application/json'];
        $url = 'https://suzgrid.crpt.ru/api/v3/codes?orderId=' . $orderId . '&gtin=' . $gtin .
            '&quantity=' . $quantity . '&omsId=' . $this->omsId;
        $request = wp_remote_get($url, array('headers' => $headers, ));
        return json_decode(wp_remote_retrieve_body($request));
    }
    public function blocksAbg($orderId, $gtin)
    {
        /*
        /api/v3/order/codes/blocks?omsId=qq&orderId=ee&gtin=04601234567898 HTTP/1.1
        Accept: application/json
        clientToken: 1cecc8fb-fb47-4c8a-af3d-d34c1ead8c4f
        */
        $headers = ['clientToken' => $this->token, 'Content-Type' => 'application/json'];
        $url = 'https://suzgrid.crpt.ru/api/v3/order/codes/blocks?omsId=' . $this->
            omsId . '&orderId=' . $orderId . '&gtin=' . $gtin;
        $request = wp_remote_get($url, array('headers' => $headers, ));
        print_r(json_decode(wp_remote_retrieve_body($request)));
        //  return json_decode(wp_remote_retrieve_body($request));
        //	print_r($url);

    }
    //	4.4.2. Метод «Получить статус массива КМ из заказа»
    public function CPOrderStatusAbg($orderId, $gtin)
    {
        $headers = ['clientToken' => $this->token, 'Content-Type' => 'application/json'];
        $url = 'https://suzgrid.crpt.ru/api/v3/order/status?orderId=' . $orderId .
            '&omsId=' . $this->omsId . '&gtin=' . $gtin;
        $request = wp_remote_get($url, array('headers' => $headers, ));
        return json_decode(wp_remote_retrieve_body($request));
        /*  GET /api/v3/order/status?orderId=b024ae09-ef7c-449e-b461-05d8eb116c79&omsId=cdf12109-10d3-11e6-8b6f-0050569977a1&gtin=01334567894339 HTTP/1.1
        Accept: application/json
        */
    }
    public function SignOrdersAbg($docId)
    {
        $cert = $this->SetupCertificate(CURRENT_USER_STORE, "My", STORE_OPEN_READ_ONLY,
            0, $this->SHA1, 0, 1);
        $signer = new CPSigner();
        $signer->set_Certificate($cert);
        $sd = new CPSignedData();
        $sd->set_Content('{"productGroup":"lp","products":[{"gtin":"04640255275356","quantity":2,"serialNumberType":"OPERATOR","templateId":10,"cisType":"UNIT"},{"gtin":"04640255275349","quantity":1,"serialNumberType":"OPERATOR","templateId":10,"cisType":"UNIT"}],"releaseMethodType":"PRODUCTION","createMethodType":"SELF_MADE"}');
        /*
        $client = new GuzzleHttp\Client();
        // $this->urlV3  'https://markirovka.crpt.ru/api/v3/';
        $url = $this->urlV3 . 'true-api/auth/key';
        $res = $client->request('GET', $url, ['debug' => $this->debug_cps]);
        $authkey = json_decode($res->getBody()->__toString());
        
        
        $content = $authkey->data;
        $sd->set_Content($content);
        
        
        */
        $sd->set_ContentEncoding(1);
        $sm = $sd->SignCades($signer, CADES_BES, true, ENCODE_BASE64);
        $signature = preg_replace("/[\r\n]/", "", $sm);
        //print_r($signature);
        $json = ["docId" => $docId, 'signature' => $signature];
        $headers = ['clientToken' => $this->token, 'Content-Type' => 'application/json'];
        $url = 'https://suzgrid.crpt.ru/api/v3/documents/sign?omsId=' . $this->omsId;
        $request = wp_remote_post($url, array(
            'body' => json_encode($json),
            'headers' => $headers,
            ));
        return $request;
    }
    public function SearchMarketSku($sku)
    { // тут опускаем маркет SKU   оно ищет по ЧЗ поле article и возвращает 1 ый найденный
        $products = $this->GetProductListGtin();
        //	print_r( $products );		die;
        $filtered_products = array_filter($products, function ($product)use ($sku)
        {
            return $product->article === $sku; }
        );
        if (!empty($filtered_products)) {
            return reset($filtered_products); // Получаем первый найденный продукт

        }
        return false;
    }
    public function OrderList()
    { // доработать
        // $headers = [ 'Authorization' => 'clientToken ' .$this->token];
        $headers = ['clientToken' => $this->token, 'Content-Type' => 'application/json'];
        $client = new GuzzleHttp\Client(['headers' => $headers]);
        $url = 'https://suzgrid.crpt.ru/api/v3/order/list?omsId=' . $this->omsId;
        $res = $client->request('GET', $url, ['debug' => $this->debug_cps, 'curl' => [CURLOPT_SSL_CIPHER_LIST =>
            'LEGACY-GOST2012-GOST8912-GOST8912', ], 'verify' => WBPRIME_PLUGIN_PATH .
            'guc2022.crt', ]);
        return $json = json_decode($res->getBody()->__toString());
    }
    public function GetAuxChzToken()
    {
        $cache_key = ('CPSABG_token' . $this->apikey);
        $token = wp_cache_get($cache_key, 'CPSABG', true);
        // $token = false;
        if ($token) {
            $this->token = $token;
            return $token;
        }
        $client = new GuzzleHttp\Client();
        // $this->urlV3  'https://markirovka.crpt.ru/api/v3/';
        $url = $this->urlV3 . 'true-api/auth/key';
        $res = $client->request('GET', $url, ['debug' => $this->debug_cps]);
        $authkey = json_decode($res->getBody()->__toString());
        $content = $authkey->data;
        $cert = $this->SetupCertificate(CURRENT_USER_STORE, "My", STORE_OPEN_READ_ONLY,
            0, $this->SHA1, 0, 1);
        $signer = new CPSigner();
        $signer->set_Certificate($cert);
        $sd = new CPSignedData();
        $sd->set_Content($content);
        $sd->set_ContentEncoding(1);
        $sm = $sd->SignCades($signer, CADES_BES, false, ENCODE_BASE64);
        $sm = preg_replace("/[\r\n]/", "", $sm);
        $json = ['uuid' => $authkey->uuid, 'data' => $sm, ];
        // $this->urlV3  'https://markirovka.crpt.ru/api/v3/';
        $url = $this->urlV3 . 'true-api/auth/simpleSignIn/' . $this->ConnectionID; // Получение аутентификационного токена
        $rez = $client->request('POST', $url, ['debug' => $this->debug_cps, 'curl' => [CURLOPT_SSL_CIPHER_LIST =>
            'LEGACY-GOST2012-GOST8912-GOST8912', ], 'verify' => WBPRIME_PLUGIN_PATH .
            'guc2022.crt', 'json' => ($json), ]);
        $this->token = json_decode($rez->getBody()->__toString())->token;
        wp_cache_set($cache_key, $this->token, 'CPSABG', 18000); // 5*60*60  5 часов
        return $this->token;
    }
    public function GetProductListGtin()
    {
        $cache_key = ('GetProductListGtin' . $this->apikey);
        $goods = wp_cache_get($cache_key, 'GetProductListGtin', true);
        if ($goods) {
            return $goods;
        }
        $to_date = (new \DateTime('now + 10000 days'))->format('Y-m-d H:i:s');
        $from_date = (new \DateTime('now - 10000 days'))->format('Y-m-d H:i:s');
        $offset = 0;
        //ceil(405/10)
        $client = new GuzzleHttp\Client();
        $searchStr = $this->urlNK . '/v4/product-list?apikey=' . $this->apikey .
            '&limit=' . $this->limit_GetProductListGtin . '&offset=' . $offset .
            '&from_date=' . $from_date . '&to_date=' . $to_date . '&good_status=published';
        $res = $client->request('GET', $searchStr, ['debug' => $this->debug_cps])->
            getBody()->getContents();
        $res = json_decode($res);
        $total = $res->result->total;
        $goods = $this->GetProductListGtinAddArticle($res->result->goods);
        for ($offset = 1; $offset <= ceil($total / $this->limit_GetProductListGtin); $offset++) {
            $searchStr = $this->urlNK . '/v4/product-list?apikey=' . $this->apikey .
                '&limit=' . $this->limit_GetProductListGtin . '&offset=' . $offset * $this->
                limit_GetProductListGtin . '&from_date=' . $from_date . '&to_date=' . $to_date .
                '&good_status=published';
            //	print_r($searchStr);
            $res = $client->request('GET', $searchStr, ['debug' => $this->debug_cps])->
                getBody()->getContents();
            $res = json_decode($res);
            if ($res->result->goods) {
                $goods = array_merge($this->GetProductListGtinAddArticle($res->result->goods), $goods);
            }
        }
        wp_cache_set($cache_key, $goods, 'GetProductListGtin', WBPRIME_CACHE_CHZ_TIME);
        return $goods;
    }
    public function GetProductListGtinAddArticle($goods)
    {
        $gtins = [];
        $cache_key = ('GetProductListGtinAddArticle' . $this->apikey);
        foreach ($goods as $tmp) {
            $gtins[] = $tmp->gtin;
        }
        $gtins = implode(';', $gtins);
        $cache_key = ('GetProductListGtinAddArticle' . $this->apikey . $gtins);
        $res = wp_cache_get($cache_key, 'GetProductListGtinAddArticle', true);
        if (!$res) {
            $wait = mt_rand(1, 99);
            if ($wait <= 25) { // эмперическая хрень примерная задержка чтобы раз в минуту было меньше 100 ударов  3000 товаров в ЧЗ
                sleep(1);
            }
            $searchStr = $this->urlNK . '/v3/product?apikey=' . $this->apikey . '&gtins=' .
                $gtins;
            $client = new GuzzleHttp\Client();
            $res = $client->request('GET', $searchStr, ['debug' => $this->debug_cps])->
                getBody()->getContents();
            $res = json_decode($res);
            wp_cache_set($cache_key, $res, 'GetProductListGtinAddArticle',
                WBPRIME_CACHE_CHZ_TIME);
        }
        for ($i = 0; $i < count($goods); $i++) {
            $goods[$i]->article = $i;
            foreach ($res->result as $ar) {
                if ($goods[$i]->good_id == $ar->good_id) {
                    foreach ($ar->good_attrs as $attr) {
                        $goods[$i]->type = 'simple';
                        if ($attr->attr_id == 13914) { // аттрибут артикула
                            /*
                            [8] => stdClass Object
                            (
                            [attr_id] => 13914
                            [attr_name] => Модель / артикул производителя
                            [attr_value] => шортыМужФул_Симпсоны_желтый
                            [attr_value_type] => Артикул
                            [attr_group_id] => 24
                            [attr_group_name] => Идентификация товара
                            [value_id] => 4888445478
                            [published_date] => 2024-07-15T09:46:28+03:00
                            )
                            */
                            $goods[$i]->article = $attr->attr_value;
                            // break;
                        }

                    }
                    foreach ($ar->good_attrs as $attr) {
                        if ($attr->attr_id == 16271) { // ето типа набор
                            $goods[$i]->type = 'bundle';
                            if (preg_match('/\d+$/', $attr->attr_value, $matches)) {

                                $goods[$i]->article = $matches[0];
                            }


                            $goods[$i]->set_gtins = $ar->set_gtins;
                        }

					if ($attr->attr_id == 13933) { // ето типа tnved_code
                           
                            $goods[$i]->tnved_code = $attr->attr_value;
                   
                    }
					 $goods[$i]->certificate_number = '';
							$goods[$i]->certificate_date = '';
					if ($attr->attr_id == 23557) { // ето типа certificate_number ЕАЭС N RU Д-RU.РА05.В.32276/24:::2024-06-24
							$parts = explode(":::", $attr->attr_value);
							$certificate_number = $parts[0];  // "ЕАЭС N RU Д-RU.РА05.В.32276/24"
							$certificate_date = $parts[1];
                            $goods[$i]->certificate_number = $certificate_number;
							$goods[$i]->certificate_date = $certificate_date;
                   
                    }


                    }
					
					
					
					
					
                    unset($goods[$i]->good_status);
                    unset($goods[$i]->good_detailed_status);
                    unset($goods[$i]->updated_date);

                    // break;
                }


            }
        }
        return $goods;
    }
    public function SetupStore($location, $name, $mode)
    {
        $store = new CPStore();
        $store->Open($location, $name, $mode);
        //	print_r($store);
        return $store;
    }
    public function SetupCertificates($location, $name, $mode)
    {
        $store = $this->SetupStore($location, $name, $mode);
        $certs = $store->get_Certificates();
        //	print_r($certs);
        //	echo '9999999999';
        return $certs;
    }
    public function SetupCertificate($location, $name, $mode, $find_type, $query, $valid_only,
        $number)
    {
        $certs = $this->SetupCertificates($location, $name, $mode);
        if (!is_null($find_type)) {
            $certs = $certs->Find($find_type, $query, $valid_only);
            return $certs->Item($number);
        } else {
            $cert = $certs->Item($number);
            return $cert;
        }
    }
}
