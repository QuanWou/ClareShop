<?php

namespace App\Modules\Settings\Actions;

use App\Modules\Settings\Support\SiteSettingsRegistry;
use Illuminate\Support\Facades\Mail;

class ConfigureStoreMailAction
{
    public function __construct(private readonly SiteSettingsRegistry $settings) {}

    public function execute(): void
    {
        if (! $this->settings->configured('smtp_host')) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $this->settings->get('smtp_host'),
            'mail.mailers.smtp.port' => (int) $this->settings->get('smtp_port'),
            'mail.mailers.smtp.scheme' => $this->settings->get('smtp_encryption') === 'ssl' ? 'smtps' : null,
            'mail.mailers.smtp.username' => $this->settings->get('smtp_username') ?: null,
            'mail.mailers.smtp.password' => $this->settings->get('smtp_password') ?: null,
            'mail.from.address' => $this->settings->get('mail_from_address'),
            'mail.from.name' => $this->settings->get('mail_from_name'),
        ]);
        Mail::purge('smtp');
    }
}
