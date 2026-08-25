<?php
namespace App\Controllers;

use App\Core\{Request, Response, Auth, CSRF, Database};
use App\Models\ComponentComment;
use App\Services\{CommentService, AuditService};

class CommentsController
{
    /**
     * Lista todos los comentarios de una página (AJAX).
     */
    public function index(Request $req, array $params): void
    {
        $pageId = (int)$params['id'];
        $status = $req->get('status', 'all');

        $comments = ComponentComment::forPage($pageId, $status);
        $counts = ComponentComment::countByStatus($pageId);

        Response::json([
            'success' => true,
            'data' => [
                'comments' => $comments,
                'counts' => $counts,
            ]
        ]);
    }

    /**
     * Crea un nuevo comentario.
     */
    public function create(Request $req, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('comments.create')) {
            Response::json(['success' => false, 'message' => 'Sin permisos'], 403);
        }

        $pageId = (int)$params['id'];
        $message = trim($req->post('message', ''));

        if (empty($message)) {
            Response::json(['success' => false, 'message' => 'El mensaje no puede estar vacío']);
        }

        $data = [
            'component_selector' => $req->post('component_selector'),
            'component_id' => $req->post('component_id'),
            'position_x' => (float)$req->post('position_x', 0),
            'position_y' => (float)$req->post('position_y', 0),
            'message' => $message,
            'priority' => $req->post('priority', 'medium'),
        ];

        $commentId = CommentService::createComment($pageId, $data);
        $comment = ComponentComment::find($commentId);

        Response::json([
            'success' => true,
            'data' => $comment,
        ]);
    }

    /**
     * Añade una respuesta a un comentario.
     */
    public function reply(Request $req, array $params): void
    {
        CSRF::guard();

        $commentId = (int)$params['comment_id'];
        $message = trim($req->post('message', ''));

        if (empty($message)) {
            Response::json(['success' => false, 'message' => 'El mensaje no puede estar vacío']);
        }

        $replyId = CommentService::addReply($commentId, $message);
        $comment = ComponentComment::find($commentId);

        Response::json([
            'success' => true,
            'data' => $comment,
        ]);
    }

    /**
     * Resuelve un comentario.
     */
    public function resolve(Request $req, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('comments.resolve')) {
            Response::json(['success' => false, 'message' => 'Sin permisos'], 403);
        }

        $commentId = (int)$params['comment_id'];
        $success = CommentService::resolve($commentId);

        Response::json(['success' => $success]);
    }

    /**
     * Reabre un comentario.
     */
    public function reopen(Request $req, array $params): void
    {
        CSRF::guard();

        $commentId = (int)$params['comment_id'];
        $success = CommentService::reopen($commentId);

        Response::json(['success' => $success]);
    }

    /**
     * Elimina un comentario.
     */
    public function delete(Request $req, array $params): void
    {
        CSRF::guard();

        $commentId = (int)$params['comment_id'];
        $success = CommentService::delete($commentId);

        Response::json(['success' => $success]);
    }

    /**
     * Actualiza la posición de un comentario (al mover el pin).
     */
    public function move(Request $req, array $params): void
    {
        CSRF::guard();

        $commentId = (int)$params['comment_id'];
        $x = (float)$req->post('position_x', 0);
        $y = (float)$req->post('position_y', 0);

        $success = ComponentComment::updatePosition($commentId, $x, $y);
        Response::json(['success' => $success]);
    }

    /**
     * Menciones pendientes del usuario actual.
     */
    public function mentions(Request $req): void
    {
        $userId = (int)Auth::id();
        $mentions = CommentService::getMentionsForUser($userId);
        Response::json(['success' => true, 'data' => $mentions]);
    }
}