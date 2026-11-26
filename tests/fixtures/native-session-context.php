<?php
declare(strict_types=1);

// Synthetic but fully schema-conforming support context for transport fixtures.
function nativeSessionFixture(string $title='Public synthetic data'): array {
    return [
        'signed_in'=>false,'account_access'=>false,'shop_enabled'=>false,
        'orders'=>[],'cart'=>[],'articles'=>[],
        'site'=>['pages'=>['contact'=>'/contact'],'campaigns'=>[
            ['title'=>$title,'summary'=>'Fixture only','url'=>'/campaigns/fixture'],
        ],'programmes'=>[],'products'=>[],
        'support_scope'=>'Public synthetic record'],
        'contact_channels'=>['phones'=>[],'whatsapp'=>[]],
    ];
}
