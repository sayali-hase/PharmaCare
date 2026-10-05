document.addEventListener("DOMContentLoaded", function () {
    loadProfile();
    updateProfileCartCount();
    setupProfileForm();
});

function loadProfile() {
    const savedProfile = JSON.parse(localStorage.getItem("pharmaProfile")) || {};
    const savedUser = localStorage.getItem("pharmaUser");

    let user = {};

    try {
        user = JSON.parse(savedUser) || {};
    } catch (error) {
        user = {
            email: savedUser || ""
        };
    }

    const fullName = savedProfile.fullName || user.fullName || user.name || "";
    const mobile = savedProfile.mobile || user.mobile || "";
    const email = savedProfile.email || user.email || "";
    const address = savedProfile.address || "";
    const city = savedProfile.city || "";
    const pin = savedProfile.pin || "";

    const fullNameInput = document.getElementById("fullName");
    const mobileInput = document.getElementById("mobile");
    const emailInput = document.getElementById("email");
    const addressInput = document.getElementById("address");
    const cityInput = document.getElementById("city");
    const pinInput = document.getElementById("pin");

    if (fullNameInput) fullNameInput.value = fullName;
    if (mobileInput) mobileInput.value = mobile;
    if (emailInput) emailInput.value = email;
    if (addressInput) addressInput.value = address;
    if (cityInput) cityInput.value = city;
    if (pinInput) pinInput.value = pin;

    const profileName = document.getElementById("profileName");
    const profileEmail = document.getElementById("profileEmail");

    if (profileName) {
        profileName.textContent = fullName || "PharmaCare User";
    }

    if (profileEmail) {
        profileEmail.textContent = email || "No email added";
    }
}

function setupProfileForm() {
    const form = document.getElementById("profileForm");

    if (!form) return;

    form.addEventListener("submit", function (event) {
        event.preventDefault();
        saveProfile();
    });
}

function saveProfile() {
    const fullName = document.getElementById("fullName")?.value.trim() || "";
    const mobile = document.getElementById("mobile")?.value.trim() || "";
    const email = document.getElementById("email")?.value.trim() || "";
    const address = document.getElementById("address")?.value.trim() || "";
    const city = document.getElementById("city")?.value.trim() || "";
    const pin = document.getElementById("pin")?.value.trim() || "";

    if (fullName === "" || mobile === "" || email === "") {
        alert("Please fill in all required fields.");
        return;
    }

    const profile = {
        fullName: fullName,
        mobile: mobile,
        email: email,
        address: address,
        city: city,
        pin: pin
    };

    localStorage.setItem("pharmaProfile", JSON.stringify(profile));

    const savedUser = localStorage.getItem("pharmaUser");

    if (savedUser) {
        try {
            const user = JSON.parse(savedUser);

            if (typeof user === "object" && user !== null) {
                user.fullName = fullName;
                user.name = fullName;
                user.mobile = mobile;
                user.email = email;

                localStorage.setItem("pharmaUser", JSON.stringify(user));
            }
        } catch (error) {
            localStorage.setItem("pharmaUser", email);
        }
    }

    const profileName = document.getElementById("profileName");
    const profileEmail = document.getElementById("profileEmail");

    if (profileName) {
        profileName.textContent = fullName;
    }

    if (profileEmail) {
        profileEmail.textContent = email;
    }

    alert("Profile updated successfully.");
}

function updateProfileCartCount() {
    let cart = [];

    try {
        cart = JSON.parse(localStorage.getItem("pharmacareCart")) || [];
    } catch (error) {
        cart = [];
    }

    let count = 0;

    cart.forEach(function (item) {
        count += Number(item.quantity) || 0;
    });

    const cartCount = document.getElementById("cartCount");

    if (cartCount) {
        cartCount.textContent = count;
    }

    document.querySelectorAll(".cart-count").forEach(function (element) {
        element.textContent = count;
    });
}