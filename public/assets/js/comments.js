/**
 * ═══════════════════════════════════════════════════════════════
 * SISTEMA DE COMENTARIOS EN COMPONENTES — PePiuoX Maker
 * ═══════════════════════════════════════════════════════════════
 */
class CommentsManager {
    constructor(pageId, grapesEditor, options = {}) {
        this.pageId = pageId;
        this.editor = grapesEditor;
        this.options = {
            baseUrl: options.baseUrl || '',
            csrfToken: options.csrfToken || '',
            ...options
        };
        
        this.comments = [];
        this.activeCommentId = null;
        this.currentFilter = 'all';
        this.commentMode = false;
        this.pins = new Map();
        
        this.init();
    }
    
    init() {
        this.renderPanel();
        this.loadComments();
        this.bindEvents();
    }
    
    // ═══════════════════════════════════════════════════════════
    // RENDERIZADO DEL PANEL
    // ═══════════════════════════════════════════════════════════
    renderPanel() {
        const panel = document.createElement('aside');
        panel.className = 'comments-panel';
        panel.id = 'commentsPanel';
        panel.innerHTML = `
            <div class="comments-header">
                <h3><i class="bi bi-chat-dots"></i> Comentarios</h3>
                <div class="d-flex gap-1">
                    <button class="btn-builder btn-ghost btn-sm" onclick="commentsManager.toggleCommentMode()" title="Añadir comentario">
                        <i class="bi bi-plus-circle"></i>
                    </button>
                    <button class="btn-builder btn-ghost btn-sm" onclick="commentsManager.togglePanel()" title="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
            
            <div class="comments-filters">
                <button class="filter-btn active" data-filter="all" onclick="commentsManager.setFilter('all')">
                    Todos <span class="count" id="countAll">0</span>
                </button>
                <button class="filter-btn" data-filter="open" onclick="commentsManager.setFilter('open')">
                    Abiertos <span class="count" id="countOpen">0</span>
                </button>
                <button class="filter-btn" data-filter="resolved" onclick="commentsManager.setFilter('resolved')">
                    Resueltos <span class="count" id="countResolved">0</span>
                </button>
            </div>
            
            <div class="comments-list" id="commentsList">
                <div class="comments-empty">
                    <i class="bi bi-chat-dots"></i>
                    <p>Cargando comentarios...</p>
                </div>
            </div>
            
            <div class="comment-form" id="commentForm" style="display:none">
                <textarea id="newCommentText" placeholder="Escribe un comentario... Usa @usuario para mencionar"></textarea>
                <div class="comment-form-actions">
                    <select id="newCommentPriority">
                        <option value="low">🔵 Baja</option>
                        <option value="medium" selected>🟡 Media</option>
                        <option value="high">🟠 Alta</option>
                        <option value="urgent">🔴 Urgente</option>
                    </select>
                    <button onclick="commentsManager.submitNewComment()">
                        <i class="bi bi-send"></i> Publicar
                    </button>
                </div>
            </div>
        `;
        
        // Insertar después del configPanel
        const main = document.querySelector('.builder-main');
        main.appendChild(panel);
    }
    
