<?php require_once('core/get_token.php');?>
<?php
//SiteID must also be set in the Request's XML
//SiteID = 0  (US) - UK = 3, Canada = 2, Australia = 15, ....
//SiteID Indicates the eBay site to associate the call with
//$siteID = 0;
//the call being made:
$verb = 'GetOrders';

//Time with respect to GMT
//by default retreive orders in last 30 minutes
$CreateTimeFrom = gmdate("Y-m-d\TH:i:s",strtotime("-3 month")); //current time minus 30 minutes
$CreateTimeTo = gmdate("Y-m-d\TH:i:s");

//If you want to hard code From and To timings, Follow the below format in "GMT".
//$CreateTimeFrom = YYYY-MM-DDTHH:MM:SS; //GMT
//$CreateTimeTo = YYYY-MM-DDTHH:MM:SS; //GMT

///Build the request Xml string
$requestXmlBody = '<?xml version="1.0" encoding="utf-8" ?>';
$requestXmlBody .= '<GetOrdersRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
$requestXmlBody .= '<DetailLevel>ReturnAll</DetailLevel>';
$requestXmlBody .= "<CreateTimeFrom>$CreateTimeFrom</CreateTimeFrom><CreateTimeTo>$CreateTimeTo</CreateTimeTo>";
$requestXmlBody .= '<OrderRole>Seller</OrderRole><OrderStatus>Active</OrderStatus>';
$requestXmlBody .= "<RequesterCredentials><eBayAuthToken>$Token</eBayAuthToken></RequesterCredentials>";
$requestXmlBody .= '</GetOrdersRequest>';

//Create a new eBay session with all details pulled in from included keys.php
$session = new eBaySession($Token, $devID, $appID, $certID, $serverUrl, $compatabilityLevel, $siteID, $verb);

//send the request and get response
$responseXml = $session->sendHttpRequest($requestXmlBody);
if (stristr($responseXml, 'HTTP 404') || $responseXml == '')
    die('<P>Error sending request');

//Xml string is parsed and creates a DOM Document object
$responseDoc = new DomDocument();
$responseDoc->loadXML($responseXml);

//get any error nodes
$errors = $responseDoc->getElementsByTagName('Errors');
$response = simplexml_import_dom($responseDoc);
$entries = $response->PaginationResult->TotalNumberOfEntries;

echo 'Xml Response : <br><textarea rows="22" cols="70">'.$responseXml.'</textarea><br>';

//if there are error nodes
if ($errors->length > 0) {
    echo '<P><B>eBay returned the following error(s):</B>';
    //display each error
    //Get error code, ShortMesaage and LongMessage
    $code = $errors->item(0)->getElementsByTagName('ErrorCode');
    $shortMsg = $errors->item(0)->getElementsByTagName('ShortMessage');
    $longMsg = $errors->item(0)->getElementsByTagName('LongMessage');

    //$HasMoreOrders = $errors->item(0)->getElementsByTagName('HasMoreOrders');
    //echo $HasMoreOrders;

    //Display code and shortmessage
    echo '<P>', $code->item(0)->nodeValue, ' : ', str_replace(">", "&gt;", str_replace("<", "&lt;", $shortMsg->item(0)->nodeValue));
    
    //if there is a long message (ie ErrorLevel=1), display it
    if (count($longMsg) > 0)
        echo '<BR>', str_replace(">", "&gt;", str_replace("<", "&lt;", $longMsg->item(0)->nodeValue));
}else { //If there are no errors, continue
    if(isset($_GET['debug']))
    {  
       //header("Content-type: text/xml");
       //print_r($responseXml);
    }else
     {  //$responseXml is parsed in view.php
        echo '<br>';
        echo 'HasMoreOrders : ' . $response->HasMoreOrders;
        echo '<br><br>';
        include_once 'view_orders.php';
    }
} 
?>