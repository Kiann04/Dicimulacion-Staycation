<!-- Messenger-Style Chatbot -->
<div class="chatbot-wrapper">
  <!-- Floating Button -->
  <button id="ChatbotBtn"
    class="btn btn-primary rounded-circle shadow-lg position-fixed flex justify-center items-center"
    style="bottom: 20px; right: 20px; width: 60px; height: 60px; z-index: 1050;">
    <i class="bi bi-messenger fs-3 text-white"></i>
  </button>

  <!-- Chatbot Window -->
  <div id="ChatbotContainer"
    class="card position-fixed shadow-lg hidden"
    style="bottom: 90px; right: 20px; width: 360px; height: 520px; border-radius: 20px; overflow: hidden; z-index: 1050;">

    <!-- Header -->
    <div class="bg-primary text-white px-3 py-2 d-flex align-items-center justify-content-between">
      <div>
        <h6 class="mb-0 fw-bold">Dicimulacion Staycation</h6>
        <small>Online</small>
      </div>
      <button id="CloseChatBot" class="btn btn-light btn-sm rounded-circle">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <!-- Messages -->
    <div id="messages" class="p-3 overflow-auto" style="height: 340px; background-color: #f0f2f5;">
      <div class="text-center text-muted small mt-2">Start a conversation</div>
    </div>

    <!-- Quick FAQ -->
    <div id="faqButtons" class="px-3 py-2 d-flex flex-wrap gap-2 border-top bg-white"></div>

    <!-- Input -->
    <div class="bg-white border-top px-2 py-2 d-flex align-items-center">
      <input type="text" id="userMessage" class="form-control rounded-pill me-2" placeholder="Aa">
      <button id="sendBtn" class="btn btn-primary rounded-pill px-3">
        <i class="bi bi-send-fill"></i>
      </button>
    </div>
  </div>
