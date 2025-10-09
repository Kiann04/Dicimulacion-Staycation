<!-- Chatbot Wrapper -->
<div class="chatbot-wrapper">
    <!-- Floating Button -->
    <button id="ChatbotBtn" class="btn btn-primary rounded-circle shadow-lg position-fixed"
        style="bottom: 20px; right: 20px; width: 60px; height: 60px; z-index: 1050;">
        <i class="bi bi-chat-dots-fill fs-3"></i>
    </button>

    <!-- Chatbot Container -->
    <div id="ChatbotContainer" class="card shadow-lg position-fixed hidden"
        style="bottom: 90px; right: 20px; width: 320px; height: 450px; z-index: 1050;">

        <!-- Header -->
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span>Dicimulation Staycation</span>
            <button id="CloseChatBot" class="btn btn-sm btn-light">&times;</button>
        </div>

        <!-- Messages -->
        <div id="messages" class="card-body overflow-auto" style="height: 300px; background: #f8f9fa;">
            <!-- Messages will appear here -->
        </div>

        <!-- Input -->
        <div class="card-footer bg-white d-flex">
            <input type="text" id="userMessage" class="form-control me-2" placeholder="Type a message...">
            <button id="sendBtn" class="btn btn-primary">
                <i class="bi bi-send-fill"></i>
            </button>
        </div>
    </div>
</div>

<!-- Bootstrap Icons (for chat/send icon) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<script>
document.addEventListener("DOMContentLoaded", () => {
    const chatbotBtn = document.getElementById("ChatbotBtn");
    const chatbotContainer = document.getElementById("ChatbotContainer");
    const closeBtn = document.getElementById("CloseChatBot");
    const sendBtn = document.getElementById("sendBtn");
    const input = document.getElementById("userMessage");
    const messages = document.getElementById("messages");

    // FAQ database
    const faq = [
        { keywords: ["checkin", "check-in", "arrival"], reply: "Check-in time is 2:00 PM." },
        { keywords: ["checkout", "check-out", "departure"], reply: "Check-out time is 12:00 PM." },
        { keywords: ["standard room", "price standard"], reply: "Standard Room costs ₱2,500 per night." },
        { keywords: ["family suite", "price family"], reply: "Family Suite costs ₱4,500 per night." },
        { keywords: ["amenities", "facility", "services"], reply: "We offer Free WiFi, Pool, Kitchen, Parking, and Netflix." },
        { keywords: ["location", "where", "address"], reply: "We are located in Falcons Court, Village East Avenue, Angono, 1930 Rizal." },
        { keywords: ["contact", "phone", "number"], reply: "You can call us at 0912-345-6789." },
        { keywords: ["dicimulacion", "about staycation"], reply: "Dicimulacion Staycation is a premier retreat destination for relaxation." }
    ];

    // Show/Hide chatbot
    chatbotBtn.addEventListener("click", () => chatbotContainer.classList.toggle("hidden"));
    closeBtn.addEventListener("click", () => chatbotContainer.classList.add("hidden"));

    // Append message
    function appendMessage(text, sender) {
        const div = document.createElement("div");
        div.classList.add("mb-2", "p-2", "rounded");
        if (sender === "user") {
            div.classList.add("bg-primary", "text-white", "ms-auto");
            div.style.maxWidth = "80%";
        } else {
            div.classList.add("bg-light", "text-dark", "me-auto");
            div.style.maxWidth = "80%";
        }
        div.textContent = text;
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }

    // Check FAQ
    function checkFAQ(userMessage) {
        userMessage = userMessage.toLowerCase();
        for (let item of faq) {
            for (let keyword of item.keywords) {
                if (userMessage.includes(keyword)) {
                    return item.reply;
                }
            }
        }
        return null;
    }

    // Send message
    function sendMessage() {
        let rawMessage = input.value.trim();
        if (!rawMessage) return;

        appendMessage(rawMessage, "user");
        input.value = "";

        let faqReply = checkFAQ(rawMessage);
        if (faqReply) {
            appendMessage(faqReply, "bot");
            return;
        }

        // Server call
        const typingDiv = document.createElement("div");
        typingDiv.classList.add("bg-light", "p-2", "rounded", "me-auto");
        typingDiv.textContent = "Typing...";
        messages.appendChild(typingDiv);

        fetch("{{ route('chatbot.ask') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({ message: rawMessage })
        })
        .then(res => res.json())
        .then(data => {
            typingDiv.remove();
            appendMessage(data.reply, "bot");
        })
        .catch(err => {
            typingDiv.remove();
            appendMessage("Error: Could not connect to the server.", "bot");
        });
    }

    sendBtn.addEventListener("click", sendMessage);
    input.addEventListener("keypress", e => { if (e.key === "Enter") sendMessage(); });
});
</script>

<style>
/* Hide chatbot initially */
.hidden {
    display: none;
}
</style>