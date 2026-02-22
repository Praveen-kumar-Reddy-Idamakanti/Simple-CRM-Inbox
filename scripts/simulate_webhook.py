"""
Webhook Simulator
==================
Simulates webhook messages to test your Laravel endpoint without needing the mock server.
"""

import requests
import json
import time
import random

WEBHOOK_URL = "http://localhost:8000/api/webhook"
WEBHOOK_TOKEN = "secret123"

def create_instagram_message(sender_id, message_text):
    """Create an Instagram message payload"""
    return {
        "object": "instagram",
        "entry": [{
            "messaging": [{
                "sender": {"id": sender_id},
                "message": {"text": message_text}
            }]
        }]
    }

def create_facebook_message(sender_id, message_text):
    """Create a Facebook message payload"""
    return {
        "object": "page",
        "entry": [{
            "messaging": [{
                "sender": {"id": sender_id},
                "message": {"text": message_text}
            }]
        }]
    }

def create_image_message(sender_id, channel):
    """Create a message with image attachment"""
    payload = {
        "object": channel,
        "entry": [{
            "messaging": [{
                "sender": {"id": sender_id},
                "message": {
                    "text": "Check out this image!",
                    "attachments": [{
                        "type": "image",
                        "url": "https://example.com/image.jpg"
                    }]
                }
            }]
        }]
    }
    return payload

def send_webhook(payload):
    """Send webhook to Laravel endpoint"""
    headers = {
        "Content-Type": "application/json",
        "X-Webhook-Token": WEBHOOK_TOKEN
    }
    
    try:
        response = requests.post(WEBHOOK_URL, json=payload, headers=headers, timeout=10)
        return response.status_code, response.text
    except requests.exceptions.ConnectionError:
        return None, "Cannot reach Laravel server. Is it running?"
    except Exception as e:
        return None, f"Error: {e}"

def main():
    """Run webhook simulation"""
    print("🚀 Webhook Simulator Started")
    print(f"Target: {WEBHOOK_URL}")
    print("-" * 50)
    
    # Test messages
    test_messages = [
        ("ig_user_001", "Hello from Instagram!", create_instagram_message),
        ("fb_user_001", "Hi there from Facebook!", create_facebook_message),
        ("ig_user_002", "I need help with my order", create_instagram_message),
        ("fb_user_002", "When will my package arrive?", create_facebook_message),
        ("ig_user_001", "Thanks for the help!", create_instagram_message),
        ("ig_user_003", "Do you have this in stock?", create_instagram_message),
        ("fb_user_003", "What are your business hours?", create_facebook_message),
    ]
    
    for i, (sender_id, text, create_func) in enumerate(test_messages, 1):
        print(f"[{i}] Sending message from {sender_id}")
        
        # Create payload
        if "image" in text.lower() or random.random() < 0.1:  # 10% chance of image
            channel = "instagram" if sender_id.startswith("ig_") else "page"
            payload = create_image_message(sender_id, channel)
            message_type = "image"
        else:
            channel = "instagram" if sender_id.startswith("ig_") else "facebook"
            payload = create_func(sender_id, text)
            message_type = "text"
        
        # Send webhook
        status, response = send_webhook(payload)
        
        if status == 200:
            print(f"  ✅ Success ({message_type}): \"{text[:50]}...\"")
        elif status:
            print(f"  ❌ Failed ({status}): {response}")
        else:
            print(f"  ❌ Connection Error: {response}")
        
        time.sleep(1)  # Wait 1 second between messages
    
    print("\n🎉 Simulation completed!")
    print("\nCheck your Laravel application to verify:")
    print("- Contacts were created")
    print("- Conversations were created") 
    print("- Messages were stored")
    print("- Relationships work correctly")

if __name__ == "__main__":
    main()
