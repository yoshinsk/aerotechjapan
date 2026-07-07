<?php
/**
 * httpdocs/app/Mailer.php
 * 問い合わせ内容を管理者へメール送信し、失敗時はログへ退避します。
 */

declare(strict_types=1);

final class Mailer
{
    public function sendInquiry(array $inquiry, ?array $product): void
    {
        $to = config_value('mail.to', 'rando@aero-tech.co.jp');
        $from = config_value('mail.from', 'no-reply@aero-tech.co.jp');
        $subject = '【AERO TECH JAPAN】Web問い合わせ';
        if ($product) {
            $subject .= ' / ' . ($product['name_ja'] ?? $product['slug']);
        }

        $body = $this->buildInquiryBody($inquiry, $product);
        $headers = [
            'From: ' . $from,
            'Reply-To: ' . $inquiry['email'],
            'Content-Type: text/plain; charset=UTF-8',
        ];

        $sent = mb_send_mail($to, $subject, $body, implode("\r\n", $headers));
        if (!$sent) {
            $this->writeFallbackLog($subject, $body);
        }
    }

    private function buildInquiryBody(array $inquiry, ?array $product): string
    {
        $lines = [
            'Webサイトから問い合わせがありました。',
            '',
            '対象商品: ' . ($product ? (($product['name_ja'] ?? '') . ' / ' . ($product['slug'] ?? '')) : '指定なし'),
            '氏名: ' . $inquiry['name'],
            '会社名: ' . ($inquiry['company'] ?: '-'),
            '国/地域: ' . ($inquiry['country'] ?: '-'),
            'メール: ' . $inquiry['email'],
            '電話: ' . ($inquiry['phone'] ?: '-'),
            '表示言語: ' . $inquiry['locale'],
            '',
            '内容:',
            $inquiry['message'],
            '',
            'IP: ' . $inquiry['ip_address'],
            'User-Agent: ' . $inquiry['user_agent'],
        ];
        return implode("\n", $lines);
    }

    private function writeFallbackLog(string $subject, string $body): void
    {
        $dir = config_value('app.log_root');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $entry = '[' . date('c') . "] {$subject}\n{$body}\n\n";
        file_put_contents($dir . '/mail-fallback.log', $entry, FILE_APPEND | LOCK_EX);
    }
}
