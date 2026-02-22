<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class AiService
{
    /**
     * Get a suggested reply or auto-reply for a conversation
     * (Currently using Advanced Local Intelligence)
     */
    public function getSuggestion(Conversation $conversation, string $lastMessageText): ?string
    {
        Log::info('Generating intelligent auto-response locally', [
            'text' => $lastMessageText,
            'conversation_id' => $conversation->_id
        ]);

        return $this->generateSimulationReply($lastMessageText);
    }

    /**
     * Advanced Keyword-Based Intelligence Engine
     */
    private function generateSimulationReply(string $text): string
    {
        $text = strtolower($text);
        
        // 1. Pricing & Plans
        if (preg_match('/price|cost|how much|payment|annual|bill/', $text)) {
            if (str_contains($text, 'annual')) {
                return "We offer a 20% discount on annual plans! Our Pro plan is $49/mo billed monthly, or $39/mo billed annually.";
            }
            if (str_contains($text, 'payment')) {
                return "We accept all major credit cards, PayPal, and bank transfers for annual contracts. Which method do you prefer?";
            }
            return "Our standard plan starts at $29/month. We also have a Pro plan for growing teams. Would you like a link to our pricing page?";
        }

        // 2. Beginners / Ease of Use
        if (preg_match('/beginner|easy|support|help|setup|started/', $text)) {
            if (str_contains($text, 'beginner') || str_contains($text, 'suitable')) {
                return "Absolutely! Revio is designed for simplicity. It takes less than 10 minutes to set up, and we provide a guided tour for beginners.";
            }
            return "Getting started is a breeze! I can send you a 2-minute setup video, or we can schedule a quick walkthrough call.";
        }

        // 3. Business Type / Suitability
        if (preg_match('/small business|agency|enterprise|work with/', $text)) {
            return "We specialize in helping small to medium businesses scale their customer support. We currently support over 500+ businesses globally!";
        }

        // 4. Social Proof / Testimonials
        if (preg_match('/testimonial|review|case study|happy customer/', $text)) {
            return "We have many happy customers! You can check out our testimonials here: revio.com/reviews or I can tell you about a similar business using us.";
        }

        // 5. Refunds & Guarantees
        if (preg_match('/refund|money back|cancel|guarantee/', $text)) {
            return "We offer a 30-day money-back guarantee. If you're not 100% satisfied, just let us know and we'll process your refund immediately, no questions asked!";
        }

        // 6. Shipping & Logistics
        if (preg_match('/ship|deliver|international|how long/', $text)) {
            return "We ship worldwide! International delivery typically takes 2-5 business days depending on your location. Standard shipping is free on all annual plans!";
        }

        // 7. Free / Trial
        if (preg_match('/free|trial|demo/', $text)) {
            return "We offer a 14-day full-access trial. No credit card is required to start. Would you like the sign-up link?";
        }

        // 8. Greetings
        if (preg_match('/hi|hello|hey|greetings/', $text)) {
            $greetings = [
                "Hello! I'm your AI assistant. How's your day going?",
                "Hey there! Reach out if you have any questions about our CRM.",
                "Hi! I'm here to help. What can I do for you today?"
            ];
            return $greetings[array_rand($greetings)];
        }

        // 9. Gratitude
        if (preg_match('/thank|thanks|great|cool/', $text)) {
            return "You're very welcome! I'm here if you need anything else.";
        }

        // 10. General Fallback with Context Aware phrases
        $fallbacks = [
            "That's a great question. I've notified our team to give you a detailed answer. Is there anything else I can help with in the meantime?",
            "I'm still learning, but I've passed this to a human specialist. They usually respond within 30 minutes!",
            "Got it! While you wait for an agent, feel free to ask me anything else about our features."
        ];
        
        return $fallbacks[array_rand($fallbacks)];
    }
}