</div>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<!-- Script -->
<script>
document.addEventListener("DOMContentLoaded", () => {
  const chatbotBtn = document.getElementById("ChatbotBtn");
  const chatbotContainer = document.getElementById("ChatbotContainer");
  const closeBtn = document.getElementById("CloseChatBot");
  const sendBtn = document.getElementById("sendBtn");
  const input = document.getElementById("userMessage");
  const messages = document.getElementById("messages");
  const faqButtons = document.getElementById("faqButtons");

  // Greetings
  const greetings = [
    "Hi there! 👋 How can I help you today?",
    "Hello! 😊 Planning a staycation? Ask me anything!",
    "Welcome to Dicimulacion Staycation! 🏡 How may I assist?",
    "Good day! ☀️ Need help with booking or checking availability?"
  ];

  chatbotBtn.addEventListener("click", () => {
    chatbotContainer.classList.toggle("hidden");
    if (!chatbotContainer.classList.contains("hidden")) {
      appendMessage(greetings[Math.floor(Math.random() * greetings.length)], "bot");
    }
  });

  closeBtn.addEventListener("click", () => chatbotContainer.classList.add("hidden"));

  // ✅ Expanded FAQ knowledge base (from your paper)
 const faq = [
  // 🧾 BOOKING
  { keywords: ["book","reserve","how to book","make reservation","booking process","how do i book"], reply: "📝 You can book directly on our website! Go to the Booking page, choose your house, select your date, and fill out the booking form." },
  { keywords: ["available","availability","vacant","check house"], reply: "📅 You can check available houses and dates on our Booking page before confirming your stay." },
  { keywords: ["house 1","house one"], reply: "🏠 House 1 offers a cozy stay with modern amenities — you can book it on our Booking page." },
  { keywords: ["house 2","house two"], reply: "🏡 House 2 provides comfort and privacy — you can reserve it directly on the website." },
  { keywords: ["house 3","house three"], reply: "🏡 House 3 can be booked on our website — select it from the Booking page and pick your preferred dates." },
  { keywords: ["house 4","house four"], reply: "🏡 House 4 features elegant interiors and access to shared pool amenities — book it online anytime!" },
  { keywords: ["house 5","house five"], reply: "🏡 House 5 is perfect for groups or families. Check availability on the Booking page." },
  { keywords: ["house 6","house six"], reply: "🏡 House 6 offers relaxing vibes with full amenities — book it directly on our website." },
  { keywords: ["house 7","house seven"], reply: "🏡 House 7 provides a great view and cozy ambiance — available for online booking." },
  { keywords: ["house 8","house eight"], reply: "🏡 House 8 is designed for comfort and convenience. You can reserve it online anytime." },
  { keywords: ["confirm","approval","approved","waiting","pending"], reply: "✅ After you submit your booking, our admin will review it. Once approved, you’ll receive a confirmation email or see it on your account page." },
  { keywords: ["reschedule","move date","change date","change schedule"], reply: "📅 You can reschedule your stay at least 14 days before your booked date. Contact the admin for assistance." },
  { keywords: ["cancel","refund","cancel booking","cancellation"], reply: "❌ Cancellations are not allowed, but you can reschedule at least 14 days in advance. No-shows without notice forfeit the booking payment." },
  { keywords: ["edit booking","change booking","modify booking"], reply: "✏️ To make changes to your booking, please message our admin through the Contact Us page." },

  // 💰 PAYMENT
  { keywords: ["payment","gcash","maya","bank","card","bpi","pay","how to pay","payment options"], reply: "💳 We accept **GCash, debit, and credit card payments**. A **50% downpayment** confirms your booking. After paying, upload your proof of payment on the website." },
  { keywords: ["downpayment","half payment"], reply: "💰 A 50% downpayment is required to confirm your reservation. The remaining balance can be paid before check-in." },
  { keywords: ["full payment","remaining balance"], reply: "💸 You may also pay the full amount in advance, or settle the remaining balance upon arrival." },
  { keywords: ["proof","upload payment","receipt","payment proof"], reply: "📤 Upload your payment proof on your account’s Payment Confirmation section after booking." },
  { keywords: ["confirmation","payment received","payment verified"], reply: "✅ Once your payment is verified by our admin, you’ll receive an email or dashboard notification confirming your booking." },
  { keywords: ["refund policy"], reply: "⚠️ Refunds are not available. However, you may reschedule your stay at least 14 days before your check-in date." },

  // 🕒 CHECK-IN / CHECK-OUT
  { keywords: ["checkin","check-in","check in"], reply: "⏰ Check-in time is **2:00 PM**." },
  { keywords: ["checkout","check-out","check out"], reply: "🕛 Check-out time is **12:00 PM noon**." },
  { keywords: ["late checkout","late check-out","late check in"], reply: "⚠️ Late check-out may incur additional charges. Please inform the admin in advance if needed." },
  { keywords: ["early checkin","early check-in"], reply: "🌅 Early check-in may be allowed depending on availability. Please message the admin for approval." },

  // 🧍‍♂️ GUEST POLICIES
  { keywords: ["extra guest","additional guest","more guests"], reply: "👥 Each extra guest costs **₱500 per person per night**." },
  { keywords: ["guest limit","maximum","how many people","capacity"], reply: "🏠 Each house has its own guest limit, listed on the Booking page." },
  { keywords: ["children","kids","baby","infant"], reply: "👶 Children are allowed! Please include them in your total guest count when booking." },
  { keywords: ["pets","dog","cat","pet friendly"], reply: "🐾 Yes! We are pet-friendly — no extra charge for your furry friends!" },

  // 🏖️ AMENITIES
  { keywords: ["amenities","features","services"], reply: "🌟 Our amenities include WiFi, kitchen, BBQ grill, Netflix, parking, pool access, and pet-friendly rooms!" },
  { keywords: ["wifi","internet"], reply: "🌐 Yes, we have free WiFi in all houses. The password will be provided upon check-in." },
  { keywords: ["pool","swimming","swim"], reply: "🏊 Our pool is shared among every three units and open daily from **8 AM to 10 PM**." },
  { keywords: ["breakfast","food","coffee","snacks"], reply: "☕ Yes! Breakfast is provided, and early guests receive complimentary coffee and snacks." },
  { keywords: ["parking","car","garage"], reply: "🚗 Free parking is available both on-site and along the street." },
  { keywords: ["grill","bbq","barbecue"], reply: "🔥 BBQ grills are available for guests to use near the pool area." },
  { keywords: ["tv","netflix","entertainment"], reply: "📺 Each house comes with a Smart TV and free Netflix access." },

  // 🏡 HOUSE DETAILS
  { keywords: ["houses","rooms","units"], reply: "🏡 We have **8 unique staycation houses**, each with its own design and amenities. You can view them on the 'Houses' page." },
  { keywords: ["long stay","monthly","28 days","long term"], reply: "📆 Yes! We allow long-term stays (28 days or more) with special rates." },
  { keywords: ["cleaning","housekeeping"], reply: "🧹 Rooms are cleaned before every check-in. Extra cleaning services are available upon request." },
  { keywords: ["aircon","air conditioning"], reply: "❄️ All houses include air conditioning for your comfort." },
  { keywords: ["kitchen","cook","cooking"], reply: "🍳 Each unit has a kitchen where you can cook your own meals." },
  { keywords: ["security","safe","guard"], reply: "🛡️ The area is gated with 24/7 security for your safety." },

  // 🔒 ACCOUNT / LOGIN
  { keywords: ["account","login","register","signup","sign up"], reply: "👤 You can register for an account to manage bookings, view payment history, and message the admin." },
  { keywords: ["forgot password","reset password","recover account"], reply: "🔑 You can reset your password on the Login page by clicking 'Forgot Password'." },
  { keywords: ["update account","edit profile"], reply: "⚙️ You can update your profile information and password in your account settings." },

  // 📞 CONTACT
  { keywords: ["contact","admin","owner","message","help","support"], reply: "📞 You can contact the admin via the **Contact Us** tab or send a message directly through your account dashboard." },
  { keywords: ["owner","host"], reply: "👨‍💼 The owner is **Mr. Edgar Fuentes Dicimulacion**, a Computer Engineer with over 7 years of hosting experience." },
  { keywords: ["facebook","social","page","messenger"], reply: "💬 You can reach us on Facebook Messenger via our official Dicimulacion Staycation page." },

  // ❓ GENERAL QUESTIONS
  { keywords: ["rules","policy","house rules"], reply: "📜 Guests must follow the house rules — no loud noise after 10 PM, no smoking indoors, and keep the place clean." },
  { keywords: ["check availability","available date"], reply: "📅 You can check available dates on our Booking page before reserving." },
  { keywords: ["thanks","thank you","thank"], reply: "😊 You're very welcome! We're happy to help you plan your perfect staycation!" },
  { keywords: ["bye","goodbye","see you"], reply: "👋 Goodbye! Hope to see you soon at Dicimulacion Staycation!" }
];
  // Append Message
  function appendMessage(text, sender) {
    const msgDiv = document.createElement("div");
    msgDiv.classList.add("d-flex", "mb-2");

    const bubble = document.createElement("div");
    bubble.textContent = text;
    bubble.classList.add("p-2", "rounded-3", "shadow-sm");

    if (sender === "user") {
      msgDiv.classList.add("justify-content-end");
      bubble.classList.add("bg-primary", "text-white");
    } else {
      msgDiv.classList.add("justify-content-start");
      bubble.classList.add("bg-white", "text-dark");
    }

    msgDiv.appendChild(bubble);
    messages.appendChild(msgDiv);
    messages.scrollTop = messages.scrollHeight;
  }

  // Typing animation
  function showTyping() {
    const typing = document.createElement("div");
    typing.id = "typing";
    typing.classList.add("text-muted", "small", "fst-italic");
    typing.textContent = "Dicimulacion is typing...";
    messages.appendChild(typing);
    messages.scrollTop = messages.scrollHeight;
  }

  function hideTyping() {
    const typing = document.getElementById("typing");
    if (typing) typing.remove();
  }

  // Handle message (FAQ → Gemini)
  async function handleMessage(msg) {
    appendMessage(msg, "user");
    input.value = "";
    showTyping();

    // Check FAQs first
    for (let item of faq) {
      for (let keyword of item.keywords) {
        if (msg.toLowerCase().includes(keyword)) {
          hideTyping();
          appendMessage(item.reply, "bot");
          return;
        }
      }
    }

    // Fallback to Gemini AI
    try {
      const response = await fetch("{{ url('/chat-gemini') }}", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": document.querySelector('meta[name=\"csrf-token\"]').content
        },
        body: JSON.stringify({ message: msg })
      });
      const data = await response.json();
      hideTyping();
      appendMessage(data.reply || "🤖 Sorry, I couldn’t get that. Try asking differently!", "bot");
    } catch (err) {
      hideTyping();
      appendMessage("⚠️ Connection error. Please try again.", "bot");
    }
  }

  sendBtn.addEventListener("click", () => {
    const msg = input.value.trim();
    if (msg) handleMessage(msg);
  });

  input.addEventListener("keypress", e => {
    if (e.key === "Enter") {
      const msg = input.value.trim();
      if (msg) handleMessage(msg);
    }
  });

  // Quick buttons
  const quick = [
    "How to book a stay?",
    "What are your payment options?",
    "What time is check-in?",
    "Are pets allowed?",
    "Can I reschedule my booking?"
  ];
  quick.forEach(q => {
    const b = document.createElement("button");
    b.classList.add("btn", "btn-outline-primary", "btn-sm", "rounded-pill");
    b.textContent = q;
    b.addEventListener("click", () => handleMessage(q));
    faqButtons.appendChild(b);
  });
});
</script>


<!-- Style -->
<style>
.hidden { display: none; }
#messages::-webkit-scrollbar { width: 6px; }
#messages::-webkit-scrollbar-thumb {
  background-color: rgba(0,0,0,0.2);
  border-radius: 4px;
}
</style>
