<?php
namespace App\Services {
    final class DatabaseService {
        public array $rows = ['old'=>['id'=>'old', 'razorpay_key_id'=>'rzp_test_older','razorpay_key_secret'=>'previous-payment',
            'agent_api_key'=>'previous-agent','smtp_password'=>'previous-mail','therapy_webhook_test'=>'previous-webhook']];
        public function read(string $table): array { return array_values($this->rows); }
        public function upsert(string $table, array $row, string $key): void { $this->rows[$row[$key]] = $row; }
    }
}
namespace {
    require __DIR__.'/../../app/bootstrap.php';
    use App\Services\{DatabaseService, SecretService};
    putenv('SECRET_STORAGE_KEY=synthetic-unit-test-key');
    $db = new DatabaseService();
    $service = new SecretService($db);
    $service->save(SecretService::integrationChanges(['razorpay_mode'=>'test','razorpay_test_key_id'=>'rzp_test_older','razorpay_test_key_secret'=>'',
        'ai_api_key'=>'','smtp_password'=>'','therapy_webhook_test'=>'','google_analytics_id'=>'G-ABCD123456']));
    $saved = $service->all();
    $ok = $saved['razorpay_key_secret'] === 'previous-payment' && $saved['smtp_password'] === 'previous-mail'
        && $saved['therapy_webhook_test'] === 'previous-webhook' && $saved['agent_api_key'] === 'previous-agent';
    $service->save(SecretService::integrationChanges(['ai_api_key'=>'new-agent','remove_credentials'=>['razorpay_test_key_secret','therapy_webhook_test']]));
    $saved = $service->all();
    $ok = $ok && $saved['ai_api_key'] === 'new-agent' && $saved['agent_api_key'] === '' && $saved['razorpay_key_secret'] === ''
        && $saved['razorpay_test_key_secret'] === '' && $saved['therapy_webhook_test'] === '' && $saved['smtp_password'] === 'previous-mail';
    putenv('AI_API_KEY=stale-environment-fallback');
    $service->save(SecretService::integrationChanges(['remove_credentials'=>['ai_api_key']]));
    $ok = $ok && !$service->getModelConfig()['configured'];
    exit($ok ? 0 : 1);
}
