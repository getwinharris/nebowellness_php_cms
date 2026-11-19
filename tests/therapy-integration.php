<?php
declare(strict_types=1);

// Run only against an explicitly selected, disposable local database.
if (PHP_SAPI !== 'cli' || !in_array(getenv('BAPX_MYSQL_HOST'), ['localhost','127.0.0.1'], true)
    || !preg_match('/(?:_test|_fixture)$/D', (string)getenv('BAPX_MYSQL_DB')) || getenv('BAPX_TEST_MODE') === '1') {
    fwrite(STDERR, "Set the disposable local BAPX_MYSQL_* connection with a _test or _fixture database.\n");
    exit(2);
}
require __DIR__ . '/../app/bootstrap.php';

use App\Services\{DatabaseService, TherapySessionService};
$database = new DatabaseService(true);
$service = new TherapySessionService($database);
if (($argv[1] ?? '') === '--confirm-fixture') {
    if (!preg_match('/^t[a-f0-9]{16}[cd]$/D', $argv[2] ?? '')) exit(2);
    try {
        $service->change($argv[2], 'triage', ['revision'=>'1','status'=>'confirmed','assigned_to'=>'Fixture','assignee_id'=>'clinic','start'=>$argv[3],
            'duration'=>'25','timezone'=>'Asia/Kolkata','fee'=>'0']);
        exit(0);
    } catch (DomainException $error) {
        if ($error->getMessage() === 'That time overlaps a confirmed clinic session.') exit(3);
        throw $error;
    }
}
$prefix = 't' . bin2hex(random_bytes(8));
$ids = [$prefix.'a', $prefix.'b', $prefix.'c', $prefix.'d', $prefix.'e', $prefix.'f', $prefix.'g'];
$customer = 'customer-'.$prefix;
$base = new DateTimeImmutable('+3 years', new DateTimeZone('Asia/Kolkata'));
$slot = $base->setTime(9, 30)->format('Y-m-d\TH:i');
$triage = ['status'=>'approved', 'revision'=>'0', 'timezone'=>'Asia/Kolkata', 'assigned_to'=>'Test clinician','assignee_id'=>'clinic',
    'start'=>$slot,'duration'=>'50','fee'=>'124.50','fee_description'=>'Session fee','note'=>'Private fixture'];
