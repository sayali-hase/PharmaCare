document.addEventListener("DOMContentLoaded", function () {
    updateCartCount();
});

function updateCartCount() {
    let cart = [];

    try {
        cart = JSON.parse(localStorage.getItem("pharmacareCart")) || [];
    } catch (error) {
        cart = [];
    }

    let totalItems = 0;

    cart.forEach(function (item) {
        totalItems += Number(item.quantity) || 0;
    });

    document.querySelectorAll("#cartCount").forEach(function (element) {
        element.textContent = totalItems;
    });

    document.querySelectorAll(".cart-count").forEach(function (element) {
        element.textContent = totalItems;
    });
}

function goToCart() {
    window.location.href = "pages/cart.html";
}

function goToProfile() {
    window.location.href = "pages/profile.html";
}

function goToTrackOrder() {
    window.location.href = "pages/track-order.html";
}