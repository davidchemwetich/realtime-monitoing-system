<div>
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 p-6">

            <!-- Active Users Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center">
                        <span class="w-3 h-3 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                        Active Users (<span id="user-count">0</span>)
                    </h3>
                    <div id="active-users-list" class="space-y-2">
                        <!-- Users will be populated by JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="lg:col-span-3">
                <!-- Messages Container -->
                <div class="border border-gray-200 rounded-lg mb-4">
                    <div id="messages-container" class="h-96 overflow-y-auto p-4 bg-gray-50">
                        <div class="text-center text-gray-500 py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.955 8.955 0 01-4.126-.964L3 20l1.036-5.874A8.955 8.955 0 013 12a8 8 0 018-8 8 8 0 018 8z" />
                            </svg>
                            <p class="mt-2">Welcome to the chat! Start a conversation...</p>
                        </div>
                    </div>
                </div>

                <!-- Message Input -->
                <div class="flex gap-2">
                    <input type="text" wire:model="message" wire:keydown.enter="sendMessage"
                        placeholder="Type your message..."
                        class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        maxlength="500">
                    <button wire:click="sendMessage"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                        Send
                    </button>
                </div>

                <!-- Character Counter -->
                <div class="text-right text-sm text-gray-500 mt-1">
                    <span wire:ignore id="char-counter">0</span>/500
                </div>
            </div>
        </div>

        <!-- JavaScript for Real-time Features -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Character counter
                const messageInput = document.querySelector('input[wire\\:model="message"]');
                const charCounter = document.getElementById('char-counter');

                messageInput.addEventListener('input', function() {
                    charCounter.textContent = this.value.length;
                });

                // Connect to Reverb
                window.Echo.join('chat-room')
                    .here((users) => {
                        updateActiveUsers(users);
                    })
                    .joining((user) => {
                        addUserNotification(user.name + ' joined the chat', 'join');
                        // Will be updated by .here() callback
                    })
                    .leaving((user) => {
                        addUserNotification(user.name + ' left the chat', 'leave');
                        // Will be updated by .here() callback
                    })
                    .listen('MessageSent', (e) => {
                        addMessage(e.message, e.user, e.timestamp);
                    });

                // Handle Livewire message sending
                Livewire.on('send-message', (event) => {
                    fetch('/notify', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content')
                            },
                            body: JSON.stringify({
                                message: event.message
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (!data.success) {
                                alert('Failed to send message');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Failed to send message');
                        });
                });

                function updateActiveUsers(users) {
                    const usersList = document.getElementById('active-users-list');
                    const userCount = document.getElementById('user-count');

                    userCount.textContent = users.length;

                    usersList.innerHTML = users.map(user => `
                    <div class="flex items-center space-x-2 p-2 bg-white rounded">
                        <img src="${user.avatar}" alt="${user.name}" class="w-8 h-8 rounded-full">
                        <span class="text-sm font-medium text-gray-900">${user.name}</span>
                        ${user.id === {{ Auth::id() }} ? '<span class="text-xs text-blue-600">(You)</span>' : ''}
                    </div>
                `).join('');
                }

                function addMessage(message, user, timestamp) {
                    const messagesContainer = document.getElementById('messages-container');
                    const messageElement = document.createElement('div');
                    const isCurrentUser = user.id === {{ Auth::id() }};

                    messageElement.className = `mb-4 ${isCurrentUser ? 'text-right' : 'text-left'}`;
                    messageElement.innerHTML = `
                    <div class="inline-block max-w-xs lg:max-w-md px-4 py-2 rounded-lg ${
                        isCurrentUser 
                            ? 'bg-blue-600 text-white' 
                            : 'bg-white border border-gray-200'
                    }">
                        <div class="font-medium text-sm ${isCurrentUser ? 'text-blue-100' : 'text-gray-600'} mb-1">
                            ${user.name}
                        </div>
                        <div>${message}</div>
                        <div class="text-xs ${isCurrentUser ? 'text-blue-200' : 'text-gray-400'} mt-1">
                            ${new Date(timestamp).toLocaleTimeString()}
                        </div>
                    </div>
                `;

                    // Remove welcome message if it exists
                    const welcomeMessage = messagesContainer.querySelector('.text-center');
                    if (welcomeMessage) {
                        welcomeMessage.remove();
                    }

                    messagesContainer.appendChild(messageElement);
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }

                function addUserNotification(message, type) {
                    const messagesContainer = document.getElementById('messages-container');
                    const notificationElement = document.createElement('div');

                    notificationElement.className = 'text-center my-2';
                    notificationElement.innerHTML = `
                    <span class="inline-block px-3 py-1 rounded-full text-xs ${
                        type === 'join' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                    }">
                        ${message}
                    </span>
                `;

                    messagesContainer.appendChild(notificationElement);
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            });
        </script>
    </div>
</div>
