<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot AI (Gemini)</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-900 h-screen flex items-center justify-center font-sans antialiased">

    <div class="w-full max-w-2xl bg-white shadow-2xl rounded-2xl overflow-hidden flex flex-col h-[85vh]">
        
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-5 flex items-center gap-3 shadow-md z-10">
            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-white font-bold text-xl">
                AI
            </div>
            <div>
                <h1 class="text-white font-bold text-lg">Asisten Cerdas</h1>
                <p class="text-blue-100 text-xs">Powered by Google Gemini</p>
            </div>
        </div>

        <div id="chat-container" class="flex-1 p-5 overflow-y-auto space-y-4 bg-slate-50 scroll-smooth">
            <div class="flex justify-start animate-fade-in-up">
                <div class="bg-white border border-gray-200 text-gray-800 px-5 py-3 rounded-2xl rounded-tl-none shadow-sm max-w-[80%]">
                    Halo! Saya siap membantu. Tanyakan apa saja! 🤖
                </div>
            </div>
        </div>

        <div class="p-4 bg-white border-t border-gray-100">
            <div id="error-alert" class="hidden mb-3 bg-red-100 border border-red-200 text-red-700 px-4 py-2 rounded-lg text-sm">
                Terjadi kesalahan. Cek koneksi atau coba lagi.
            </div>

            <form id="chat-form" class="flex gap-3 items-end">
                <div class="relative flex-1">
                    <textarea id="message-input" rows="1"
                        class="w-full bg-gray-100 border-0 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:bg-white transition resize-none"
                        placeholder="Ketik pesan kamu di sini..."></textarea>
                </div>
                <button type="submit" id="send-btn"
                    class="bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-xl transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <script>
        const chatContainer = document.getElementById('chat-container');
        const chatForm = document.getElementById('chat-form');
        const messageInput = document.getElementById('message-input');
        const sendBtn = document.getElementById('send-btn');
        const errorAlert = document.getElementById('error-alert');

        // Auto-resize textarea
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
            if(this.value === '') this.style.height = 'auto';
        });

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = messageInput.value.trim();
            if (!message) return;

            // 1. UI Updates (User Message)
            appendMessage('user', message);
            messageInput.value = '';
            messageInput.style.height = 'auto';
            errorAlert.classList.add('hidden');
            
            // Set Loading State
            sendBtn.disabled = true;
            
            // Tambahkan bubble loading sementara
            const loadingId = appendLoadingBubble();

            try {
                // 2. Fetch ke Backend
                const response = await fetch('/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ message: message })
                });

                // Hapus bubble loading
                document.getElementById(loadingId).remove();

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Gagal menghubungi server');
                }

                // 3. Tampilkan Balasan Bot
                appendMessage('bot', data.reply);

            } catch (error) {
                // Hapus bubble loading jika masih ada
                const loadingBubble = document.getElementById(loadingId);
                if(loadingBubble) loadingBubble.remove();

                console.error('Error:', error);
                errorAlert.textContent = error.message;
                errorAlert.classList.remove('hidden');
            } finally {
                sendBtn.disabled = false;
                scrollToBottom();
            }
        });

        function appendMessage(sender, text) {
            const div = document.createElement('div');
            div.className = `flex ${sender === 'user' ? 'justify-end' : 'justify-start'} animate-fade-in`;
            
            // Convert newline ke <br> untuk bot response
            const formattedText = text.replace(/\n/g, '<br>');

            const bubbleClass = sender === 'user' 
                ? 'bg-blue-600 text-white rounded-br-none' 
                : 'bg-white border border-gray-200 text-gray-800 rounded-tl-none';

            div.innerHTML = `
                <div class="${bubbleClass} px-5 py-3 rounded-2xl shadow-sm max-w-[85%] text-sm leading-relaxed">
                    ${formattedText}
                </div>
            `;
            
            chatContainer.appendChild(div);
            scrollToBottom();
        }

        function appendLoadingBubble() {
            const id = 'loading-' + Date.now();
            const div = document.createElement('div');
            div.id = id;
            div.className = 'flex justify-start animate-pulse';
            div.innerHTML = `
                <div class="bg-gray-200 px-4 py-3 rounded-2xl rounded-tl-none flex gap-1 items-center">
                    <div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce"></div>
                    <div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce delay-100"></div>
                    <div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce delay-200"></div>
                </div>
            `;
            chatContainer.appendChild(div);
            scrollToBottom();
            return id;
        }

        function scrollToBottom() {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    </script>

    <style>
        /* Animasi sederhana */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }
    </style>
</body>
</html>