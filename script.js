document.addEventListener('DOMContentLoaded', () => {

    // ===============================
    // Mobile Menu
    // ===============================
    const mobileMenuBtn = document.getElementById('mobile-menu');
    const navLinks = document.querySelector('.nav-links');

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    }

    // ===============================
    // Sticky Navbar
    // ===============================
    window.addEventListener('scroll', () => {
        const navbar = document.getElementById('navbar');
        if (!navbar) return;

        if (window.scrollY > 50) {
            navbar.style.background = 'rgba(5, 5, 5, 0.95)';
            navbar.style.boxShadow = '0 5px 15px rgba(0,0,0,0.5)';
        } else {
            navbar.style.background = 'rgba(5, 5, 5, 0.8)';
            navbar.style.boxShadow = 'none';
        }
    });

    // ===============================
    // BMI Calculator
    // ===============================
    const calculateBtn = document.getElementById('calculate-bmi');
    if (calculateBtn) {
        calculateBtn.addEventListener('click', () => {
            const weight = parseFloat(document.getElementById('bmi-weight')?.value);
            const height = parseFloat(document.getElementById('bmi-height')?.value);

            if (weight > 0 && height > 0) {
                const h = height / 100;
                const bmi = (weight / (h * h)).toFixed(2);

                let category = "Normal";
                if (bmi < 18.5) category = "Underweight";
                else if (bmi >= 25 && bmi < 30) category = "Overweight";
                else if (bmi >= 30) category = "Obese";

                document.getElementById('bmi-value').innerHTML = `${bmi} (${category})`;
                document.getElementById('bmi-result-display').style.display = 'block';
            } else {
                alert("Enter valid height and weight");
            }
        });
    }

    // =====================================================
    // 🤖 AI HEALTH ASSISTANT CHAT LOGIC (FINAL + WORKING)
    // =====================================================
    const chatInput = document.getElementById("chat-input");
    const chatSend = document.getElementById("chat-send");
    const chatMessages = document.getElementById("chat-messages");

    console.log("Chat JS loaded");

    if (!chatInput || !chatSend || !chatMessages) {
        console.warn("AI Chat elements not found on this page");
        return;
    }

    function addMessage(text, sender = "bot") {
        const msg = document.createElement("div");
        msg.className = sender === "user" ? "user-msg" : "bot-msg";
        msg.textContent = text;
        chatMessages.appendChild(msg);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    chatSend.addEventListener("click", sendMessage);
    chatInput.addEventListener("keydown", (e) => {
        if (e.key === "Enter") sendMessage();
    });

    function sendMessage() {
        const message = chatInput.value.trim();
        if (!message) return;

        console.log("Sending message:", message);

        addMessage(message, "user");
        chatInput.value = "";

        fetch("/Gym-Fit-master/ai_chat.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ message })
        })
        .then(res => res.json())
        .then(data => {
            console.log("AI response:", data);
            addMessage(data.reply || "No reply from AI");
        })
        .catch(err => {
            console.error("AI Fetch error:", err);
            addMessage("I'm having trouble connecting to the AI.");
        });
    }

});
