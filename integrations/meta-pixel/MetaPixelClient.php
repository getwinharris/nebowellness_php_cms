<?php
namespace App\Integrations\MetaPixel;

/** The public layout supplies this destination only for opt-in measurement. */
final class MetaPixelClient {
    public function __construct(private string $pixelId) {}

    public function enabled(): bool {
        return (bool)preg_match('/^[0-9]{5,24}$/D', $this->pixelId);
    }

    public function pixelId(): string {
        return $this->enabled() ? $this->pixelId : '';
    }
}
