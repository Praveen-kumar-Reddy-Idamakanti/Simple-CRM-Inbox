"""
Intern Evaluation - Webhook Poller
====================================
Polls the mock server for pending messages and forwards them
as webhook POSTs to your local Laravel app.

Usage:
    pip install requests
    python poller.py --token <your_token> --target http://localhost:8000

Arguments:
    --token     Your assigned intern token (required)
    --target    Base URL of your local Laravel app (default: http://localhost:8000)
    --interval  Poll interval in seconds (default: 5)

Dependencies:
    - requests: HTTP library for API calls
"""

import time
import argparse
import sys
from urllib.parse import urlparse, urljoin
from typing import List, Dict, Tuple, Optional

try:
    import requests
except ImportError:
    print("Error: 'requests' library is required. Install it using: pip install requests")
    sys.exit(1)


MOCK_SERVER = "https://mock-simulation.omts.in"
WEBHOOK_HEADER = "X-Webhook-Token"
WEBHOOK_TOKEN = "secret123"


class HTTPClient:
    """
    HTTP client for interacting with the mock server and forwarding webhooks.
    
    Attributes:
        api_key (str): API key for authenticating with mock server
        base_url (str): Base URL of the mock server
        session (requests.Session): Persistent HTTP session for connection pooling
    """
    
    def __init__(self, api_key: str):
        """
        Initialize HTTP client with API key.
        
        Args:
            api_key (str): Your assigned API token for mock server authentication
        """
        self.api_key = api_key
        self.base_url = MOCK_SERVER
        self.session = requests.Session()
        self.session.headers.update({"X-Api-Key": api_key})
        
    def fetch_pending_messages(self) -> List[Dict]:
        """
        Fetch pending messages from the mock server.
        
        Returns:
            List[Dict]: List of pending message payloads
            
        Raises:
            requests.exceptions.RequestException: If request fails
            
        Example:
            >>> client = HTTPClient("your_token")
            >>> messages = client.fetch_pending_messages()
            >>> print(f"Fetched {len(messages)} messages")
        """
        url = f"{self.base_url}/messages/pending"
        resp = self.session.get(url, timeout=10)
        resp.raise_for_status()
        return resp.json().get("messages", [])
    
    def forward_webhook(self, target_url: str, payload: Dict) -> int:
        """
        Forward webhook payload to target Laravel application.
        
        Args:
            target_url (str): Base URL of the Laravel app
            payload (Dict): Message payload to forward
            
        Returns:
            int: HTTP status code from the webhook endpoint
            
        Raises:
            requests.exceptions.RequestException: If request fails
            
        Example:
            >>> client = HTTPClient("your_token")
            >>> status = client.forward_webhook("http://localhost:8000", message_payload)
            >>> print(f"Webhook returned status: {status}")
        """
        webhook_url = self._build_webhook_url(target_url)
        headers = {
            "Content-Type": "application/json",
            WEBHOOK_HEADER: WEBHOOK_TOKEN
        }
        resp = requests.post(webhook_url, json=payload, headers=headers, timeout=10)
        return resp.status_code
    
    def _build_webhook_url(self, target_url: str) -> str:
        """
        Build complete webhook URL from base target URL.
        
        Args:
            target_url (str): Base URL (with or without scheme)
            
        Returns:
            str: Complete webhook URL with /api/webhook path
            
        Example:
            >>> client._build_webhook_url("localhost:8000")
            'http://localhost:8000/api/webhook'
            >>> client._build_webhook_url("https://example.com")
            'https://example.com/api/webhook'
        """
        if not target_url.startswith(("http://", "https://")):
            target_url = f"http://{target_url}"
        
        parsed = urlparse(target_url)
        path = parsed.path.rstrip("/") + "/api/webhook"
        
        return f"{parsed.scheme}://{parsed.netloc}{path}"


def extract_message_info(payload: Dict) -> Tuple[str, str, str]:
    """
    Extract relevant information from message payload for logging.
    
    Args:
        payload (Dict): Message payload from mock server
        
    Returns:
        Tuple[str, str, str]: (channel, sender_id, message_text)
        
    Example:
        >>> payload = {"object": "instagram", "entry": [{"messaging": [...]}]}
        >>> channel, sender, text = extract_message_info(payload)
        >>> print(f"Channel: {channel}, Sender: {sender}")
    """
    channel = payload.get("object", "unknown")
    
    sender_id = "?"
    text = ""
    
    entries = payload.get("entry", [])
    if entries and isinstance(entries, list) and len(entries) > 0:
        messaging_list = entries[0].get("messaging", [])
        if messaging_list and isinstance(messaging_list, list) and len(messaging_list) > 0:
            messaging = messaging_list[0]
            sender_id = messaging.get("sender", {}).get("id", "?")
            text = messaging.get("message", {}).get("text", "")
    
    return channel, sender_id, text


