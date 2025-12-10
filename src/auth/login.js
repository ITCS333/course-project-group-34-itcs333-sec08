// --- Element Selections ---
const loginForm = document.getElementById("login-form");
const emailInput = document.getElementById("email");
const passwordInput = document.getElementById("password");
const messageContainer = document.getElementById("message-container");

// --- Functions ---

function displayMessage(message, type) {
    messageContainer.textContent = message;
    messageContainer.className = type;
}

function isValidEmail(email) {
    return /\S+@\S+\.\S+/.test(email);
}

function isValidPassword(password) {
    return password.length >= 8;
}

async function handleLogin(event) {
    event.preventDefault();

    const email = emailInput.value.trim();
    const password = passwordInput.value;

    if (!isValidEmail(email)) {
        displayMessage("Invalid email format.", "error");
        return;
    }

    if (!isValidPassword(password)) {
        displayMessage("Password must be at least 8 characters.", "error");
        return;
    }

    try {
        const response = await fetch("api/index.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "include",
            body: JSON.stringify({ email, password })
        });

        const result = await response.json();

        if (!result.success) {
            displayMessage(result.message, "error");
            return;
        }

        displayMessage("Login successful!", "success");

        window.location.href = "../../index.html";

    } catch (err) {
        displayMessage("Server error. Try again later.", "error");
    }
}

loginForm.addEventListener("submit", handleLogin);