function verifyTherapy(bool $value, string $message): void {
    if (!$value) throw new RuntimeException($message);
}
function rejectedTherapy(callable $operation): void {
    try { $operation(); } catch (DomainException) { return; }
    throw new RuntimeException('A rejected operation unexpectedly succeeded');
}
try {
    foreach ($ids as $index=>$id) {
        $database->upsert('contact_submissions', ['id'=>$id,'customer_id'=>$customer.($index >= 2 ? 'other' : ''),
            'campaign_slug'=>'fixture','campaign_title'=>'Fixture therapy','created_at'=>date('Y-m-d H:i:s')]);
    }
    $approved = $service->change($ids[0], 'triage', $triage);
    rejectedTherapy(fn()=>$service->change($ids[0], 'triage', $triage));
    $owned = $service->requests($customer);
    verifyTherapy(array_column($owned, 'id') === array_values(array_intersect(array_column($owned, 'id'), array_slice($ids,0,2))) && count($owned) === 2, 'Account isolation');
    verifyTherapy(!isset($owned[0]['notes']) && !isset($owned[1]['notes']), 'Private notes leaked');
    rejectedTherapy(fn()=>$service->change($ids[0], 'start_order', ['user_id'=>'wrong','mode'=>'test']));
    $service->change($ids[0], 'start_order', ['user_id'=>$customer,'mode'=>'test']);
    $order = ['id'=>'order_'.$prefix, 'receipt'=>$ids[0], 'currency'=>'INR','amount'=>12450];
    $service->change($ids[0], 'attach_order', compact('order'));
    $free = $triage;
    $free['fee'] = '0';
    $service->change($ids[1], 'triage', $free);
    $free['revision'] = '1';
    $free['status'] = 'confirmed';
    $service->change($ids[1], 'triage', $free);
    $payment = ['id'=>'pay_'.$prefix, 'order_id'=>$order['id'], 'currency'=>'INR', 'amount'=>12450, 'status'=>'captured'];
    foreach (['amount'=>12449, 'status'=>'authorized', 'currency'=>'USD', 'order_id'=>'wrong'] as $key=>$value) {
        rejectedTherapy(fn()=>$service->change($ids[0], 'capture', ['payment'=>array_replace($payment, [$key=>$value])]));
    }
    $paid = $service->change($ids[0], 'capture', compact('payment'));
    verifyTherapy($paid['payment_status'] === 'paid' && $paid['status'] === 'approved', 'A conflicting slot was confirmed');
    $again = $service->change($ids[0], 'capture', compact('payment'));
    verifyTherapy($again['revision'] === $paid['revision'], 'Duplicate capture mutated the session');
    $newTime = $triage;
    $newTime['status'] = 'confirmed';
    $newTime['revision'] = (string)$again['revision'];
    rejectedTherapy(fn()=>$service->change($ids[0], 'triage', $newTime));
    $newTime['start'] = $base->setTime(10,20)->format('Y-m-d\TH:i');
    $confirmed = $service->change($ids[0], 'triage', $newTime);
    verifyTherapy($confirmed['status'] === 'confirmed' && $confirmed['payment_id'] === $payment['id'], 'Paid request recovery failed');
    // Two separate PHP processes and MySQL connections contest the very same final slot.
    $race = $triage;
    $race['fee'] = '0';
    $race['start'] = $base->setTime(13,15)->format('Y-m-d\TH:i');
    $race['duration'] = '25';
    $jobs = [];
    foreach (array_slice($ids,2,2) as $id) $service->change($id, 'triage', $race);
    foreach (array_slice($ids,2,2) as $id) {
        $jobs[] = proc_open([PHP_BINARY, __FILE__, '--confirm-fixture', $id, $race['start']], [], $pipes);
    }
    $codes = array_map('proc_close', $jobs);
    sort($codes);
    verifyTherapy($codes === [0,3], 'Exactly one concurrent clinic session must win the slot');
    $different = $triage;
    $different['fee'] = '0';
    $different['assignee_id'] = 'consultant_independent';
    $service->change($ids[4],'triage',$different);
    $different['revision'] = '1';
    $different['status'] = 'confirmed';
    $service->change($ids[4],'triage',$different);
    $different['revision'] = '2';
    $different['start'] = $race['start'];
    rejectedTherapy(fn()=>$service->change($ids[4],'triage',$different));
    foreach (array_slice($ids,5) as $index => $id) {
        $offer = array_replace($triage, ['start'=>$base->setTime(16 + $index,0)->format('Y-m-d\TH:i')]);
        $service->change($id, 'triage', $offer);
        $checkout = ['user_id'=>$customer.'other','mode'=>'test'];
        $active = $service->change($id, 'start_order', $checkout);
        $providerOrder = ['order'=>array_replace($order, ['id'=>'order_'.$id,'receipt'=>$id])];
        if ($index === 1) $active = $service->change($id,'attach_order',$providerOrder);
        $replacement = array_replace($offer,['revision'=>(string)$active['revision'],'start'=>$base->setTime(18 + $index,0)->format('Y-m-d\TH:i')]);
        rejectedTherapy(fn()=>$service->change($id,'triage',$replacement));

        // Move the isolated fixture clock past the proposed time while retaining a real
        // durable order. The missing checkout_start represents records created before this fix.
        $past = intdiv(time(), 60) * 60 - 3600;
        $stored = array_replace($active, ['starts_at'=>$past,'ends_at'=>$past+3000]);
        unset($stored['checkout_start']);
        $database->delete('therapy_sessions',$id);
        $database->upsert('therapy_sessions',$stored);
        rejectedTherapy(fn()=>$service->change($id,'start_order',$checkout));
        rejectedTherapy(fn()=>$service->change($id,'triage',array_replace($replacement,['fee'=>'300'])));
        $moved = $service->change($id,'triage',$replacement);
        verifyTherapy($moved['fee_paise'] === $active['fee_paise'] && $moved['payment_mode'] === $active['payment_mode']
            && ($moved['order_id'] ?? '') === ($active['order_id'] ?? '') && $moved['payment_status'] === $active['payment_status'],
            'An expired checkout must retain the existing order, price, mode and payment state.');
        rejectedTherapy(fn()=>$service->change($id,'start_order',$checkout));
        rejectedTherapy(fn()=>$service->change($id,'start_order',$checkout + ['shown_start'=>(string)$past]));
        if ($index === 0) {
            // Simulate the old checkout finishing after the clinic's edit.
            $service->change($id,'attach_order',$providerOrder);
        } else {
            $resumed = $service->change($id,'start_order',$checkout + ['shown_start'=>(string)$moved['starts_at']]);
            verifyTherapy($resumed['order_id'] === $active['order_id'], 'Resuming must reuse the original order.');
        }
        $capture = ['payment'=>array_replace($payment,['id'=>'pay_'.$id,'order_id'=>'order_'.$id])];
        $paid = $service->change($id,'capture',$capture);
        verifyTherapy($paid['payment_status'] === 'paid' && $paid['status'] === ($index === 0 ? 'approved' : 'confirmed'),
            'Auto-confirmation requires checkout acceptance of the replacement time.');
        verifyTherapy($service->change($id,'capture',$capture)['revision'] === $paid['revision'], 'Duplicate late capture changed the request.');
    }
    echo "PASS abandoned ordering/pending checkout recovery, protected original fee/mode/order, stale acceptance and replayed late captures.\n";
    echo "PASS independent clinicians and customer overlap protection.\n";
    echo "PASS concurrent clinic slot reservation.\n";
    echo "PASS therapy persistence, customer isolation, stale edits, approval, capture validation, duplicate delivery, overlap and rescheduling.\n";
} finally {
    foreach ($ids as $id) {
        $database->delete('therapy_sessions', $id);
        $database->delete('contact_submissions', $id);
    }
}
