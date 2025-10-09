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
        // Booking & Stay Details
        { keywords: ["checkin", "check-in", "arrival", "check in"], reply: "Check-in time is 2:00 PM." },
        { keywords: ["checkout", "check-out", "departure"], reply: "Check-out time is 12:00 PM." },
        { keywords: ["early checkin", "early check-in"], reply: "Early arrivals are allowed, and we offer complimentary coffee with snacks while waiting." },
        { keywords: ["late checkout", "overtime stay"], reply: "Late check-out may result in additional charges." },
        { keywords: ["additional guest", "extra person", "extra guest"], reply: "An additional ₱500 per extra person will be charged." },
        { keywords: ["walk in", "walkin"], reply: "Walk-in guests are allowed, but it’s best to book in advance to secure your preferred stay." },

        // Room Information & Prices
        { keywords: ["standard room", "price standard"], reply: "Standard Room costs ₱6,000 per night." },
        { keywords: ["family suite", "price family"], reply: "Family Suite costs ₱6,000 per night." },
        { keywords: ["room available", "availability", "available rooms"], reply: "We have 8 uniquely designed rooms, each with different views and styles." },

        // Amenities
        { keywords: ["amenities", "facility", "services"], reply: "We offer Free WiFi, Swimming Pool, Kitchen, BBQ Area, Parking, Netflix, and Pet-Friendly accommodations." },
        { keywords: ["pool", "swimming", "pool access"], reply: "The pool is shared among every three units, and guests can enjoy it during their stay." },
        { keywords: ["breakfast", "food", "meal"], reply: "Breakfast is provided to all guests." },
        { keywords: ["pet", "dog", "cat"], reply: "Yes, we are a pet-friendly staycation!" },

        // Booking & Payments
        { keywords: ["booking", "reserve", "reservation"], reply: "You can book directly through our website by selecting your preferred room and dates." },
        { keywords: ["payment", "gcash", "bank transfer", "pay"], reply: "We accept payments via GCash, Maya, and bank transfer. A 50% down payment is required to secure your booking." },
        { keywords: ["receipt", "invoice", "official receipt"], reply: "We issue official receipts for all confirmed bookings and payments." },

        // Policies
        { keywords: ["cancellation", "cancel", "refund"], reply: "Cancellations are not allowed, but you may reschedule your booking at least 14 days before your stay." },
        { keywords: ["policy", "rules", "terms"], reply: "Guests are expected to follow check-in/out schedules and maintain cleanliness. Damages will be charged accordingly." },

        // Location & Contact
        { keywords: ["location", "where", "address"], reply: "We are located at Falcons Court, Village East Avenue, Angono, 1930 Rizal, Philippines." },
        { keywords: ["contact", "phone", "number"], reply: "You can reach us at 0912-345-6789 or via our website’s Contact Us section." },

        // About
        { keywords: ["dicimulacion", "about staycation", "who"], reply: "Dicimulacion Staycation is a peaceful retreat owned by Mr. Edgar Fuentes Dicimulacion, offering comfort, relaxation, and scenic views." },
        { keywords: ["owner", "edgar", "host"], reply: "Our host, Mr. Edgar Fuentes Dicimulacion, is a Computer Engineer with 7 years of hosting experience and a 4.78-star guest rating." },

        // System Assistance
        { keywords: ["login", "account", "sign in"], reply: "To access your account, click Log In on our homepage and enter your registered email and password." },
        { keywords: ["signup", "register", "create account"], reply: "Click Sign Up to create an account. Fill in your full name, email, phone number, and password to get started." },
        { keywords: ["forgot password", "reset password"], reply: "You can reset your password via the 'Forgot Password' option. A verification link will be sent to your email." },
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