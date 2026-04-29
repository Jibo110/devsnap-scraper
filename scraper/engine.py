#!/usr/bin/env python3
"""
DEVSNAP Ticket Scraper — engine.py (ScaleSerp version)
Requires: pip install requests
Usage: python engine.py "Lakers tickets"
Output: JSON array only
"""

import sys
import json
import re
import requests

SCALESERP_KEY = "5D669235567C46C9AE0E8AF89EF6F472"

PRICE_RE = re.compile(r'\$\s*[\d,]+(?:\.\d{1,2})?')


def find_section(text: str) -> str:
    for word in ["Floor", "Court", "VIP", "Club", "Balcony",
                 "Lower", "Upper", "Section", "Row", "Pit", "GA", "Field"]:
        if re.search(word, text, re.I):
            return word
    return "General"


def scrape(query: str) -> list:
    results = []

    try:
        resp = requests.get(
            "https://api.scaleserp.com/search",
            params={
                "api_key":       SCALESERP_KEY,
                "q":             query + " tickets price buy",
                "location":      "United States",
                "google_domain": "google.com",
                "gl":            "us",
                "hl":            "en",
                "num":           "20",
                "output":        "json",
            },
            timeout=20,
        )
        data = resp.json()
    except Exception as e:
        print(json.dumps({"error": f"ScaleSerp request failed: {str(e)}"}))
        sys.exit(1)

    if data.get("request_info", {}).get("success") is False:
        msg = data.get("request_info", {}).get("message", "Unknown API error")
        print(json.dumps({"error": f"ScaleSerp API error: {msg}"}))
        sys.exit(1)

    for item in data.get("organic_results", []):
        title   = item.get("title",          "")
        snippet = item.get("snippet",        "")
        source  = item.get("displayed_link", item.get("domain", "google.com"))
        combined = title + " " + snippet

        prices = PRICE_RE.findall(combined)
        if not prices:
            continue

        section = find_section(combined)

        for price in prices[:2]:
            results.append({
                "event":   title[:80] or query,
                "section": section,
                "price":   price.strip(),
                "source":  source[:60],
            })

        if len(results) >= 20:
            break

    for ev in data.get("events_results", [])[:10]:
        title = ev.get("title", "")
        date  = ev.get("date",  "")
        venue = ev.get("address", "")

        for ticket in ev.get("ticket_info", []):
            price_raw = ticket.get("price", "")
            prices    = PRICE_RE.findall(price_raw)
            source    = ticket.get("source", "google.com")

            if prices:
                label = f"{title} — {venue} ({date})"[:80] if venue else title[:80]
                results.append({
                    "event":   label or query,
                    "section": "General",
                    "price":   prices[0],
                    "source":  source[:60],
                })

    seen   = set()
    unique = []
    for r in results:
        key = (r["event"][:40], r["price"])
        if key not in seen:
            seen.add(key)
            unique.append(r)

    return unique[:20]


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No query provided. Usage: python engine.py \"Lakers tickets\""}))
        sys.exit(1)

    query = " ".join(sys.argv[1:])

    try:
        result = scrape(query)
        print(json.dumps(result))
    except Exception as exc:
        print(json.dumps({"error": str(exc)}))
        sys.exit(1)
