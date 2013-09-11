<?php

include_once('KashooRequest.php');

$user = 'mailbox@example.com';
$pass = 'somepassword';
// business id pulled from kashoo
$business_id = 123456789;

KashooRequest::init($user, $pass);

$kash = new KashooRequest('businesses/' . $business_id . '/records/invoices');
$kash->parameters(array(
  'startDate'   => date('Y-m-d', strtotime("-6 weeks")),
  'endDate'     => date('Y-m-d', strtotime("now")),
  'limit'       => 10,
));
$result = $kash->request();

foreach($result as $invoice) {
  echo "Customer: " . $invoice->contactName . ", Balance Due: " . number_format($invoice->balanceDue / 100, 2);
}