def get_channel_display(channel: str) -> str:
    """
    Get display name for channel.
    
    Args:
        channel (str): Channel identifier from payload
        
    Returns:
        str: Display name in uppercase (maps 'page' to 'FACEBOOK')
        
    Example:
        >>> get_channel_display("instagram")
        'INSTAGRAM'
        >>> get_channel_display("page")
        'FACEBOOK'
    """
    if not channel:
        return "UNKNOWN"
    
    normalized = channel.lower()
    if normalized == "page":
        return "FACEBOOK"
    
    return channel.upper()


def is_connection_error(error: Exception) -> bool:
    """
    Check if error is connection-related.
    
    Args:
        error (Exception): Exception to check
        
    Returns:
        bool: True if connection error, False otherwise
    """
    if isinstance(error, requests.exceptions.ConnectionError):
        return True
    
    error_str = str(error).lower()
    return "connection refused" in error_str or "connection error" in error_str


def poll(token: str, target: str, interval: int) -> None:
    """
    Main polling loop that continuously fetches and forwards messages.
    
    This function runs indefinitely, polling the mock server at regular intervals
    and forwarding any new messages to the Laravel webhook endpoint.
    
    Args:
        token (str): API token for mock server authentication
        target (str): Base URL of Laravel app (e.g., http://localhost:8000)
        interval (int): Polling interval in seconds
        
    Example:
        >>> poll(token="your_token", target="http://localhost:8000", interval=5)
        [Poller] Started. Polling https://mock-simulation.omts.in every 5s
        [Poller] Forwarding webhooks to http://localhost:8000/api/webhook
        --------------------------------------------------
        [✓] [INSTAGRAM] ig_52e5c8ecf7: "Hello there"
    """
    client = HTTPClient(token)
    
    print(f"[Poller] Started. Polling {MOCK_SERVER} every {interval}s")
    print(f"[Poller] Forwarding webhooks to {target}/api/webhook")
    print("-" * 50)
    
    while True:
        try:
            messages = client.fetch_pending_messages()
            
            if messages:
                for payload in messages:
                    channel, sender_id, text = extract_message_info(payload)
                    
                    try:
                        status_code = client.forward_webhook(target, payload)
                        
                        if status_code == 200:
                            print(f"[✓] [{get_channel_display(channel)}] {sender_id}: \"{text}\"")
                            # Add a small delay between messages to prevent bursts
                            time.sleep(5)
                        else:
                            print(f"[✗] [{get_channel_display(channel)}] {sender_id}: webhook returned {status_code}")
                    
                    except requests.exceptions.ConnectionError:
                        print(f"[!] Cannot reach {target}. Is your Laravel app running?")
                    except Exception as e:
                        if is_connection_error(e):
                            print(f"[!] Cannot reach {target}. Is your Laravel app running?")
                        else:
                            print(f"[!] Error forwarding message: {e}")
        
        except requests.exceptions.ConnectionError:
            print(f"[!] Cannot reach mock server at {MOCK_SERVER}. Retrying...")
        except requests.exceptions.RequestException as e:
            print(f"[!] Mock server error: {e}. Retrying...")
        except Exception as e:
            print(f"[!] Unexpected error: {e}")
        
        time.sleep(interval)


def main() -> None:
    """
    Entry point for the poller script.
    Parses command-line arguments and starts the polling loop.
    """
    parser = argparse.ArgumentParser(
        description="Webhook poller for intern evaluation",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  python poller.py --token abc123 --target http://localhost:8000
  python poller.py --token abc123 --target http://localhost:8000 --interval 3
        """
    )
    
    parser.add_argument(
        "--token",
        required=True,
        help="Your assigned intern token (required)"
    )
    parser.add_argument(
        "--target",
        default="http://localhost:8000",
        help="Your Laravel app base URL (default: http://localhost:8000)"
    )
    parser.add_argument(
        "--interval",
        type=int,
        default=5,
        help="Poll interval in seconds (default: 5)"
    )
    
    args = parser.parse_args()
    
    try:
        poll(args.token, args.target, args.interval)
    except KeyboardInterrupt:
        print("\n[Poller] Stopped by user")
        sys.exit(0)


if __name__ == "__main__":
    main()
