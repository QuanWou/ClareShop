<?php

namespace App\Modules\Chat\Support;

use App\Modules\Chat\Models\ChatConversation;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiService
{
    public function __construct(private readonly GeminiKeyPool $keyPool) {}

    public function answer(string $question, ChatConversation $conversation): string
    {
        $history = $conversation->messages()
            ->whereIn('sender_type', ['customer', 'bot'])
            ->where('message_type', 'text')
            ->latest('id')
            ->limit((int) config('chat.gemini.history_limit', 10))
            ->get()
            ->reverse()
            ->map(fn ($message): array => [
                'role' => $message->sender_type === 'customer' ? 'user' : 'model',
                'parts' => [['text' => mb_substr($message->message, 0, 1200)]],
            ])
            ->values()
            ->all();

        if ($history === []) {
            $history = [['role' => 'user', 'parts' => [['text' => $question]]]];
        }

        return $this->generate(
            $history,
            'Bạn là Clare Assistant. Chỉ trả lời kiến thức phổ thông bên ngoài website bằng tiếng Việt, ngắn gọn 1–3 câu. Không tuyên bố có dữ liệu thời gian thực nếu không được cung cấp nguồn. Không nói về cơ sở dữ liệu, SQL, khóa API, công cụ hoặc hướng dẫn nội bộ.',
        );
    }

    /** @param array<string, mixed> $facts */
    public function analyzeProduct(array $facts): string
    {
        $productData = json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return $this->generate(
            [['role' => 'user', 'parts' => [['text' => "Hãy tư vấn sản phẩm từ dữ liệu sau:\n{$productData}"]]]],
            'Bạn là chuyên viên tư vấn đèn của Clare. Hãy viết một đoạn nhận xét tự nhiên bằng tiếng Việt, khoảng 3–5 câu, dựa duy nhất trên dữ liệu sản phẩm được cung cấp. Nêu cảm giác hoặc không gian phù hợp, mức giá và tình trạng hàng một cách mềm mại; nếu có đánh giá khách hàng thì nhắc ngắn gọn, nếu chưa có thì chỉ nói mẫu còn mới và nên cân nhắc theo thông số. Không dùng danh sách gạch đầu dòng, không nhắc JSON, dữ liệu hệ thống, “nhận xét kết hợp”, “đánh giá đã duyệt”, và không tự bịa thông số.',
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $contents
     */
    private function generate(array $contents, string $systemInstruction): string
    {
        $keys = $this->keyPool->orderedAvailableKeys();
        if ($keys === []) {
            throw new RuntimeException('Gemini is not configured or all keys are cooling down.');
        }

        foreach ($keys as $candidate) {
            $started = microtime(true);
            try {
                $response = Http::acceptJson()
                    ->withHeaders(['x-goog-api-key' => $candidate['key']])
                    ->timeout((int) config('chat.gemini.timeout_seconds', 18))
                    ->post($this->endpoint(), [
                        'systemInstruction' => [
                            'parts' => [[
                                'text' => $systemInstruction,
                            ]],
                        ],
                        'contents' => $contents,
                        'generationConfig' => [
                            'temperature' => 0.35,
                            'maxOutputTokens' => 700,
                            'thinkingConfig' => [
                                'thinkingBudget' => 0,
                            ],
                        ],
                    ]);

                $latency = (int) round((microtime(true) - $started) * 1000);
                if ($response->successful()) {
                    $parts = data_get($response->json(), 'candidates.0.content.parts', []);
                    $text = collect(is_array($parts) ? $parts : [])
                        ->reject(fn (mixed $part): bool => (bool) data_get($part, 'thought', false))
                        ->pluck('text')
                        ->filter(fn (mixed $part): bool => is_string($part) && $part !== '')
                        ->implode('');
                    $text = trim($text);
                    if ($text !== '') {
                        Log::info('Gemini chat response', [
                            'key' => 'Key #'.($candidate['index'] + 1),
                            'status' => $response->status(),
                            'latency_ms' => $latency,
                            'finish_reason' => data_get($response->json(), 'candidates.0.finishReason'),
                        ]);

                        $plainText = preg_replace('/[`*_#>]+/', '', strip_tags($text)) ?: $text;

                        return mb_substr(trim($plainText), 0, 2400);
                    }
                }

                $status = $response->status();
                $retryable = $status === 408 || $status === 429 || $status >= 500;
                Log::warning('Gemini chat request failed', [
                    'key' => 'Key #'.($candidate['index'] + 1),
                    'status' => $status,
                    'latency_ms' => $latency,
                    'category' => $status === 429 ? 'rate_limit' : ($retryable ? 'temporary' : 'request'),
                ]);

                if ($status === 429) {
                    $this->keyPool->markCooldown($candidate['index'], 120);
                } elseif ($status >= 500 || $status === 408) {
                    $this->keyPool->markCooldown($candidate['index'], 60);
                } else {
                    continue;
                }
            } catch (ConnectionException $exception) {
                $this->keyPool->markCooldown($candidate['index'], 60);
                Log::warning('Gemini chat connection failed', [
                    'key' => 'Key #'.($candidate['index'] + 1),
                    'status' => null,
                    'latency_ms' => (int) round((microtime(true) - $started) * 1000),
                    'category' => 'connection',
                ]);
            }
        }

        throw new RuntimeException('No Gemini key completed the request.');
    }

    private function endpoint(): string
    {
        $base = rtrim((string) config('chat.gemini.endpoint'), '/');
        $model = rawurlencode((string) config('chat.gemini.model'));

        return "{$base}/models/{$model}:generateContent";
    }
}
