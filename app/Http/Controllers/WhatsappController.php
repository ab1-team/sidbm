<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class WhatsappController extends Controller
{
    private function client()
    {
        return new \GuzzleHttp\Client([
            'timeout' => 15,
            'http_errors' => false,
            'headers' => [
                'Authorization' => 'Basic '.base64_encode(env('WA_GATEWAY_API_KEY')),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            ],
        ]);
    }

    private function base()
    {
        return rtrim(env('WA_GATEWAY_BASE', 'https://wa-gateway.enpiistudio.com'), '/');
    }

    public function instanceState(Request $request)
    {
        $lokasi = Session::get('lokasi');
        $wa = \App\Models\Whatsapp::where('lokasi', $lokasi)->first();

        if (! $wa || ! $wa->instance_name) {
            return response()->json(['success' => false, 'state' => 'unknown']);
        }

        try {
            $res = $this->client()->get($this->base().'/instance-state', [
                'query' => ['instance' => $wa->instance_name],
            ]);
            $body = json_decode((string) $res->getBody(), true) ?? [];

            // Shape n8n saat ini: {success: "true", instance: {name, status, qr?}}
            // Fallback ke shape {data: {instance: {...}}} kalau Anda pindah ke sana.
            $instance = $body['instance'] ?? ($body['data']['instance'] ?? []);
            $state = $instance['status'] ?? ($body['status'] ?? ($body['state'] ?? 'unknown'));
            $qr = $instance['qr'] ?? ($body['qr'] ?? null);

            if ($state === 'open' && $wa->status !== 'connected') {
                $wa->update(['status' => 'connected']);
            }

            return response()->json([
                'success' => true,
                'state' => $state,
                'qr' => $qr,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    public function sendMessage(Request $request)
    {
        $data = $request->validate([
            'number' => 'required|string',
            'text' => 'required|string',
            'instance' => 'nullable|string',
        ]);

        $instance = $data['instance']
            ?? optional(\App\Models\Whatsapp::where('lokasi', Session::get('lokasi'))->first())->instance_name;

        if (! $instance) {
            return response()->json(['success' => false, 'msg' => 'Instance WhatsApp belum disetel'], 422);
        }

        $delay = 1500 + random_int(0, 2000);

        try {
            $res = $this->client()->post($this->base().'/send-message', [
                'body' => json_encode([
                    'instance' => $instance,
                    'number' => $data['number'],
                    'text' => $data['text'],
                    'delay' => $delay,
                ]),
            ]);
            $body = json_decode((string) $res->getBody(), true) ?? [];

            return response()->json([
                'success' => $body['success'] ?? ($res->getStatusCode() === 200),
                'delay' => $delay,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    public function historyMessage(Request $request)
    {
        $wa = \App\Models\Whatsapp::where('lokasi', Session::get('lokasi'))->first();

        if (! $wa || ! $wa->instance_name) {
            return response()->json(['success' => false, 'msg' => 'Instance WhatsApp belum disetel'], 422);
        }

        try {
            $res = $this->client()->get($this->base().'/history-message', [
                'query' => ['instance' => $wa->instance_name],
            ]);
            $body = json_decode((string) $res->getBody(), true) ?? [];

            return response()->json([
                'success' => $res->getStatusCode() === 200,
                'data' => $body,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    public function sendMessages(Request $request)
    {
        $data = $request->validate([
            'instance' => 'nullable|string',
            'messages' => 'required|array|min:1',
            'messages.*.number' => 'required|string',
            'messages.*.text' => 'required|string',
        ]);

        $instance = $data['instance']
            ?? optional(\App\Models\Whatsapp::where('lokasi', Session::get('lokasi'))->first())->instance_name;

        if (! $instance) {
            return response()->json(['success' => false, 'msg' => 'Instance WhatsApp belum disetel'], 422);
        }

        try {
            $res = $this->client()->post($this->base().'/send-messages', [
                'body' => json_encode([
                    'instance' => $instance,
                    'messages' => $data['messages'],
                ]),
            ]);
            $body = json_decode((string) $res->getBody(), true) ?? [];

            return response()->json([
                'success' => $body['success'] ?? ($res->getStatusCode() === 200),
                'count' => count($data['messages']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }
}