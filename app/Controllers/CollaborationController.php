<?php
namespace App\Controllers;

use App\Core\{Request, Response, Auth, CSRF};
use App\Services\{CollaborationService, AuditService};

class CollaborationController
{
    /** Unirse a sesión de colaboración */
    public function join(Request $req, array $params): void
    {
        $pageId = (int)$params['id'];
        $result = CollaborationService::join($pageId);
        
        AuditService::log('collab_join', "Se unió a colaboración", 'pages', $pageId);
        
        Response::json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /** Salir de sesión */
    public function leave(Request $req, array $params): void
    {
        $pageId = (int)$params['id'];
        CollaborationService::leave($pageId);
        Response::json(['success' => true]);
    }

    /** Heartbeat (mantener vivo) */
    public function heartbeat(Request $req, array $params): void
    {
        $pageId = (int)$params['id'];
        $cursor = $req->json();
        
        $result = CollaborationService::heartbeat($pageId, $cursor);
        Response::json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /** Obtener eventos nuevos (polling) */
    public function poll(Request $req, array $params): void
    {
        $pageId = (int)$params['id'];
        $since = (int)$req->get('since', 0);
        
        $events = CollaborationService::getEventsSince($pageId, $since);
        $collaborators = CollaborationService::getActiveCollaborators($pageId);
        $locks = CollaborationService::getActiveLocks($pageId);
        
        Response::json([
            'success' => true,
            'data' => [
                'events' => $events,
                'collaborators' => $collaborators,
                'locks' => $locks,
                'server_time' => time(),
            ],
        ]);
    }

    /** Publicar cambio de contenido */
    public function publishChange(Request $req, array $params): void
    {
        CSRF::guard();
        $pageId = (int)$params['id'];
        $content = $req->json();
        
        $sequence = CollaborationService::publishContentChange($pageId, $content);
        Response::json([
            'success' => true,
            'data' => ['sequence' => $sequence],
        ]);
    }

    /** Publicar movimiento de cursor */
    public function publishCursor(Request $req, array $params): void
    {
        $pageId = (int)$params['id'];
        $cursor = $req->json();
        
        $sequence = CollaborationService::publishCursorMove($pageId, $cursor);
        Response::json([
            'success' => true,
            'data' => ['sequence' => $sequence],
        ]);
    }

    /** Adquirir lock */
    public function acquireLock(Request $req, array $params): void
    {
        CSRF::guard();
        $pageId = (int)$params['id'];
        $data = $req->json();
        $selector = $data['selector'] ?? '';
        
        if (empty($selector)) {
            Response::json(['success' => false, 'message' => 'Selector requerido'], 400);
        }
        
        $result = CollaborationService::acquireLock($pageId, $selector);
        Response::json(['success' => true, 'data' => $result]);
    }

    /** Liberar lock */
    public function releaseLock(Request $req, array $params): void
    {
        CSRF::guard();
        $pageId = (int)$params['id'];
        $data = $req->json();
        
        CollaborationService::releaseLock($pageId, $data['selector'] ?? '');
        Response::json(['success' => true]);
    }

    /** Enviar mensaje de chat */
    public function sendMessage(Request $req, array $params): void
    {
        CSRF::guard();
        $pageId = (int)$params['id'];
        $data = $req->json();
        $message = $data['message'] ?? '';
        
        if (empty(trim($message))) {
            Response::json(['success' => false, 'message' => 'Mensaje vacío'], 400);
        }
        
        $id = CollaborationService::sendMessage($pageId, $message);
        Response::json(['success' => true, 'data' => ['message_id' => $id]]);
    }

    /** Obtener mensajes recientes */
    public function getMessages(Request $req, array $params): void
    {
        $pageId = (int)$params['id'];
        $messages = CollaborationService::getRecentMessages($pageId);
        Response::json(['success' => true, 'data' => $messages]);
    }

    /** Estadísticas de sesión */
    public function stats(Request $req, array $params): void
    {
        $pageId = (int)$params['id'];
        $stats = CollaborationService::getSessionStats($pageId);
        Response::json(['success' => true, 'data' => $stats]);
    }
}