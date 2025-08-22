//CHATBOT
document.addEventListener("DOMContentLoaded", function () {
    const chatbotBtn = document.getElementById("ChatbotBtn");
    const chatbotContainer = document.getElementById("ChatbotContainer");
    const closeChatbot = document.getElementById("CloseChatBot");
    const faqQuestions = document.querySelectorAll(".FaqQuestion");

    chatbotBtn.addEventListener("click", function () {
        chatbotContainer.classList.toggle("hidden");
    });

    closeChatbot.addEventListener("click", function () {
        chatbotContainer.classList.add("hidden");
    });

    faqQuestions.forEach(question => {
        question.addEventListener("click", function () {
            const answer = this.nextElementSibling;
            answer.style.display = answer.style.display === "block" ? "none" : "block";
        });
    });
});
