<x-app-layout>


    @section('content')

        <body class="bg-gradient-to-r from-indigo-50 to-purple-50 min-h-screen flex items-center justify-center p-4">

            <div
                class="bg-white mt-[30px] p-[30px] h-[80vh] flex flex-col overflow-hidden max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between bg-blue-500 px-6 py-4">
                    <h2 class="text-white text-xl font-semibold">What can I help with?</h2>
                    <button class="bg-white text-indigo-600 px-4 py-1 rounded-lg font-medium shadow hover:bg-gray-100">New
                        Chat</button>
                </div>

                <!-- Message Area -->
                <div id="chatArea" class="flex-1 px-6 py-4 overflow-y-auto space-y-4 bg-indigo-50">
                    @foreach ($chats as $chat)
                        <div class="flex {{ $chat->user_id == auth()->id() ? 'justify-end' : '' }}">
                            <div
                                class="{{ $chat->user_id == auth()->id() ? 'bg-blue-500 text-white' : 'bg-white text-gray-800' }} p-3 rounded-2xl max-w-xs shadow-md">
                                {{ $chat->user_id == auth()->id() ? $chat->user_message : $chat->bot_response }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Input Area -->
                <div class="flex items-center px-6 py-4 gap-3 bg-white border-t border-gray-200">
                    <input id="userInput" type="text" placeholder="Type a message..." maxlength="300"
                        class="flex-1 border border-gray-300 rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    <button id="sendBtn"
                        class="bg-blue-500 text-white px-4 py-2 rounded-full shadow hover:bg-indigo-700">Send</button>
                </div>
            </div>

            <script>
                const chatArea = document.getElementById('chatArea');
                const userInput = document.getElementById('userInput');
                const sendBtn = document.getElementById('sendBtn');

                sendBtn.addEventListener('click', sendMessage);
                userInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') sendMessage();
                });

                async function sendMessage() {
                    const message = userInput.value.trim();

                    // --- Validation ---
                    if (message === '') {
                        alert('⚠️ Please type a message before sending.');
                        return;
                    }

                    if (message.length > 300) {
                        alert('⚠️ Message too long! Maximum 300 characters allowed.');
                        return;
                    }

                    // --- Display user message instantly ---
                    const senderDiv = document.createElement('div');
                    senderDiv.classList.add('flex', 'justify-end');
                    senderDiv.innerHTML = `
            <div class="bg-blue-500 text-white p-3 rounded-2xl max-w-xs shadow-md">
                ${message}
            </div>`;
                    chatArea.appendChild(senderDiv);
                    chatArea.scrollTop = chatArea.scrollHeight;

                    userInput.value = '';

                    // --- Send to backend via fetch ---
                    try {
                        const res = await fetch('{{ route('chat.send') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                message
                            })
                        });

                        if (!res.ok) {
                            throw new Error(`Server error: ${res.status}`);
                        }

                        const data = await res.json();

                        // --- Display bot response ---
                        const botDiv = document.createElement('div');
                        botDiv.classList.add('flex');
                        botDiv.innerHTML = `
                <div class="bg-white shadow-md text-gray-800 p-3 rounded-2xl max-w-xs">
                    🤖 ${data.bot_response ?? 'No response from bot.'}
                </div>`;
                        chatArea.appendChild(botDiv);
                        chatArea.scrollTop = chatArea.scrollHeight;

                    } catch (error) {
                        console.error('Chat send failed:', error);
                        alert('Something went wrong while sending the message!');
                    }
                }
            </script>



            <script>
                // Chat load on page load
                window.onload = function() {
                    fetch('/api/chat/history')
                        .then(res => res.json())
                        .then(data => {
                            data.forEach(chat => {
                                showMessage(chat.user_message, 'user');
                                showMessage(chat.bot_response, 'bot');
                            });
                        });
                };

                // Send new message
                function sendMessage() {
                    const message = document.getElementById('messageInput').value;

                    fetch('/api/chat', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                message: message
                            }),
                        })
                        .then(res => res.json())
                        .then(data => {
                            showMessage(data.message, 'user');
                            showMessage(data.response, 'bot');
                        });
                }
            </script>

        </body>



    </x-app-layout>
