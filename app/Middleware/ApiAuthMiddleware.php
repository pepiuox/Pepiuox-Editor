<?php
namespace App\Middleware;
use App\Core\{Request, Response, Database};
use App\Models\ApiToken;

class ApiAuthMiddleware
{
    public function handle(Request $request): void
    {
        $auth = $request->getHeader('Authorization') ?? '';
        $token = null;
        if (str_starts_with($auth, 'Bearer ')) {
            $token = substr($auth, 7);
        } else {
            $token = $request->get('api_key') ?? $request->get('access_token');
        }

        if (!$token) {
            Response::json(['success'=>false,'message'=>'Unauthorized','code'=>'AUTH_MISSING'], 401);
        }

        $data = ApiToken::validate($token);
        if (!$data) {
            Response::json(['success'=>false,'message'=>'Invalid or expired token','code'=>'AUTH_INVALID'], 401);
        }

        // IP whitelist
        if (!empty($data['allowed_ips'])) {
            $allowed = json_decode($data['allowed_ips'], true) ?: [];
            if ($allowed && !in_array($request->ip(), $allowed, true)) {
                Response::json(['success'=>false,'message'=>'IP not allowed','code'=>'IP_BLOCKED'], 403);
            }
        }

        // Rate limit (simple, por minuto)
        $minute = date('Y-m-d H:i');
        $key = 'api:' . $data['application_id'] . ':' . $minute;
        $count = (int)(Database::fetch("SELECT cache_value FROM cache_settings WHERE cache_key = ?", [$key])['cache_value'] ?? 0);
        if ($count >= (int)$data['rate_limit']) {
            Response::json(['success'=>false,'message'=>'Rate limit exceeded','code'=>'RATE_LIMIT'], 429);
        }
        Database::execute(
            "INSERT INTO cache_settings (cache_key, cache_value, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))
             ON DUPLICATE KEY UPDATE cache_value = CAST(cache_value AS UNSIGNED) + 1",
            [$key, (string)($count + 1)]
        );

        // Log
        $start = microtime(true);
        register_shutdown_function(function() use ($data, $request, $start) {
            try {
                Database::insert(
                    "INSERT INTO api_access_logs (application_id, token_id, endpoint, method, ip_address, user_agent, request_data, response_code, execution_time)
                     VALUES (?,?,?,?,?,?,?,?,?)",
                    [
                        $data['application_id'], $data['id'],
                        $request->uri(), $request->method(), $request->ip(), $request->userAgent(),
                        json_encode($request->all()), http_response_code() ?: 200,
                        round(microtime(true) - $start, 4),
                    ]
                );
            } catch (\Throwable $e) {}
        });

        // Guardar contexto
        Request::$apiContext = $data;
    }
}