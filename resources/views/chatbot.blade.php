<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gemini Chatbot</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        #chatbox {
            background: white;
            padding: 15px;
            border-radius: 10px;
            width: 400px;
            margin: auto;
            height: 400px;
            overflow-y: auto;
            border: 1px solid #ccc;
        }
        .message {
            margin: 5px 0;
            padding: 8px;
            border-radius: 5px;
        }
        .user {
            background: #007bff;
            color: white;
            text-align: right;
        }
        .bot {
            background: #e9ecef;
            color: black;
            text-align: left;
        }
        #input-area {
            margin-top: 15px;
            display: flex;
            justify-content: center;
        }
        input {
            width: 300px;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            margin-left: 5px;
            padding: 8px 15px;
            background: #28a745;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
        }
        button:hover {
            background: #218838;
        }
    </style>
</head>
<body>

    <h1 style="text-align:center;">Gemini Chatbot</h1>
    <div id="chatbox"></div>

    <div id="input-area">
        <input type="text" id="messageInput" placeholder="Type your message">
        <button id="sendBtn">Send</button>
    </div>

    <script>
    const chatbox = document.getElementById("chatbox");
    const input = document.getElementById("messageInput");
    const sendBtn = document.getElementById("sendBtn");

    function appendMessage(text, sender) {
        const div = document.createElement("div");
        div.classList.add("message", sender);
        div.textContent = text;
        chatbox.appendChild(div);
        chatbox.scrollTop = chatbox.scrollHeight;
    }

    sendBtn.addEventListener("click", function() {
        let userMessage = input.value.trim();
        if (!userMessage) return;

        appendMessage(userMessage, "user");
        input.value = "";

        fetch("/chat", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ message: userMessage })
        })
        .then(res => res.json())
        .then(data => {
            appendMessage(data.reply, "bot");
        })
        .catch(err => {
            appendMessage("Error: Could not connect to the server.", "bot");
        });
    });
    </script>

</body>
</html>
