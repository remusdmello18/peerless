<?php declare(strict_types=1);

use Sprain\SwissQrBill as QrBill;

require __DIR__ . '/vendor/autoload.php';


     $IBAN = $_POST["IBAN"];
     $receiverName = $_POST["receivername"];
     $rAddress = $_POST["raddress"];
     $rZip = $_POST["rzip"];
     $rCity = $_POST["rcity"];
     $rCountry = $_POST["rcountry"];
     $referenceNumber = $_POST["referencenumber"];

     $senderName = $_POST["sendername"];
     $sAddress = $_POST["saddress"];
     $sZip = $_POST["szip"];
     $sCity = $_POST["scity"];
     $sCountry = $_POST["scountry"];
   

     $creditorbody1 = ' <style>
                            p
                                {
                                                margin:0;   
                                                font-family: Arial, Helvetica, sans-serif;
                                }
                        </style>
                        <div>
                            <p><img src="Logo.png" width="150" height="150"> &nbsp;</p>&nbsp;
                                                <p>'.$receiverName.'</p>
                                                <p>'.$rAddress.'</p>
                                                <p>'.$rZip.'</p>
                                                <p>'.$rCity.'</p>
                                                <p>'.$rCountry.'</p>
                                                &nbsp;
                        </div>';

     $body1 = ' <style>
                    #debtor
                        {
                                text-align  : right;
                        }
                    p
                        {
                                margin:0;   
                                font-family: Arial, Helvetica, sans-serif;
                        }
            
                </style>
                <div id="debtor">
                        <p>'.$senderName.'</p>
                        <p>'.$sAddress.'</p>
                        <p>'.$sZip.'</p>
                        <p>'.$sCity.'</p>
                        <p>'.$sCountry.'</p>
                        &nbsp;
                </div>';
     $invnum = rand(1000,10000);
     $day = date("D");;
     $date = date('d-m-Y');;
     $datebody = '<div>
                     <p style="text-align: left; font-family: Arial, Helvetica, sans-serif; font-size:25px">
                         <b>Invoice No. '.$invnum.'</b>
                     </p>
                     <p style="text-align: right">'.$day.' '.$date.'</p>
                  </div>';

     $tempTable = '<style>
                     td 
                         {
                             text-align: left;
                             padding-top: 8px;
                             padding-bottom: 8px;
                         }
                   </style>
                   <table class="table" style="width:100%" border=0 border-style=solid>';
     $tempTable1 ='<tr style = "background-color:LightGray;">
                         <td>
                             <b>Position</b>
                         </td>
                         <td>
                             <b>Count</b>
                         </td>
                         <td>
                             <b>Label</b>
                         </td>
                         <td>
                             <b>Total</b>
                         </td>
                   </tr>';
     $tempTable2 = "";
     $sum = 0;
         for ($a = 0; $a < count($_POST["Label"]); $a++)
             {
                 $b = $a + 1;
                 $tempTable2.='<tr><td>'.$b.'</td><td>'. $_POST["Count"][$a].' Std.</td><td>'. $_POST["Label"][$a] .' </td><td> CHF '. $_POST["Total"][$a] .'</td></tr>';        
                 $val = $_POST["Total"][$a];          
                 $sum += $val; 
             }    
 
      $vatamount = $sum * 0.077;
      $totalSum = $sum + $vatamount;
      $totalTag ='<tr><td></td><td></td><td><b>Total Amount</b></td><td><b>CHF '.$sum.'</b></td></tr>
                  <tr><td></td><td></td><td>VAT</td><td>7.7%</td></tr>
                  <tr><td></td><td></td><td>VAT Amount</td><td>CHF '.$vatamount.'</td></tr>
                  <tr><td></td><td></td><td><b>Total Payable Amount</b></td><td><b>CHF '.$totalSum.'</b></td></tr>
                  &nbsp;
                  &nbsp;
                 </table>';

// Create a new instance of QrBill, containing default headers with fixed values
    $qrBill = QrBill\QrBill::create();

// Add creditor information
// Who will receive the payment and to which bank account?
    $qrBill->setCreditor(
        QrBill\DataGroup\Element\CombinedAddress::create(
            $receiverName,
            $rAddress,
            $rZip .' '. $rCity,
            $rCountry
        ));
    
    $qrBill->setCreditorInformation(
        QrBill\DataGroup\Element\CreditorInformation::create(
            //'CH4431999123000889012' // This is a special QR-IBAN. Classic IBANs will not be valid here.
            $IBAN
        ));

// Add debtor information
// Who has to pay the invoice? This part is optional.
// Notice how you can use two different styles of addresses: CombinedAddress or StructuredAddress.
// They are interchangeable for creditor as well as debtor.
    $qrBill->setUltimateDebtor(
        QrBill\DataGroup\Element\StructuredAddress::createWithStreet(
            $senderName,
            $sAddress,
            ' ',
            $sZip,
            $sCity,
            $sCountry
        ));

// Add payment amount information
// What amount is to be paid?
    $qrBill->setPaymentAmountInformation(
        QrBill\DataGroup\Element\PaymentAmountInformation::create(
            'CHF',
            $totalSum
        ));

// Add payment reference
// This is what you will need to identify incoming payments.
    $referenceNumber = QrBill\Reference\QrPaymentReferenceGenerator::generate(
        '210000',  // You receive this number from your bank (BESR-ID). Unless your bank is PostFinance, in that case use NULL.
        '313947143000901' // A number to match the payment with your internal data, e.g. an invoice number
    );
    
    $qrBill->setPaymentReference(
        QrBill\DataGroup\Element\PaymentReference::create(
            QrBill\DataGroup\Element\PaymentReference::TYPE_QR,
            $referenceNumber
        ));



// Now get the QR code image and save it as a file.
    try {
        $qrBill->getQrCode()->writeFile(__DIR__ . '/qr.png');
        $qrBill->getQrCode()->writeFile(__DIR__ . '/qr.svg');
    } catch (Exception $e) {
        foreach($qrBill->getViolations() as $violation) {
            print $violation->getMessage()."\n";
        }
        exit;
    }
    
    $output = new QrBill\PaymentPart\Output\HtmlOutput\HtmlOutput($qrBill, 'en');
    
    $html = $output
        ->setPrintable(false)
        ->getPaymentPart();
    $html = $creditorbody1 . $body1 . $datebody . $tempTable .  $tempTable1 . $tempTable2 . $totalTag . $html;
    $examplePath = __DIR__ . '/html-example.htm';
    file_put_contents($examplePath, $html);
    print 'HTML example created here: ' . $examplePath;



/*$html = file_get_contents("html-example.htm"); 
require_once __DIR__ . '/vendor/autoload.php';
    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML($html);    
    $mpdf->Output();   
*/

