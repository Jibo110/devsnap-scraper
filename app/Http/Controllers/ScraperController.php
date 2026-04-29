<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class ScraperController extends Controller
{
    private string $scaleserpKey = '5D669235567C46C9AE0E8AF89EF6F472';
    private int    $timeoutSeconds = 30;

    // GET /scraper
    public function index()
    {
        return view('scraper.dashboard');
    }

    // POST /scraper/run
    public function run(Request $request): JsonResponse
    {
        // 1. Validate ─────────────────────────────────────────────────────────
        $validator = Validator::make($request->all(), [
            'query' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid query: ' . $validator->errors()->first(),
                'results' => [],
            ], 422);
        }

        $query = trim($request->input('query'));

        // 2. Block malicious input ─────────────────────────────────────────────
        if (preg_match('/[`$(){}|;&<>]/', $query)) {
            return response()->json([
                'success' => false,
                'message' => 'Query contains disallowed characters.',
                'results' => [],
            ], 422);
        }

        // 3. Call ScaleSerp API directly from PHP ─────────────────────────────
        try {
            $response = Http::timeout($this->timeoutSeconds)
                ->get('https://api.scaleserp.com/search', [
                    'api_key'       => $this->scaleserpKey,
                    'q'             => $query . ' tickets price buy',
                    'location'      => 'United States',
                    'google_domain' => 'google.com',
                    'gl'            => 'us',
                    'hl'            => 'en',
                    'num'           => '20',
                    'output'        => 'json',
                ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'API request failed: ' . $e->getMessage(),
                'results' => [],
            ], 500);
        }

        if (! $response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'ScaleSerp returned HTTP ' . $response->status(),
                'results' => [],
            ], 500);
        }

        $data = $response->json();

        if (isset($data['request_info']['success']) && $data['request_info']['success'] === false) {
            return response()->json([
                'success' => false,
                'message' => 'ScaleSerp API error: ' . ($data['request_info']['message'] ?? 'Unknown error'),
                'results' => [],
            ], 500);
        }

        // 4. Parse organic results ─────────────────────────────────────────────
        $results = [];
        $priceRe = '/\$\s*[\d,]+(?:\.\d{1,2})?/';
        $sections = ['Floor','Court','VIP','Club','Balcony','Lower','Upper','Section','Row','Pit','GA','Field'];

        foreach ($data['organic_results'] ?? [] as $item) {
            $title    = $item['title']    ?? '';
            $snippet  = $item['snippet']  ?? '';
            $source   = $item['displayed_link'] ?? ($item['domain'] ?? 'google.com');
            $combined = $title . ' ' . $snippet;

            preg_match_all($priceRe, $combined, $matches);
            $prices = $matches[0] ?? [];

            if (empty($prices)) continue;

            $section = 'General';
            foreach ($sections as $word) {
                if (stripos($combined, $word) !== false) {
                    $section = $word;
                    break;
                }
            }

            foreach (array_slice($prices, 0, 2) as $price) {
                $results[] = [
                    'event'   => mb_substr($title, 0, 80) ?: $query,
                    'section' => $section,
                    'price'   => trim($price),
                    'source'  => mb_substr($source, 0, 60),
                ];
            }

            if (count($results) >= 20) break;
        }

        // 5. Also parse events block if present ───────────────────────────────
        foreach ($data['events_results'] ?? [] as $ev) {
            $title = $ev['title']   ?? '';
            $date  = $ev['date']    ?? '';
            $venue = $ev['address'] ?? '';

            foreach ($ev['ticket_info'] ?? [] as $ticket) {
                $priceRaw = $ticket['price'] ?? '';
                preg_match_all($priceRe, $priceRaw, $pm);
                $prices = $pm[0] ?? [];
                $source = $ticket['source'] ?? 'google.com';

                if (! empty($prices)) {
                    $label = $venue
                        ? mb_substr("{$title} — {$venue} ({$date})", 0, 80)
                        : mb_substr($title, 0, 80);

                    $results[] = [
                        'event'   => $label ?: $query,
                        'section' => 'General',
                        'price'   => $prices[0],
                        'source'  => mb_substr($source, 0, 60),
                    ];
                }
            }
        }

        // 6. Deduplicate ──────────────────────────────────────────────────────
        $seen   = [];
        $unique = [];
        foreach ($results as $r) {
            $key = mb_substr($r['event'], 0, 40) . '|' . $r['price'];
            if (! in_array($key, $seen)) {
                $seen[]   = $key;
                $unique[] = $r;
            }
        }

        return response()->json([
            'success' => true,
            'count'   => count($unique),
            'query'   => $query,
            'results' => array_slice($unique, 0, 20),
        ]);
    }
}