    // ═══════════════════════════════════════════════════════════
    // CARGA DE COMENTARIOS
    // ═══════════════════════════════════════════════════════════
    async loadComments() {
        try {
            const r = await fetch(`${this.options.baseUrl}/admin/pages/${this.pageId}/comments?status=${this.currentFilter}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await r.json();
            
            if (result.success) {
                this.comments = result.data.comments;
                this.updateCounts(result.data.counts);
                this.renderComments();
                this.renderPins();
            }
        } catch (err) {
            console.error('Error cargando comentarios:', err);
        }
    }
    
    updateCounts(counts) {
        document.getElementById('countAll').textContent = counts.total || 0;
        document.getElementById('countOpen').textContent = counts.open || 0;
        document.getElementById('countResolved').textContent = counts.resolved || 0;
    }
    
    renderComments() {
        const list = document.getElementById('commentsList');
        
        if (this.comments.length === 0) {
            list.innerHTML = `
                <div class="comments-empty">
                    <i class="bi bi-chat-dots"></i>
                    <p>No hay comentarios${this.currentFilter !== 'all' ? ' en este filtro' : ''}</p>
                </div>
            `;
            return;
        }
        
        list.innerHTML = this.comments.map(c => this.renderCommentItem(c)).join('');
    }
    
    renderCommentItem(c) {
        const initial = (c.full_name || c.username || '?')[0].toUpperCase();
        const color = this.getUserColor(c.user_id);
        const timeAgo = this.timeAgo(c.created_at);
        const priorityClass = `priority-${c.priority}`;
        const priorityLabel = { low: 'Baja', medium: 'Media', high: 'Alta', urgent: 'Urgente' }[c.priority];
        
        const repliesHtml = (c.replies || []).map(r => {
            const rInitial = (r.full_name || r.username || '?')[0].toUpperCase();
            const rColor = this.getUserColor(r.user_id);
            return `
                <div class="reply-item">
                    <div class="reply-avatar" style="background:${rColor}">${rInitial}</div>
                    <div class="reply-content">
                        <div class="reply-header">
                            <span class="reply-author">${this.escapeHtml(r.full_name || r.username)}</span>
                            <span class="reply-time">${this.timeAgo(r.created_at)}</span>
                        </div>
                        <div class="reply-message">${this.formatMessage(r.message)}</div>
                    </div>
                </div>
            `;
        }).join('');
        
        return `
            <div class="comment-item ${c.status === 'resolved' ? 'resolved' : ''} ${this.activeCommentId === c.id ? 'active' : ''}"
                 data-id="${c.id}" onclick="commentsManager.selectComment(${c.id})">
                <div class="comment-header">
                    <div class="comment-avatar" style="background:${color}">${initial}</div>
                    <div class="comment-meta">
                        <div class="comment-author">${this.escapeHtml(c.full_name || c.username)}</div>
                        <div class="comment-time">${timeAgo}</div>
                    </div>
                    <span class="comment-priority ${priorityClass}">${priorityLabel}</span>
                </div>
                <div class="comment-message">${this.formatMessage(c.message)}</div>
                <div class="comment-footer">
                    ${c.component_id ? `<span class="comment-badge"><i class="bi bi-box"></i> Componente</span>` : ''}
                    ${c.replies_count > 0 ? `<span class="comment-badge"><i class="bi bi-chat-left-text"></i> ${c.replies_count}</span>` : ''}
                    ${c.status === 'resolved' ? `<span class="comment-badge" style="color:var(--success)"><i class="bi bi-check-circle"></i> Resuelto</span>` : ''}
                </div>
                ${repliesHtml ? `<div class="comment-replies">${repliesHtml}</div>` : ''}
                <div class="comment-actions">
                    ${c.status === 'open' ? `
                        <button class="resolve" onclick="event.stopPropagation(); commentsManager.resolveComment(${c.id})">
                            <i class="bi bi-check-circle"></i> Resolver
                        </button>
                    ` : `
                        <button onclick="event.stopPropagation(); commentsManager.reopenComment(${c.id})">
                            <i class="bi bi-arrow-counterclockwise"></i> Reabrir
                        </button>
                    `}
                    <button onclick="event.stopPropagation(); commentsManager.replyToComment(${c.id})">
                        <i class="bi bi-reply"></i> Responder
                    </button>
                    <button class="delete" onclick="event.stopPropagation(); commentsManager.deleteComment(${c.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }
    
    // ═══════════════════════════════════════════════════════════
    // PINS EN EL CANVAS
    // ═══════════════════════════════════════════════════════════
    renderPins() {
        // Eliminar pins anteriores
        this.pins.forEach(pin => pin.element.remove());
        this.pins.clear();
        
        const canvas = this.editor.Canvas.getBody();
        if (!canvas) return;
        
        this.comments.forEach((c, index) => {
            const pin = document.createElement('div');
            pin.className = `comment-pin priority-${c.priority} ${c.status === 'resolved' ? 'resolved' : ''} ${this.activeCommentId === c.id ? 'active' : ''}`;
            pin.style.left = c.position_x + 'px';
            pin.style.top = c.position_y + 'px';
            pin.textContent = index + 1;
            pin.title = `${c.full_name || c.username}: ${c.message.substring(0, 50)}`;
            pin.onclick = (e) => {
                e.stopPropagation();
                this.selectComment(c.id);
            };
            
            canvas.appendChild(pin);
            this.pins.set(c.id, { element: pin, comment: c });
        });
    }
    
    // ═══════════════════════════════════════════════════════════
    // MODO AÑADIR COMENTARIO
    // ═══════════════════════════════════════════════════════════
    toggleCommentMode() {
        this.commentMode = !this.commentMode;
        const canvas = this.editor.Canvas.getBody();
        const canvasFrame = this.editor.Canvas.getElement();
        
        if (this.commentMode) {
            canvasFrame.classList.add('canvas-comment-mode');
            this.showBanner();
            this.showCommentForm();
        } else {
            canvasFrame.classList.remove('canvas-comment-mode');
            this.hideBanner();
            this.hideCommentForm();
        }
    }
    
    showBanner() {
        if (document.getElementById('commentModeBanner')) return;
        const banner = document.createElement('div');
        banner.id = 'commentModeBanner';
        banner.className = 'comment-mode-banner';
        banner.innerHTML = `
            <i class="bi bi-pin-map"></i>
            Haz clic en el canvas para añadir un comentario
            <button onclick="commentsManager.toggleCommentMode()">Cancelar</button>
        `;
        document.body.appendChild(banner);
    }
    
    hideBanner() {
        const banner = document.getElementById('commentModeBanner');
        if (banner) banner.remove();
    }
    
    showCommentForm() {
        document.getElementById('commentForm').style.display = 'block';
        document.getElementById('newCommentText').focus();
    }
    
    hideCommentForm() {
        document.getElementById('commentForm').style.display = 'none';
        document.getElementById('newCommentText').value = '';
    }
    
    // ═══════════════════════════════════════════════════════════
    // EVENTOS
    // ═══════════════════════════════════════════════════════════
    bindEvents() {
        // Click en canvas para crear comentario
        this.editor.on('canvas:click', (e) => {
            if (!this.commentMode) return;
            
            const rect = this.editor.Canvas.getElement().getBoundingClientRect();
            const x = e.originalEvent.clientX - rect.left;
            const y = e.originalEvent.clientY - rect.top;
            
            // Obtener componente seleccionado (si hay)
            const selected = this.editor.getSelected();
            const componentId = selected ? selected.getId() : null;
            const componentSelector = selected ? selected.toHTML().match(/class="([^"]+)"/)?.[1] : null;
            
            // Guardar posición temporalmente
            this.pendingComment = {
                position_x: x,
                position_y: y,
                component_id: componentId,
                component_selector: componentSelector
            };
            
            this.showCommentForm();
        });
        
        // Mentions @usuario
        const textarea = document.getElementById('newCommentText');
        if (textarea) {
            textarea.addEventListener('input', (e) => this.handleMentions(e));
            textarea.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && e.ctrlKey) {
                    this.submitNewComment();
                }
            });
        }
    }
    
    // ═══════════════════════════════════════════════════════════
    // ACCIONES
    // ═══════════════════════════════════════════════════════════
    async submitNewComment() {
        if (!this.pendingComment) {
            showToast('Haz clic en el canvas primero', 'warning');
            return;
        }
        
        const message = document.getElementById('newCommentText').value.trim();
        if (!message) {
            showToast('Escribe un mensaje', 'warning');
            return;
        }
        
        const priority = document.getElementById('newCommentPriority').value;
        
        const formData = new FormData();
        formData.append('message', message);
        formData.append('priority', priority);
        formData.append('position_x', this.pendingComment.position_x);
        formData.append('position_y', this.pendingComment.position_y);
        if (this.pendingComment.component_id) {
            formData.append('component_id', this.pendingComment.component_id);
        }
        if (this.pendingComment.component_selector) {
            formData.append('component_selector', this.pendingComment.component_selector);
        }
        formData.append('_csrf_token', this.options.csrfToken);
        
        try {
            const r = await fetch(`${this.options.baseUrl}/admin/pages/${this.pageId}/comments`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await r.json();
            
            if (result.success) {
                showToast('✓ Comentario publicado', 'success');
                this.pendingComment = null;
                this.toggleCommentMode();
                this.loadComments();
            } else {
                showToast('✗ ' + (result.message || 'Error'), 'error');
            }
        } catch (err) {
            showToast('✗ Error: ' + err.message, 'error');
        }
    }
    
    async replyToComment(commentId) {
        const message = prompt('Escribe tu respuesta:');
        if (!message || !message.trim()) return;
        
        const formData = new FormData();
        formData.append('message', message.trim());
        formData.append('_csrf_token', this.options.csrfToken);
        
        try {
            const r = await fetch(`${this.options.baseUrl}/admin/pages/${this.pageId}/comments/${commentId}/reply`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await r.json();
            
            if (result.success) {
                showToast('✓ Respuesta publicada', 'success');
                this.loadComments();
            }
        } catch (err) {
            showToast('✗ Error: ' + err.message, 'error');
        }
    }
    
    async resolveComment(commentId) {
        const formData = new FormData();
        formData.append('_csrf_token', this.options.csrfToken);
        
        try {
            const r = await fetch(`${this.options.baseUrl}/admin/pages/${this.pageId}/comments/${commentId}/resolve`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await r.json();
            
            if (result.success) {
                showToast('✓ Comentario resuelto', 'success');
                this.loadComments();
            }
        } catch (err) {
            showToast('✗ Error: ' + err.message, 'error');
        }
    }
    
    async reopenComment(commentId) {
        const formData = new FormData();
        formData.append('_csrf_token', this.options.csrfToken);
        
        try {
            const r = await fetch(`${this.options.baseUrl}/admin/pages/${this.pageId}/comments/${commentId}/reopen`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await r.json();
            
            if (result.success) {
                showToast('Comentario reabierto', 'info');
                this.loadComments();
            }
        } catch (err) {
            showToast('✗ Error: ' + err.message, 'error');
        }
    }
    
    async deleteComment(commentId) {
        if (!confirm('¿Eliminar este comentario?')) return;
        
        const formData = new FormData();
        formData.append('_csrf_token', this.options.csrfToken);
        
        try {
            const r = await fetch(`${this.options.baseUrl}/admin/pages/${this.pageId}/comments/${commentId}/delete`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await r.json();
            
            if (result.success) {
                showToast('Comentario eliminado', 'info');
                this.loadComments();
            }
        } catch (err) {
            showToast('✗ Error: ' + err.message, 'error');
        }
    }
    
    selectComment(commentId) {
        this.activeCommentId = commentId;
        
        // Actualizar UI
        document.querySelectorAll('.comment-item').forEach(el => {
            el.classList.toggle('active', parseInt(el.dataset.id) === commentId);
        });
        
        // Actualizar pins
        this.pins.forEach((pin, id) => {
            pin.element.classList.toggle('active', id === commentId);
        });
        
        // Hacer scroll al comentario
        const commentEl = document.querySelector(`.comment-item[data-id="${commentId}"]`);
        if (commentEl) {
            commentEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        // Enfocar el componente en el canvas (si tiene component_id)
        const comment = this.comments.find(c => c.id === commentId);
        if (comment && comment.component_id) {
            const component = this.editor.DomComponents.components().filter(c => c.getId() === comment.component_id)[0];
            if (component) {
                this.editor.select(component);
            }
        }
    }
    
    setFilter(filter) {
        this.currentFilter = filter;
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.filter === filter);
        });
        this.loadComments();
    }
    
    togglePanel() {
        const panel = document.getElementById('commentsPanel');
        panel.classList.toggle('collapsed');
        setTimeout(() => {
            this.editor.Canvas.refresh();
        }, 300);
    }
    
    // ═══════════════════════════════════════════════════════════
    // MENCIONES @USUARIO
    // ═══════════════════════════════════════════════════════════
    handleMentions(e) {
        const textarea = e.target;
        const value = textarea.value;
        const cursorPos = textarea.selectionStart;
        
        // Buscar el último @ antes del cursor
        const textBefore = value.substring(0, cursorPos);
        const match = textBefore.match(/@(\w*)$/);
        
        // Eliminar dropdown anterior
        const existing = document.getElementById('mentionsDropdown');
        if (existing) existing.remove();
        
        if (!match) return;
        
        const query = match[1].toLowerCase();
        
        // Buscar usuarios (simulado - en producción harías fetch al servidor)
        this.searchUsers(query).then(users => {
            if (users.length === 0) return;
            
            const dropdown = document.createElement('div');
            dropdown.id = 'mentionsDropdown';
            dropdown.className = 'mentions-dropdown';
            
            const rect = textarea.getBoundingClientRect();
            dropdown.style.left = rect.left + 'px';
            dropdown.style.top = (rect.bottom + 4) + 'px';
            dropdown.style.position = 'fixed';
            
            dropdown.innerHTML = users.map(u => `
                <div class="mention-item" onclick="commentsManager.insertMention('${u.username}')">
                    <div class="mention-avatar">${u.username[0].toUpperCase()}</div>
                    <div>
                        <div style="font-weight:600">${this.escapeHtml(u.username)}</div>
                        <div style="font-size:0.7rem;color:var(--builder-text-muted)">${this.escapeHtml(u.full_name || '')}</div>
                    </div>
                </div>
            `).join('');
            
            document.body.appendChild(dropdown);
            
            // Cerrar al hacer clic fuera
            setTimeout(() => {
                document.addEventListener('click', function closeMentions(e) {
                    if (!dropdown.contains(e.target)) {
                        dropdown.remove();
                        document.removeEventListener('click', closeMentions);
                    }
                });
            }, 100);
        });
    }
    
    insertMention(username) {
        const textarea = document.getElementById('newCommentText');
        const value = textarea.value;
        const cursorPos = textarea.selectionStart;
        
        // Reemplazar @query por @username
        const textBefore = value.substring(0, cursorPos);
        const textAfter = value.substring(cursorPos);
        const newTextBefore = textBefore.replace(/@\w*$/, `@${username} `);
        
        textarea.value = newTextBefore + textAfter;
        textarea.focus();
        
        // Eliminar dropdown
        const dropdown = document.getElementById('mentionsDropdown');
        if (dropdown) dropdown.remove();
    }
    
    async searchUsers(query) {
        try {
            const r = await fetch(`${this.options.baseUrl}/admin/users/search?q=${encodeURIComponent(query)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await r.json();
            return result.data || [];
        } catch (err) {
            return [];
        }
    }
    
    // ═══════════════════════════════════════════════════════════
    // UTILIDADES
    // ═══════════════════════════════════════════════════════════
    formatMessage(message) {
        // Resaltar menciones @usuario
        message = this.escapeHtml(message);
        message = message.replace(/@(\w+)/g, '<span class="mention-highlight">@$1</span>');
        return message;
    }
    
    escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    timeAgo(dateStr) {
        const seconds = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
        if (seconds < 60) return 'ahora';
        if (seconds < 3600) return Math.floor(seconds / 60) + ' min';
        if (seconds < 86400) return Math.floor(seconds / 3600) + ' h';
        if (seconds < 604800) return Math.floor(seconds / 86400) + ' d';
        return Math.floor(seconds / 604800) + ' sem';
    }
    
    getUserColor(userId) {
        const colors = ['#00d4aa', '#3b82f6', '#f59e0b', '#ef4444', '#a855f7', '#ec4899', '#14b8a6'];
        return colors[userId % colors.length];
    }
}

// Instancia global
let commentsManager = null;