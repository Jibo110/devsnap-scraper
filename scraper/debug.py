#!/usr/bin/env python3
"""
DEBUG script — run this to see exactly what each search engine returns.
"""
import requests
from bs4 import BeautifulSoup

HEADERS = {
    "User-Agent": (
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
        "AppleWebKit/537.36 (KHTML, like Gecko) "
        "Chrome/124.0.0.0 Safari/537.36"
    ),
    "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
    "Accept-Language": "en-US,en;q=0.9",
}

query = "Lakers tickets price"

print("=" * 60)
print("TEST 1: DuckDuckGo HTML")
print("=" * 60)
try:
    r = requests.get(
        "https://html.duckduckgo.com/html/",
        params={"q": query, "kl": "us-en"},
        headers=HEADERS,
        timeout=15
    )
    print(f"Status: {r.status_code}")
    soup = BeautifulSoup(r.text, "html.parser")
    results = soup.select(".result")
    print(f"Result blocks found: {len(results)}")
    for i, res in enumerate(results[:3]):
        print(f"\n--- Result {i+1} ---")
        print(res.get_text(separator=" | ", strip=True)[:300])
except Exception as e:
    print(f"ERROR: {e}")

print("\n" + "=" * 60)
print("TEST 2: Bing")
print("=" * 60)
try:
    r = requests.get(
        "https://www.bing.com/search",
        params={"q": query, "setlang": "en"},
        headers=HEADERS,
        timeout=15
    )
    print(f"Status: {r.status_code}")
    soup = BeautifulSoup(r.text, "html.parser")
    items = soup.select("li.b_algo")
    print(f"Result blocks found: {len(items)}")
    for i, item in enumerate(items[:3]):
        print(f"\n--- Result {i+1} ---")
        print(item.get_text(separator=" | ", strip=True)[:300])
except Exception as e:
    print(f"ERROR: {e}")

print("\n" + "=" * 60)
print("TEST 3: Raw page snippet (first 2000 chars of Bing HTML)")
print("=" * 60)
try:
    r = requests.get(
        "https://www.bing.com/search",
        params={"q": query, "setlang": "en"},
        headers=HEADERS,
        timeout=15
    )
    print(r.text[:2000])
except Exception as e:
    print(f"ERROR: {e}")
