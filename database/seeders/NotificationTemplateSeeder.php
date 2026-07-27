<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Global (company_id = null) default templates for every notification event and
 * channel. Companies can override any of these from the template manager.
 * Idempotent via firstOrCreate on (company_id, event_key, channel).
 */
class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as [$event, $channel, $name, $subject, $body]) {
            NotificationTemplate::firstOrCreate(
                ['company_id' => null, 'event_key' => $event, 'channel' => $channel],
                [
                    'ulid' => (string) Str::ulid(),
                    'name' => $name,
                    'subject' => $subject,
                    'body' => $body,
                    'is_active' => true,
                    'whatsapp_approval_status' => $channel === 'whatsapp' ? 'approved' : null,
                    'variables' => $this->tokensIn($subject.' '.$body),
                ]
            );
        }
    }

    private function tokensIn(string $text): array
    {
        preg_match_all('/\{\{\s*(\w+)\s*\}\}/', $text, $m);

        return array_values(array_unique($m[1]));
    }

    /** @return array<int, array{0:string,1:string,2:string,3:?string,4:string}> */
    private function templates(): array
    {
        return [
            // Welcome
            ['welcome', 'database', 'Welcome (in-app)', 'Welcome to {{company}}', 'Hi {{name}}, your registration is approved. Welcome aboard!'],
            ['welcome', 'mail', 'Welcome (email)', 'Welcome to {{company}}', "Hi {{name}},\n\nYour registration has been approved. You can now enroll in chitties and participate in auctions."],
            ['welcome', 'whatsapp', 'Welcome (WhatsApp)', null, 'Hi {{name}}, welcome to {{company}}! Your account is approved.'],

            // KYC
            ['kyc.approved', 'database', 'KYC approved (in-app)', 'KYC Verified', 'Hi {{name}}, your KYC has been verified. You can now join chitties.'],
            ['kyc.approved', 'mail', 'KYC approved (email)', 'Your KYC is verified', "Hi {{name}},\n\nGood news — your KYC is verified."],
            ['kyc.approved', 'whatsapp', 'KYC approved (WhatsApp)', null, 'Hi {{name}}, your KYC is verified ✅'],
            ['kyc.rejected', 'database', 'KYC rejected (in-app)', 'KYC Needs Attention', 'Hi {{name}}, a KYC document was rejected: {{reason}}. Please re-upload.'],
            ['kyc.rejected', 'mail', 'KYC rejected (email)', 'Action needed on your KYC', "Hi {{name}},\n\nA KYC document was rejected for the following reason: {{reason}}.\n\nPlease log in and upload a corrected document."],

            // Payment
            ['payment.received', 'database', 'Payment received (in-app)', 'Payment Received', 'Hi {{name}}, we received your payment of INR {{amount}} (receipt {{receipt_number}}).'],
            ['payment.received', 'mail', 'Payment received (email)', 'Payment received — INR {{amount}}', "Hi {{name}},\n\nWe have received your payment of INR {{amount}}.\nReference: {{reference}}\nReceipt: {{receipt_number}}"],
            ['payment.received', 'whatsapp', 'Payment received (WhatsApp)', null, 'Hi {{name}}, payment of INR {{amount}} received. Receipt {{receipt_number}}.'],

            // Installment reminders
            ['installment.due_reminder', 'database', 'Due reminder (in-app)', 'Installment Due Soon', 'Hi {{name}}, INR {{amount}} for chitty {{chitty}} is due on {{due_date}}.'],
            ['installment.due_reminder', 'mail', 'Due reminder (email)', 'Installment due on {{due_date}}', "Hi {{name}},\n\nA reminder that INR {{amount}} for chitty {{chitty}} is due on {{due_date}}."],
            ['installment.due_reminder', 'whatsapp', 'Due reminder (WhatsApp)', null, 'Reminder: INR {{amount}} for {{chitty}} due {{due_date}}.'],
            ['installment.overdue', 'database', 'Overdue (in-app)', 'Installment Overdue', 'Hi {{name}}, INR {{amount}} for chitty {{chitty}} is overdue (was due {{due_date}}).'],
            ['installment.overdue', 'whatsapp', 'Overdue (WhatsApp)', null, 'Hi {{name}}, INR {{amount}} for {{chitty}} is overdue. Please pay to avoid late fees.'],

            // Auctions
            ['auction.scheduled', 'database', 'Auction scheduled (in-app)', 'Auction Scheduled', 'An auction for {{chitty}} (period {{period}}) is scheduled for {{scheduled_at}}.'],
            ['auction.scheduled', 'whatsapp', 'Auction scheduled (WhatsApp)', null, 'Auction for {{chitty}} period {{period}} on {{scheduled_at}}. Join to bid!'],
            ['auction.result', 'database', 'Auction result (in-app)', 'Auction Result', 'The auction for {{chitty}} is closed. Your dividend this cycle: INR {{dividend}}.'],
            ['auction.winner', 'database', 'Auction winner (in-app)', 'You Won!', 'Congratulations {{name}}! You won the {{chitty}} auction. Prize: INR {{prize}}.'],
            ['auction.winner', 'mail', 'Auction winner (email)', 'You won the {{chitty}} auction', "Congratulations {{name}}!\n\nYou won the auction for chitty {{chitty}}. Prize amount: INR {{prize}}."],

            // Push (mobile)
            ['payment.received', 'push', 'Payment received (push)', 'Payment received', 'INR {{amount}} received. Receipt {{receipt_number}}.'],
            ['installment.due_reminder', 'push', 'Due reminder (push)', 'Installment due soon', 'INR {{amount}} for {{chitty}} is due on {{due_date}}.'],
            ['auction.scheduled', 'push', 'Auction scheduled (push)', 'Auction scheduled', '{{chitty}} auction on {{scheduled_at}}. Tap to join.'],
            ['auction.winner', 'push', 'Auction winner (push)', 'You won!', 'You won the {{chitty}} auction. Prize INR {{prize}}.'],

            // Support
            ['support.updated', 'database', 'Support update (in-app)', 'Support Ticket {{reference}}', '{{summary}} (status: {{status}}).'],
            ['support.updated', 'mail', 'Support update (email)', 'Update on ticket {{reference}}', "Hi {{name}},\n\n{{summary}}\n\nTicket: {{reference}} — {{subject}}\nStatus: {{status}}"],
        ];
    }
}
