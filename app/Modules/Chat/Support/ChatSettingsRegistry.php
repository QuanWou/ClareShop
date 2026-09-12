<?php

namespace App\Modules\Chat\Support;

use App\Modules\Chat\Models\ChatSetting;
use Illuminate\Support\Facades\Schema;

class ChatSettingsRegistry
{
    /** @var array<string, string>|null */
    private ?array $values = null;

    /** @return array<string, string> */
    public function defaults(): array
    {
        return [
            'widget_enabled' => '1',
            'assistant_name' => 'Clare Assistant',
            'welcome_message' => 'Xin chào 👋 Mình có thể giúp bạn tìm đèn, kiểm tra đơn hàng hoặc hỗ trợ thanh toán.',
            'handoff_message' => 'Mình đang kết nối bạn với nhân viên hỗ trợ.',
            'gemini_unavailable_message' => 'Hiện tại trợ lý thông tin đang bận. Bạn có thể thử lại sau hoặc mình có thể kết nối bạn với nhân viên hỗ trợ.',
            'guest_order_message' => 'Bạn cần đăng nhập để mình kiểm tra đơn hàng riêng của bạn.',
            'closed_message' => 'Cuộc trò chuyện đã đóng. Bạn có thể gửi tin mới để bắt đầu lại.',
            'admin_unavailable_message' => 'Nhân viên sẽ phản hồi sớm nhất khi quay lại trực tuyến.',
            'gemini_enabled' => '1',
            'external_questions_enabled' => '1',
        ];
    }

    public function get(string $key): string
    {
        return $this->all()[$key] ?? $this->defaults()[$key] ?? '';
    }

    public function enabled(string $key): bool
    {
        return filter_var($this->get($key), FILTER_VALIDATE_BOOLEAN);
    }

    /** @return array<string, string> */
    public function all(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        $stored = Schema::hasTable('chat_settings')
            ? ChatSetting::query()->pluck('value', 'key')->all()
            : [];

        return $this->values = array_merge($this->defaults(), $stored);
    }
}
