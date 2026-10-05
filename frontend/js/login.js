document.addEventListener("DOMContentLoaded", function () {
    setupLoginForm();
});

function setupLoginForm() {
    const form = document.getElementById("loginForm");

    if (!form) return;

    form.addEventListener("submit", function (event) {
        event.preventDefault();
        loginUser();
    });
}

function loginUser() {
    const email = document.getElementById("email")?.value.trim() || "";
    const password = document.getElementById("password")?.value || "";

    if (email === "" || password === "") {
        alert("Please enter your email and password.");
        return;
    }

    const savedUser = localStorage.getItem("pharmaUser");

    if (!savedUser) {
        alert("No account found. Please signup first.");
        return;
    }

    let user;

    try {
        user = JSON.parse(savedUser);
    } catch (error) {
        alert("Invalid account data. Please signup again.");
        return;
    }

    if (user.email !== email || user.password !== password) {
        alert("Invalid email or password.");
        return;
    }

    localStorage.setItem("pharmaLoggedIn", "true");

    alert("Login successful!");

    window.location.href = "profile.html";
}