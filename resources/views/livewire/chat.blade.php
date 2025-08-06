<div>
    <div>
        <div class="bg-slate-50 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="grid grid-cols-1 lg:grid-cols-4">

                <!-- Active Users Sidebar -->
                <div class="lg:col-span-1 lg:border-r border-slate-200">
                    <div class="bg-slate-100/60 p-4 h-full">
                        <h3 class="font-semibold text-slate-800 mb-4 flex items-center">
                            <span class="relative flex h-3 w-3 mr-2">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                            </span>
                            Active Now (<span id="user-count">0</span>)
                        </h3>
                        <div id="active-users-list" class="space-y-2">
                            <div class="text-center text-slate-500 text-sm py-8">Loading users...</div>
                        </div>
                    </div>
                </div>

                <!-- Main Chat Area -->
                <div class="lg:col-span-3 flex flex-col h-[calc(100vh-150px)]">
                    <!-- Messages Container -->
                    <div id="messages-container" class="flex-grow overflow-y-auto p-6 space-y-6 bg-white">
                        <!-- Welcome message, shown when the chat is empty -->
                        <div class="text-center text-slate-500 py-8">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-slate-400"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <p class="mt-4 text-lg font-medium">Welcome to the Chat Room!</p>
                            <p class="text-sm">Messages will appear here. Start a conversation below.</p>
                        </div>
                    </div>

                    <!-- Message Input Area -->
                    <div class="bg-slate-100 p-4 border-t border-slate-200">
                        <div class="relative">
                            <input type="text" wire:model="message" wire:keydown.enter="sendMessage"
                                placeholder="Type your message here..."
                                class="w-full rounded-full border-slate-300 shadow-sm pl-4 pr-20 py-3 focus:border-indigo-500 focus:ring-indigo-500 transition"
                                maxlength="500">

                            <!-- Character Counter -->
                            <div class="absolute bottom-2 right-24 text-right text-xs text-slate-400">
                                <span wire:ignore id="char-counter">0</span>/500
                            </div>

                            <!-- Send Button -->
                            <button wire:click="sendMessage"
                                class="absolute right-2 top-1/2 -translate-y-1/2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-full transition duration-150 ease-in-out flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 -rotate-45" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path
                                        d="M10.894 2.553a1 1 0 00-1.789 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- JavaScript for Real-time Features -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const messageInput = document.querySelector('input[wire\\:model="message"]');
                    const charCounter = document.getElementById('char-counter');
                    const messagesContainer = document.getElementById('messages-container');

                    // Update character counter on input
                    messageInput.addEventListener('input', function() {
                        charCounter.textContent = this.value.length;
                    });

                    // Function to scroll to the bottom of the messages container
                    function scrollToBottom() {
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }

                    // Connect to Laravel Echo (Reverb)
                    window.Echo.join('chat-room')
                        .here((users) => {
                            updateActiveUsers(users);
                        })
                        .joining((user) => {
                            addUserNotification(`${user.name} joined the chat.`, 'join');
                            // The full list will be updated by the .here() call that follows a join/leave
                        })
                        .leaving((user) => {
                            addUserNotification(`${user.name} left the chat.`, 'leave');
                        })
                        .listen('MessageSent', (e) => {
                            addMessage(e.message, e.user, e.timestamp);
                        });

                    function updateActiveUsers(users) {
                        const usersList = document.getElementById('active-users-list');
                        const userCount = document.getElementById('user-count');
                        const currentUserId = {{ Auth::id() }};

                        userCount.textContent = users.length;

                        if (users.length > 0) {
                            usersList.innerHTML = users.map(user => `
                            <div class="flex items-center space-x-3 p-2 bg-white/50 hover:bg-white rounded-lg transition-colors duration-200 cursor-pointer">
                                <div class="relative">
                                    <img src="${user.avatar || 'https://placehold.co/40x40/E2E8F0/475569?text=' + user.name.charAt(0)}" alt="${user.name}" class="w-10 h-10 rounded-full">
                                    <span class="absolute bottom-0 right-0 block h-2.5 w-2.5 rounded-full bg-green-500 ring-2 ring-white"></span>
                                </div>
                                <span class="text-sm font-medium text-slate-800 truncate">${user.name}</span>
                                ${user.id === currentUserId ? '<span class="text-xs text-indigo-600 font-semibold ml-auto">(You)</span>' : ''}
                            </div>
                        `).join('');
                        } else {
                            usersList.innerHTML =
                                '<div class="text-center text-slate-500 text-sm py-8">No active users.</div>';
                        }
                    }

                    function addMessage(message, user, timestamp) {
                        const messageElement = document.createElement('div');
                        const isCurrentUser = user.id === {{ Auth::id() }};

                        // Clear the welcome message if it's the first message
                        const welcomeMessage = messagesContainer.querySelector('.text-center');
                        if (welcomeMessage) {
                            welcomeMessage.remove();
                        }

                        messageElement.className = `flex items-start gap-3 ${isCurrentUser ? 'flex-row-reverse' : ''}`;
                        messageElement.innerHTML = `
                        <img src="${user.avatar || 'https://placehold.co/40x40/E2E8F0/475569?text=' + user.name.charAt(0)}" alt="${user.name}" class="w-8 h-8 rounded-full">
                        <div class="max-w-xs lg:max-w-md">
                            <div class="px-4 py-3 rounded-xl ${isCurrentUser ? 'bg-indigo-600 text-white rounded-br-none' : 'bg-slate-100 text-slate-800 rounded-bl-none'}">
                                <p class="text-sm">${message}</p>
                            </div>
                            <div class="text-xs text-slate-400 mt-1 px-1 ${isCurrentUser ? 'text-right' : 'text-left'}">
                                ${user.name}, ${new Date(timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                            </div>
                        </div>
                    `;

                        messagesContainer.appendChild(messageElement);
                        scrollToBottom();
                    }

                    function addUserNotification(message, type) {
                        const notificationElement = document.createElement('div');
                        notificationElement.className = 'text-center my-2';
                        notificationElement.innerHTML = `
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-medium ${
                            type === 'join' 
                                ? 'bg-green-100 text-green-800' 
                                : 'bg-rose-100 text-rose-800'
                        }">
                            ${message}
                        </span>
                    `;
                        messagesContainer.appendChild(notificationElement);
                        scrollToBottom();
                    }
                });
            </script>
        </div>
    </div>

</div>
