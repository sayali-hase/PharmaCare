let currentOrder = null;

document.addEventListener("DOMContentLoaded", function () {
    setupTrackSearch();
    loadOrderFromURL();
    updateTrackCartCount();
});


/* =========================================================
   GET ORDERS
========================================================= */

function getOrders() {
    try {
        return JSON.parse(localStorage.getItem("pharmaOrders")) || [];
    } catch (error) {
        return [];
    }
}


/* =========================================================
   TRACK ORDER
========================================================= */

function trackOrder() {

    const input = document.getElementById("orderId");
    const result = document.getElementById("trackResult");
    const info = document.getElementById("trackInfo");

    if (!input || !result || !info) {
        return;
    }

    const orderId = input.value.trim();

    if (orderId === "") {
        result.textContent = "Please enter your Order ID.";
        result.style.color = "#d93025";
        info.style.display = "none";
        return;
    }

    const orders = getOrders();

    const order = orders.find(function (item) {
        return String(item.orderId).toLowerCase() === orderId.toLowerCase();
    });

    if (!order) {
        result.textContent = "Order not found. Please check your Order ID.";
        result.style.color = "#d93025";
        info.style.display = "none";
        return;
    }

    currentOrder = order;

    result.textContent =
        "Order " + order.orderId + " found successfully.";

    result.style.color = "#078b48";

    displayOrderDetails(order);
}


/* =========================================================
   DISPLAY ORDER DETAILS
========================================================= */

function displayOrderDetails(order) {

    const info = document.getElementById("trackInfo");

    if (!info) {
        return;
    }

    const status = order.status || "Placed";

    const statuses = [
        "Placed",
        "Confirmed",
        "Packed",
        "Dispatched",
        "Delivered"
    ];

    const currentIndex = statuses.indexOf(status);

    let progressHTML = "";

    statuses.forEach(function (item, index) {

        let statusClass = "";

        if (index < currentIndex) {
            statusClass = "completed";
        } else if (index === currentIndex) {
            statusClass = "active";
        }

        progressHTML += `
            <div class="track-step ${statusClass}">
                <div class="step-circle">${index + 1}</div>
                <span>${item}</span>
            </div>
        `;
    });


    /* =====================================================
       ORDER ITEMS
    ===================================================== */

    let itemsHTML = "";

    if (
        Array.isArray(order.items) &&
        order.items.length > 0
    ) {

        order.items.forEach(function (item) {

            const quantity = Number(item.quantity) || 1;
            const price = Number(item.price) || 0;
            const itemTotal = price * quantity;

            itemsHTML += `
                <div class="track-item">

                    <div class="track-item-icon">
                        ${item.icon || "💊"}
                    </div>

                    <div class="track-item-info">
                        <strong>
                            ${item.name || "Medicine"}
                        </strong>

                        <small>
                            Quantity: ${quantity}
                        </small>
                    </div>

                    <div class="track-item-price">
                        ₹${itemTotal}
                    </div>

                </div>
            `;
        });

    } else {

        itemsHTML = `
            <p style="font-size:12px;color:#777;">
                No medicine details available.
            </p>
        `;
    }


    /* =====================================================
       COMPLETE TRACK INFORMATION
    ===================================================== */

    info.innerHTML = `

        <div class="track-card">

            <!-- Header -->

            <div class="track-card-header">

                <div>

                    <h2>
                        Order #${order.orderId}
                    </h2>

                    <p>
                        Order placed on
                        ${order.date || "N/A"}
                    </p>

                </div>

                <span class="order-status">
                    ${status}
                </span>

            </div>


            <!-- Progress -->

            <div class="track-progress">

                ${progressHTML}

            </div>


            <!-- Order Information -->

            <div class="track-info-grid">

                <div class="track-info-box">

                    <span>Customer</span>

                    <strong>
                        ${order.customerName || "N/A"}
                    </strong>

                </div>


                <div class="track-info-box">

                    <span>Mobile</span>

                    <strong>
                        ${order.mobile || "N/A"}
                    </strong>

                </div>


                <div class="track-info-box">

                    <span>Payment</span>

                    <strong>
                        ${order.payment || "N/A"}
                    </strong>

                </div>


                <div class="track-info-box">

                    <span>Total Amount</span>

                    <strong>
                        ₹${Number(order.total || 0)}
                    </strong>

                </div>

            </div>


            <!-- Delivery Address -->

            <div class="track-address">

                <h3>
                    Delivery Address
                </h3>

                <p>
                    ${order.address || "N/A"}<br>
                    ${order.city || ""}
                    ${order.pincode || ""}
                </p>

            </div>


            <!-- Ordered Medicines -->

            <div class="track-items">

                <h3>
                    Ordered Medicines
                </h3>

                ${itemsHTML}

            </div>


            <!-- Total -->

            <div class="track-total">

                <span>
                    Order Total
                </span>

                <strong>
                    ₹${Number(order.total || 0)}
                </strong>

            </div>

        </div>
    `;

    info.style.display = "block";
}


/* =========================================================
   LOAD ORDER FROM URL
========================================================= */

function loadOrderFromURL() {

    const params =
        new URLSearchParams(window.location.search);

    const orderId = params.get("orderId");

    if (!orderId) {
        return;
    }

    const input =
        document.getElementById("orderId");

    if (input) {

        input.value = orderId;

        trackOrder();
    }
}


/* =========================================================
   SEARCH SETUP
========================================================= */

function setupTrackSearch() {

    const input =
        document.getElementById("trackSearch");

    if (!input) {
        return;
    }

    input.addEventListener(
        "keydown",
        function (event) {

            if (event.key === "Enter") {

                searchFromTrack();
            }
        }
    );
}


/* =========================================================
   SEARCH MEDICINES FROM HEADER
========================================================= */

function searchFromTrack() {

    const input =
        document.getElementById("trackSearch");

    if (!input) {
        return;
    }

    const value = input.value.trim();

    if (value === "") {

        alert("Please enter a medicine name.");

        return;
    }

    localStorage.setItem(
        "searchMedicine",
        value
    );

    window.location.href =
        "medicines.html?search=" +
        encodeURIComponent(value);
}


/* =========================================================
   UPDATE CART COUNT
========================================================= */

function updateTrackCartCount() {

    let cart = [];

    try {

        /*
         * Correct PharmaCare cart key
         */
        cart =
            JSON.parse(
                localStorage.getItem("pharmacareCart")
            ) || [];

    } catch (error) {

        cart = [];
    }

    let count = 0;

    cart.forEach(function (item) {

        count +=
            Number(item.quantity) || 0;

    });


    document
        .querySelectorAll("#cartCount")
        .forEach(function (element) {

            element.textContent = count;

        });


    document
        .querySelectorAll(".cart-count")
        .forEach(function (element) {

            element.textContent = count;

        });
}
