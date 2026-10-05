document.addEventListener("DOMContentLoaded", function () {
    setupSignupForm();
});

function setupSignupForm() {
    const form = document.getElementById("signupForm");

    if (!form) return;

    form.addEventListener("submit", function (event) {
        event.preventDefault();
        signupUser();
    });
}

function signupUser() {
    const fullName = document.getElementById("fullName")?.value.trim() || "";
    const email = document.getElementById("email")?.value.trim() || "";
    const mobile = document.getElementById("mobile")?.value.trim() || "";
    const password = document.getElementById("password")?.value || "";
    const confirmPassword =
        document.getElementById("confirmPassword")?.value || "";

    if (fullName === "" || email === "" || mobile === "" || password === "") {
        alert("Please fill in all required fields.");
        return;
    }

    if (password !== confirmPassword) {
        alert("Passwords do not match.");
        return;
    }

    const user = {
        fullName: fullName,
        name: fullName,
        email: email,
        mobile: mobile,
        password: password
    };

    localStorage.setItem("pharmaUser", JSON.stringify(user));

    localStorage.setItem(
        "pharmaProfile",
        JSON.stringify({
            fullName: fullName,
            mobile: mobile,
            email: email,
            address: "",
            city: "",
            pin: ""
        })
    );

    alert("Signup successful! Please login.");

    window.location.href = "login.html";
}