<!DOCTYPE html>
<html>
<head>
    <title>Dicimulacion Staycation</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: Arial; padding: 20px; }
        #chat { border: 1px solid #ccc; padding: 10px; height: 300px; overflow-y: scroll; }
        input { width: 80%; padding: 5px; }
        button { padding: 5px; }
        .user { color: blue; }
        .bot { color: green; }
    </style>
</head>
<body>
    <h2>Dicimulacion Staycation</h2>
    <div id="chat"></div>
    <input type="text" id="message" placeholder="Ask something..." />
    <button onclick="sendMessage()">Send</button>

    <script>
        const chatDiv = document.getElementById('chat');

        function appendMessage(sender, text) {
            const p = document.createElement('p');
            p.className = sender;
            p.textContent = sender + ': ' + text;
            chatDiv.appendChild(p);
            chatDiv.scrollTop = chatDiv.scrollHeight;
        }

        function sendMessage() {
            const message = document.getElementById('message').value;
            if (!message) return;

            appendMessage('You', message);

            fetch('/offline-chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ message })
            })
            .then(res => res.json())
            .then(data => appendMessage('Bot', data.reply))
            .catch(err => appendMessage('Bot', 'Error: ' + err));

            document.getElementById('message').value = '';
        }
    </script>
</body>
</html>
