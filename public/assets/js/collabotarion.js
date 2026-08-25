/**
 * ═══════════════════════════════════════════════════════════════════
 * COLABORACIÓN EN TIEMPO REAL — PePiuoX Maker
 * ═══════════════════════════════════════════════════════════════════
 */
class CollaborationClient {
    constructor(pageId, grapesEditor, options = {}) {
        this.pageId = pageId;
        this.editor = grapesEditor;
        this.options = {
            pollInterval: options.pollInterval || 2000,
            heartbeatInterval: options.heartbeatInterval || 10000,
            baseUrl: options.baseUrl || '',
            csrfToken: options.csrfToken || '',
        };
        
        this.sessionToken = null;
        this.lastSequence = 0;
        this.collaborators = new Map();
        this.locks = new Map();
        this.isApplyingRemote = false;
        this.pollTimer = null;
        this.heartbeatTimer = null;
        this.broadcastChannel = null;
        this.isConnected = false;
        
        // Cursors remotos (overlays)
        this.remoteCursors = new Map();
        
        // Inicializar
        this.init();
    }
    
    async init() {
        try {
            // Unirse a la sesión
            const joinResult = await this.api('/join', 'POST');
            if (!joinResult.success) {
                console.error('No se pudo unir a la sesión');
                return;
            }
            
            this.sessionToken = joinResult.data.token;
            this.lastSequence = joinResult.data.sequence;
            
            // Renderizar colaboradores iniciales
            this.updateCollaborators(joinResult.data.collaborators);
            
            // Iniciar polling
            this.startPolling();
            
            // Iniciar heartbeat
            this.startHeartbeat();
            
            // Iniciar BroadcastChannel (sincronización entre pestañas)
            this.initBroadcastChannel();
            
            // Escuchar eventos del editor
            this.bindEditorEvents();
            
            // Mostrar UI
            this.renderUI();
            
            this.isConnected = true;
            this.showToast('🤝 Conectado a sesión colaborativa', 'success');
            
        } catch (err) {
            console.error('Error inicializando colaboración:', err);
            this.showToast('⚠ No se pudo conectar a la sesión colaborativa', 'warning');
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // API
    // ═══════════════════════════════════════════════════════════════
    
    async api(endpoint, method = 'GET', data = null) {
        const url = `${this.options.baseUrl}/admin/collaboration/${this.pageId}${endpoint}`;
        const options = {
            method,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        };
        
        if (data && method !== 'GET') {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(url, options);
        return await response.json();
    }
    
    // ═══════════════════════════════════════════════════════════════
    // POLLING
    // ═══════════════════════════════════════════════════════════════
    
    startPolling() {
        this.pollTimer = setInterval(() => this.poll(), this.options.pollInterval);
    }
    
    async poll() {
        try {
            const result = await this.api(`/poll?since=${this.lastSequence}`);
            if (!result.success) return;
            
            const { events, collaborators, locks } = result.data;
            
            // Procesar eventos
            for (const event of events) {
                await this.handleEvent(event);
                this.lastSequence = Math.max(this.lastSequence, event.sequence);
            }
            
            // Actualizar colaboradores y locks
            this.updateCollaborators(collaborators);
            this.updateLocks(locks);
            
        } catch (err) {
            console.error('Error en polling:', err);
        }
    }
    
    async handleEvent(event) {
        // Ignorar eventos propios
        if (event.user_id == window.CURRENT_USER_ID) return;
        
        switch (event.event_type) {
            case 'content_change':
                this.applyRemoteContent(event);
                break;
            case 'cursor_move':
                this.updateRemoteCursor(event);
                break;
            case 'join':
                this.showToast(`👋 ${event.payload.username} se unió`, 'info');
                break;
            case 'leave':
                this.showToast(`👋 ${event.payload.username} salió`, 'info');
                this.removeRemoteCursor(event.user_id);
                break;
            case 'lock_acquire':
                this.showLockIndicator(event);
                break;
            case 'lock_release':
                this.hideLockIndicator(event);
                break;
            case 'chat':
                this.addChatMessage(event);
                break;
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // HEARTBEAT
    // ═══════════════════════════════════════════════════════════════
    
    startHeartbeat() {
        this.heartbeatTimer = setInterval(() => this.sendHeartbeat(), this.options.heartbeatInterval);
    }
    
    async sendHeartbeat() {
        try {
            const cursor = this.getCurrentCursor();
            await this.api('/heartbeat', 'POST', cursor);
        } catch (err) {
            console.error('Error en heartbeat:', err);
        }
    }
    
    getCurrentCursor() {
        // Obtener posición del cursor en el canvas
        const selected = this.editor.getSelected();
        return {
            position: 0,
            selector: selected ? selected.getEl().getAttribute('data-gjs-type') : null,
        };
    }
    
    // ═══════════════════════════════════════════════════════════════
    // BROADCAST CHANNEL (sincronización entre pestañas)
    // ═══════════════════════════════════════════════════════════════
    
    initBroadcastChannel() {
        if (!('BroadcastChannel' in window)) return;
        
        this.broadcastChannel = new BroadcastChannel(`collab_page_${this.pageId}`);
        this.broadcastChannel.onmessage = (e) => {
            const { type, data } = e.data;
            
            if (type === 'content_change') {
                this.applyRemoteContent({ payload: data, user_id: 'local' });
            }
        };
    }
    
    broadcastLocal(type, data) {
        if (!this.broadcastChannel) return;
        this.broadcastChannel.postMessage({ type, data });
    }
    
    // ═══════════════════════════════════════════════════════════════
    // EVENTOS DEL EDITOR
    // ═══════════════════════════════════════════════════════════════
    
    bindEditorEvents() {
        // Cambio de componente
        this.editor.on('component:update', (component) => {
            if (this.isApplyingRemote) return;
            this.publishContentChange(component, 'update');
        });
        
        // Añadir componente
        this.editor.on('component:add', (component) => {
            if (this.isApplyingRemote) return;
            this.publishContentChange(component, 'add');
        });
        
        // Eliminar componente
        this.editor.on('component:remove', (component) => {
            if (this.isApplyingRemote) return;
            this.publishContentChange(component, 'remove');
        });
        
        // Movimiento de cursor/selección
        this.editor.on('component:selected', (component) => {
            this.publishCursorMove(component);
        });
    }
    
    async publishContentChange(component, action) {
        try {
            const data = {
                html: this.editor.getHtml(),
                css: this.editor.getCss(),
                component: component ? {
                    id: component.getId(),
                    type: component.get('type'),
                } : null,
                action,
            };
            
            await this.api('/publish-change', 'POST', data);
            this.broadcastLocal('content_change', data);
        } catch (err) {
            console.error('Error publicando cambio:', err);
        }
    }
    
    async publishCursorMove(component) {
        try {
            const rect = component ? component.getEl().getBoundingClientRect() : null;
            await this.api('/publish-cursor', 'POST', {
                x: rect ? rect.left : 0,
                y: rect ? rect.top : 0,
                selector: component ? `#${component.getId()}` : null,
            });
        } catch (err) {
            // Silencioso
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // APLICAR CAMBIOS REMOTOS
    // ═══════════════════════════════════════════════════════════════
    
    applyRemoteContent(event) {
        this.isApplyingRemote = true;
        
        try {
            const { html, css, action, component } = event.payload;
            
            if (action === 'update' && component) {
                // Actualizar componente específico
                const remote = this.editor.getWrapper().find(`#${component.id}`)[0];
                if (remote) {
                    remote.set('content', html);
                }
            } else {
                // Reemplazar todo el contenido
                this.editor.setComponents(html);
                this.editor.setStyle(css);
            }
        } catch (err) {
            console.error('Error aplicando cambio remoto:', err);
        } finally {
            setTimeout(() => { this.isApplyingRemote = false; }, 100);
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // COLABORADORES
    // ═══════════════════════════════════════════════════════════════
    
    updateCollaborators(collaborators) {
        // Añadir nuevos
        collaborators.forEach(c => {
            this.collaborators.set(c.user_id, c);
        });
        
        // Remover los que ya no están
        const currentIds = new Set(collaborators.map(c => c.user_id));
        for (const [id] of this.collaborators) {
            if (!currentIds.has(id)) {
                this.collaborators.delete(id);
                this.removeRemoteCursor(id);
            }
        }
        
        this.renderCollaboratorsPanel();
    }
    
    renderCollaboratorsPanel() {
        const panel = document.getElementById('collabCollaborators');
        if (!panel) return;
        
        if (this.collaborators.size === 0) {
            panel.innerHTML = '<div class="collab-empty">Solo tú estás editando</div>';
            return;
        }
        
        let html = '';
        for (const c of this.collaborators.values()) {
            html += `
                <div class="collab-collaborator" style="border-left: 3px solid ${c.color}">
                    <div class="collab-avatar" style="background: ${c.color}20; color: ${c.color}">
                        ${c.avatar}
                    </div>
                    <div class="collab-info">
                        <div class="collab-name">${this.escapeHtml(c.full_name || c.username)}</div>
                        <div class="collab-status">Editando ahora</div>
                    </div>
                </div>
            `;
        }
        panel.innerHTML = html;
    }
    
    // ═══════════════════════════════════════════════════════════════
    // CURSORES REMOTOS
    // ═══════════════════════════════════════════════════════════════
    
    updateRemoteCursor(event) {
        const collab = this.collaborators.get(event.user_id);
        if (!collab) return;
        
        let cursor = this.remoteCursors.get(event.user_id);
        if (!cursor) {
            cursor = this.createRemoteCursor(collab);
            this.remoteCursors.set(event.user_id, cursor);
        }
        
        // Posicionar
        const { x, y } = event.payload;
        cursor.style.left = x + 'px';
        cursor.style.top = y + 'px';
        
        // Auto-ocultar tras 5s de inactividad
        clearTimeout(cursor._hideTimer);
        cursor._hideTimer = setTimeout(() => {
            cursor.style.opacity = '0';
        }, 5000);
        
        cursor.style.opacity = '1';
    }
    
    createRemoteCursor(collab) {
        const cursor = document.createElement('div');
        cursor.className = 'collab-remote-cursor';
        cursor.style.cssText = `
            position: fixed;
            width: 2px;
            height: 20px;
            background: ${collab.color};
            z-index: 9998;
            pointer-events: none;
            transition: all 0.2s ease;
        `;
        
        const label = document.createElement('div');
        label.className = 'collab-cursor-label';
        label.style.cssText = `
            position: absolute;
            top: -20px;
            left: 0;
            background: ${collab.color};
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            white-space: nowrap;
        `;
        label.textContent = collab.full_name || collab.username;
        
        cursor.appendChild(label);
        document.body.appendChild(cursor);
        return cursor;
    }
    
    removeRemoteCursor(userId) {
        const cursor = this.remoteCursors.get(userId);
        if (cursor) {
            cursor.remove();
            this.remoteCursors.delete(userId);
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // LOCKS
    // ═══════════════════════════════════════════════════════════════
    
    updateLocks(locks) {
        this.locks.clear();
        locks.forEach(l => this.locks.set(l.selector, l));
        this.renderLocksIndicators();
    }
    
    async acquireLock(selector) {
        const result = await this.api('/acquire-lock', 'POST', { selector });
        if (!result.data.success) {
            this.showToast(
                `🔒 ${result.data.locked_by_name} está editando este componente`,
                'warning'
            );
            return false;
        }
        return true;
    }
    
    async releaseLock(selector) {
        await this.api('/release-lock', 'POST', { selector });
    }
    
    showLockIndicator(event) {
        const collab = this.collaborators.get(event.user_id);
        if (!collab) return;
        
        const component = this.editor.getWrapper().find(event.payload.selector)[0];
        if (!component) return;
        
        const el = component.getEl();
        el.style.outline = `2px dashed ${collab.color}`;
        el.dataset.lockedBy = event.user_id;
        
        const badge = document.createElement('div');
        badge.className = 'collab-lock-badge';
        badge.style.cssText = `
            position: absolute;
            top: -10px;
            right: -10px;
            background: ${collab.color};
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            z-index: 100;
        `;
        badge.textContent = `🔒 ${collab.full_name || collab.username}`;
        el.style.position = 'relative';
        el.appendChild(badge);
    }
    
    hideLockIndicator(event) {
        const component = this.editor.getWrapper().find(event.payload.selector)[0];
        if (!component) return;
        
        const el = component.getEl();
        el.style.outline = '';
        delete el.dataset.lockedBy;
        const badge = el.querySelector('.collab-lock-badge');
        if (badge) badge.remove();
    }
    
    renderLocksIndicators() {
        // Limpiar indicadores anteriores
        document.querySelectorAll('.collab-lock-badge').forEach(b => b.remove());
        
        // Renderizar locks actuales
        for (const [selector, lock] of this.locks) {
            if (lock.user_id == window.CURRENT_USER_ID) continue;
            this.showLockIndicator({
                user_id: lock.user_id,
                payload: { selector },
            });
        }
    }
    
    // ═══════════════════════════════════════════════════════════════
    // CHAT
    // ═══════════════════════════════════════════════════════════════
    
    async sendChatMessage(message) {
        await this.api('/send-message', 'POST', { message });
    }
    
    addChatMessage(event) {
        const chat = document.getElementById('collabChatMessages');
        if (!chat) return;
        
        const collab = this.collaborators.get(event.user_id) || {
            username: event.payload.username,
            color: '#8b949e',
            avatar: event.payload.username[0].toUpperCase(),
        };
        
        const msg = document.createElement('div');
        msg.className = 'collab-chat-message';
        msg.innerHTML = `
            <div class="collab-chat-avatar" style="background: ${collab.color}20; color: ${collab.color}">
                ${collab.avatar}
            </div>
            <div class="collab-chat-content">
                <div class="collab-chat-name">${this.escapeHtml(collab.full_name || collab.username)}</div>
                <div class="collab-chat-text">${this.escapeHtml(event.payload.message)}</div>
            </div>
        `;
        chat.appendChild(msg);
        chat.scrollTop = chat.scrollHeight;
    }
    
    // ═══════════════════════════════════════════════════════════════
    // UI
    // ═══════════════════════════════════════════════════════════════
    
    renderUI() {
        // Crear panel de colaboración
        const panel = document.createElement('aside');
        panel.className = 'collab-panel';
        panel.id = 'collabPanel';
        panel.innerHTML = `
            <div class="collab-header">
                <h3><i class="bi bi-people-fill"></i> Colaboración</h3>
                <button class="collab-toggle" onclick="collabClient.togglePanel()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="collab-body">
                <div class="collab-section">
                    <div class="collab-section-title">
                        <i class="bi bi-people"></i> En línea
                        <span class="collab-count" id="collabCount">0</span>
                    </div>
                    <div id="collabCollaborators" class="collab-collaborators">
                        <div class="collab-empty">Cargando...</div>
                    </div>
                </div>
                
                <div class="collab-section">
                    <div class="collab-section-title">
                        <i class="bi bi-chat-dots"></i> Chat
                    </div>
                    <div id="collabChatMessages" class="collab-chat-messages"></div>
                    <div class="collab-chat-input">
                        <input type="text" id="collabChatInput" placeholder="Escribe un mensaje..." 
                               onkeypress="if(event.key==='Enter') collabClient.handleChatSubmit()">
                        <button onclick="collabClient.handleChatSubmit()">
                            <i class="bi bi-send"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(panel);
        
        // Cargar mensajes recientes
        this.loadRecentMessages();
    }
    
    async loadRecentMessages() {
        const result = await this.api('/messages');
        if (!result.success) return;
        
        const chat = document.getElementById('collabChatMessages');
        if (!chat) return;
        
        result.data.reverse().forEach(msg => {
            this.addChatMessage({
                user_id: msg.user_id,
                payload: {
                    message: msg.message,
                    username: msg.username,
                },
            });
        });
    }
    
    async handleChatSubmit() {
        const input = document.getElementById('collabChatInput');
        const message = input.value.trim();
        if (!message) return;
        
        await this.sendChatMessage(message);
        input.value = '';
    }
    
    togglePanel() {
        const panel = document.getElementById('collabPanel');
        panel.classList.toggle('collapsed');
    }
    
    // ═══════════════════════════════════════════════════════════════
    // UTILIDADES
    // ═══════════════════════════════════════════════════════════════
    
    showToast(msg, type = 'info') {
        if (typeof window.showToast === 'function') {
            window.showToast(msg, type);
        } else {
            console.log(`[${type}] ${msg}`);
        }
    }
    
    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    // ═══════════════════════════════════════════════════════════════
    // CLEANUP
    // ═══════════════════════════════════════════════════════════════
    
    destroy() {
        if (this.pollTimer) clearInterval(this.pollTimer);
        if (this.heartbeatTimer) clearInterval(this.heartbeatTimer);
        if (this.broadcastChannel) this.broadcastChannel.close();
        
        // Salir de la sesión
        this.api('/leave', 'POST').catch(() => {});
        
        // Remover cursores remotos
        this.remoteCursors.forEach(c => c.remove());
        
        // Remover panel
        const panel = document.getElementById('collabPanel');
        if (panel) panel.remove();
    }
}

// Instancia global
let collabClient = null;

// Inicializar cuando el builder esté listo
window.initCollaboration = function(pageId, grapesEditor, options) {
    if (collabClient) collabClient.destroy();
    collabClient = new CollaborationClient(pageId, grapesEditor, options);
};

// Limpiar al salir de la página
window.addEventListener('beforeunload', () => {
    if (collabClient) collabClient.destroy();
});