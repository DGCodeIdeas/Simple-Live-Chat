/**
 * Aether Frontend Lifecycle Logic
 *
 * Implements JWT authentication, optimistic UI, and the "Burst-Pulse" heartbeat loop.
 */

const App = {
    token: localStorage.getItem('aether_jwt'),
    currentUser: JSON.parse(localStorage.getItem('aether_user') || 'null'),
    activeConversation: null,
    lastKnownMicrotime: 0,
    lastUpdateCheck: 0,
    isHeartbeatRunning: false,

    init() {
        this.bindEvents();
        if (this.token) {
            this.showChat();
        } else {
            this.showAuth();
        }
    },

    bindEvents() {
        // Auth Toggles
        $('#show-register').on('click', (e) => {
            e.preventDefault();
            $('#login-form').addClass('hidden');
            $('#register-form').removeClass('hidden');
        });

        $('#show-login').on('click', (e) => {
            e.preventDefault();
            $('#register-form').addClass('hidden');
            $('#login-form').removeClass('hidden');
        });

        // Auth Submissions
        $('#login-form').on('submit', (e) => {
            e.preventDefault();
            const data = {
                email: e.target.email.value,
                password: e.target.password.value
            };
            this.apiPost('/api/auth/login', data, (res) => {
                this.saveSession(res.token, res.user);
                this.showChat();
            });
        });

        $('#register-form').on('submit', (e) => {
            e.preventDefault();
            const data = {
                username: e.target.username.value,
                email: e.target.email.value,
                password: e.target.password.value
            };
            this.apiPost('/api/auth/register', data, (res) => {
                alert('Registration successful! Please login.');
                $('#show-login').click();
            });
        });

        $('#logout-btn').on('click', () => {
            this.logout();
        });

        // Messaging
        $('#message-form').on('submit', (e) => {
            e.preventDefault();
            this.handleSendMessage();
        });

        // Conversation Selection (delegated)
        $('#conversation-list').on('click', '.conversation-item', function() {
            const id = $(this).data('id');
            const title = $(this).find('.conv-title').text();
            App.loadChat(id, title);
        });
    },

    showAuth() {
        $('#chat-screen').addClass('hidden');
        $('#auth-screen').removeClass('hidden');
    },

    showChat() {
        $('#auth-screen').addClass('hidden');
        $('#chat-screen').removeClass('hidden');
        $('#current-username').text(this.currentUser.username);
        this.loadConversations();
        this.startHeartbeat();
    },

    saveSession(token, user) {
        this.token = token;
        this.currentUser = user;
        localStorage.setItem('aether_jwt', token);
        localStorage.setItem('aether_user', JSON.stringify(user));
    },

    logout() {
        localStorage.removeItem('aether_jwt');
        localStorage.removeItem('aether_user');
        this.token = null;
        this.currentUser = null;
        this.isHeartbeatRunning = false;
        location.reload();
    },

    loadConversations() {
        this.apiGet('/api/conversations', (res) => {
            const $list = $('#conversation-list');
            $list.empty();
            res.forEach(conv => {
                const html = `
                    <div class="conversation-item p-4 hover:bg-gray-100 cursor-pointer transition flex items-center" data-id="${conv.id}">
                        <div class="w-12 h-12 rounded-full bg-indigo-100 mr-3 flex items-center justify-center font-bold text-indigo-600">
                            ${(conv.title || 'G').charAt(0)}
                        </div>
                        <div class="flex-grow">
                            <div class="flex justify-between">
                                <span class="font-bold conv-title">${this.escapeHtml(conv.title || 'Group Chat')}</span>
                                ${conv.unread_count > 0 ? `<span class="bg-indigo-600 text-white text-xs px-2 py-1 rounded-full">${conv.unread_count}</span>` : ''}
                            </div>
                            <p class="text-sm text-gray-500 truncate">${this.escapeHtml(conv.last_message || 'No messages yet')}</p>
                        </div>
                    </div>
                `;
                $list.append(html);
            });
        });
    },

    loadChat(id, title) {
        this.activeConversation = id;
        $('#active-chat-title').text(title);
        $('#chat-header').removeClass('hidden');
        $('#chat-feed').empty();

        this.apiGet(`/api/chat/${id}/history`, (res) => {
            res.forEach(msg => {
                this.appendMessage(msg, false);
                if (parseFloat(msg.created_microtime) > this.lastKnownMicrotime) {
                    this.lastKnownMicrotime = parseFloat(msg.created_microtime);
                }
            });
            this.scrollToBottom();
        });
    },

    handleSendMessage() {
        if (!this.activeConversation) return;
        const $input = $('#message-input');
        const content = $input.val().trim();
        if (!content) return;

        const tempId = 'temp-' + Date.now();
        const optimisticMsg = {
            id: tempId,
            content: content,
            username: this.currentUser.username,
            created_microtime: (Date.now() / 1000).toFixed(4),
            is_optimistic: true,
            user_id: 'me'
        };

        this.appendMessage(optimisticMsg);
        this.scrollToBottom();
        $input.val('');

        this.apiPost(`/api/chat/${this.activeConversation}/send`, { message: content, temp_id: tempId }, (res) => {
            $(`[data-id="${tempId}"]`).removeClass('opacity-70').attr('data-id', res.id);
        }, (err) => {
            $(`[data-id="${tempId}"]`).find('.status').text('⚠️ Failed').addClass('text-red-500');
        });
    },

    appendMessage(msg, scroll = true) {
        if ($(`[data-id="${msg.id}"]`).length > 0) return;

        const isOwn = msg.user_id === 'me' || msg.user_id == this.currentUser.id || msg.username == this.currentUser.username;
        const html = `
            <div class="flex flex-col ${isOwn ? 'items-end' : 'items-start'} ${msg.is_optimistic ? 'opacity-70' : ''}" data-id="${msg.id}">
                <span class="text-xs text-gray-400 mb-1 px-2">${this.escapeHtml(msg.username)}</span>
                <div class="chat-bubble ${isOwn ? 'chat-bubble-sent' : 'chat-bubble-received'}">
                    ${this.escapeHtml(msg.content)}
                    <div class="status text-[10px] text-right mt-1 opacity-50"></div>
                </div>
            </div>
        `;
        $('#chat-feed').append(html);
        if (scroll) this.scrollToBottom();
    },

    startHeartbeat() {
        if (this.isHeartbeatRunning) return;
        this.isHeartbeatRunning = true;
        this.heartbeat();
    },

    heartbeat() {
        if (!this.isHeartbeatRunning) return;

        this.apiPost('/api/sync/heartbeat', {
            last_known_microtime: this.lastKnownMicrotime,
            last_update_check: this.lastUpdateCheck
        }, (res) => {
            if (res && res.messages) {
                res.messages.forEach(msg => {
                    this.appendMessage(msg);
                    if (parseFloat(msg.created_microtime) > this.lastKnownMicrotime) {
                        this.lastKnownMicrotime = parseFloat(msg.created_microtime);
                    }
                });
                this.lastUpdateCheck = res.last_update_check;
            }
            // Immediately restart heartbeat
            this.heartbeat();
        }, (err) => {
            // Retry after delay on error
            setTimeout(() => this.heartbeat(), 3000);
        });
    },

    // API Helpers
    apiPost(url, data, success, error) {
        $.ajax({
            url: url,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            headers: this.token ? { 'Authorization': 'Bearer ' + this.token } : {},
            success: success,
            error: (xhr) => {
                if (xhr.status === 401) this.logout();
                if (error) error(xhr);
            }
        });
    },

    apiGet(url, success, error) {
        $.ajax({
            url: url,
            method: 'GET',
            headers: this.token ? { 'Authorization': 'Bearer ' + this.token } : {},
            success: success,
            error: (xhr) => {
                if (xhr.status === 401) this.logout();
                if (error) error(xhr);
            }
        });
    },

    scrollToBottom() {
        const feed = document.getElementById('chat-feed');
        if (feed) feed.scrollTop = feed.scrollHeight;
    },

    escapeHtml(text) {
        if (!text) return '';
        return text.toString().replace(/[&<>"']/g, (m) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[m]));
    }
};

$(document).ready(() => App.init());
