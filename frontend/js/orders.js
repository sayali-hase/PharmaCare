document.addEventListener("DOMContentLoaded", () => {
    loadOrders();
    updateOrdersCartCount();
});


/* =========================
   GET ORDERS
========================= */

function getOrders() {
    const orders = localStorage.getItem("pharmaOrders");

    if (!orders) {
        return [];
    }

    try {
        return JSON.parse(orders);
    } catch (error) {
        console.error("Error reading orders:", error);
        return [];
    }
}


/* =========================
   LOAD ORDERS
========================= */

function loadOrders() {
    const ordersList = document.getElementById("ordersList");
    const emptyOrders = document.getElementById("emptyOrders");

    if (!ordersList || !emptyOrders) {
        return;
    }

    const orders = getOrders();

    if (orders.length === 0) {
        ordersList.innerHTML = "";
        emptyOrders.style.display = "block";
        return;
    }

    emptyOrders.style.display = "none";

    // Latest order first
    const latestOrders = [...orders].reverse();

    ordersList.innerHTML = latestOrders
        .map(order => createOrderCard(order))
        .join("");
}


/* =========================
   CREATE ORDER CARD
========================= */

function createOrderCard(order) {

    const orderId = order.orderId || "N/A";
    const status = order.status || "Order Placed";

    const customerName = order.customerName || "Customer";
    const payment = order.payment || "Cash on Delivery";
    const city = order.city || "";

    const total = Number(order.total) || 0;

    let orderDate = order.date || "";

    if (orderDate) {
        try {
            orderDate = new Date(orderDate).toLocaleString();
        } catch (error) {
            console.error(error);
        }
    }

    const items = Array.isArray(order.items)
        ? order.items
        : [];

    const itemsHTML = items.length > 0
        ? items.map(item => {

            const quantity = Number(item.quantity) || 1;
            const price = Number(item.price) || 0;

            return `
                <div class="order-item">

                    <div class="order-item-info">
                        <strong>
                            ${escapeHTML(item.name || "Medicine")}
                        </strong>

                        <span>
                            Qty: ${quantity}
                        </span>
                    </div>

                    <div class="order-item-price">
                        ₹${(price * quantity).toFixed(2)}
                    </div>

                </div>
            `;

        }).join("")
        : `
            <div class="order-item">
                <span>No item information available.</span>
            </div>
        `;


    return `
        <div class="order-card">

            <!-- ORDER TOP -->
            <div class="order-top">

                <div>
                    <h3>Order #${escapeHTML(orderId)}</h3>

                    <p class="order-date">
                        ${escapeHTML(orderDate)}
                    </p>
                </div>

                <span class="order-status ${getStatusClass(status)}">
                    ${escapeHTML(status)}
                </span>

            </div>


            <!-- CUSTOMER DETAILS -->
            <div class="order-details">

                <div>
                    <span class="detail-label">Customer</span>
                    <strong>
                        ${escapeHTML(customerName)}
                    </strong>
                </div>

                <div>
                    <span class="detail-label">Payment</span>
                    <strong>
                        ${escapeHTML(payment)}
                    </strong>
                </div>

                <div>
                    <span class="detail-label">City</span>
                    <strong>
                        ${escapeHTML(city)}
                    </strong>
                </div>

            </div>


            <!-- ITEMS -->
            <div class="order-items">

                <h4>Order Items</h4>

                ${itemsHTML}

            </div>


            <!-- PROGRESS -->
            <div class="order-progress">

                <h4>Order Status</h4>

                <div class="progress-container">

                    ${createProgressSteps(status)}

                </div>

            </div>


            <!-- FOOTER -->
            <div class="order-footer">

                <div class="order-total">
                    Total:
                    <strong>
                        ₹${total.toFixed(2)}
                    </strong>
                </div>

                <div class="order-actions">

                    <button
                        type="button"
                        class="track-order-btn"
                        onclick="trackSpecificOrder('${escapeOrderId(orderId)}')"
                    >
                        Track Order
                    </button>

                    ${
                        canCancelOrder(status)
                            ? `
                                <button
                                    type="button"
                                    class="cancel-order-btn"
                                    onclick="cancelOrder('${escapeOrderId(orderId)}')"
                                >
                                    Cancel Order
                                </button>
                            `
                            : ""
                    }

                </div>

            </div>

        </div>
    `;
}


/* =========================
   STATUS CLASS
========================= */

function getStatusClass(status) {

    switch (status) {

        case "Delivered":
            return "status-delivered";

        case "Cancelled":
            return "status-cancelled";

        case "Out for Delivery":
            return "status-out";

        case "Shipped":
        case "Dispatched":
            return "status-shipped";

        case "Packed":
            return "status-packed";

        case "Confirmed":
            return "status-confirmed";

        default:
            return "status-placed";
    }
}


/* =========================
   PROGRESS STEPS
========================= */

function createProgressSteps(currentStatus) {

    /*
       Cancelled order
    */

    if (currentStatus === "Cancelled") {

        return `
            <div class="progress-step active cancelled-step">

                <div class="progress-dot">
                    ×
                </div>

                <span>
                    Cancelled
                </span>

            </div>
        `;
    }


    const statusMap = {

        "Placed": "Order Placed",

        "Order Placed": "Order Placed",

        "Confirmed": "Confirmed",

        "Packed": "Packed",

        "Dispatched": "Shipped",

        "Shipped": "Shipped",

        "Out for Delivery": "Out for Delivery",

        "Delivered": "Delivered"

    };


    const statuses = [

        "Order Placed",

        "Confirmed",

        "Packed",

        "Shipped",

        "Out for Delivery",

        "Delivered"

    ];


    const normalizedStatus =
        statusMap[currentStatus] || "Order Placed";


    const currentIndex =
        statuses.indexOf(normalizedStatus);


    return statuses.map((status, index) => {

        let stepClass = "";

        if (index < currentIndex) {
            stepClass = "completed";
        }

        if (index === currentIndex) {
            stepClass = "active";
        }


        return `
            <div class="progress-step ${stepClass}">

                <div class="progress-dot">
                    ${index < currentIndex ? "✓" : index + 1}
                </div>

                <span>
                    ${status}
                </span>

            </div>
        `;

    }).join("");
}


/* =========================
   CAN CANCEL ORDER?
========================= */

function canCancelOrder(status) {

    return (
        status === "Placed" ||
        status === "Order Placed" ||
        status === "Confirmed"
    );
}


/* =========================
   CANCEL ORDER
========================= */

function cancelOrder(orderId) {

    if (!orderId) {
        return;
    }


    const confirmCancel = confirm(
        "Are you sure you want to cancel this order?"
    );


    if (!confirmCancel) {
        return;
    }


    const orders = getOrders();


    const orderIndex = orders.findIndex(
        order =>
            String(order.orderId) === String(orderId)
    );


    if (orderIndex === -1) {

        alert("Order not found.");

        return;
    }


    const currentStatus =
        orders[orderIndex].status || "Order Placed";


    /*
       Double check whether
       cancellation is allowed.
    */

    if (!canCancelOrder(currentStatus)) {

        alert(
            "This order cannot be cancelled at this stage."
        );

        return;
    }


    /*
       Update order status
    */

    orders[orderIndex].status = "Cancelled";


    /*
       Save updated orders
    */

    localStorage.setItem(
        "pharmaOrders",
        JSON.stringify(orders)
    );


    /*
       Reload orders
    */

    loadOrders();


    alert(
        "Your order has been cancelled successfully."
    );
}


/* =========================
   TRACK ORDER
========================= */

function trackSpecificOrder(orderId) {

    if (!orderId) {
        return;
    }

    localStorage.setItem(
        "trackOrderId",
        orderId
    );

    window.location.href =
        `track-order.html?orderId=${encodeURIComponent(orderId)}`;
}


/* =========================
   SEARCH
========================= */

function searchFromOrders() {

    const searchInput =
        document.getElementById("ordersSearch");

    if (!searchInput) {
        return;
    }


    const searchTerm =
        searchInput.value.trim();


    if (!searchTerm) {
        return;
    }


    window.location.href =
        `medicines.html?search=${encodeURIComponent(searchTerm)}`;
}


/* =========================
   CART COUNT
========================= */

function updateOrdersCartCount() {

    const cartCount =
        document.getElementById("cartCount");

    if (!cartCount) {
        return;
    }


    const cart =
        localStorage.getItem("pharmacareCart");


    if (!cart) {

        cartCount.textContent = "0";

        return;
    }


    try {

        const cartItems =
            JSON.parse(cart);


        if (!Array.isArray(cartItems)) {

            cartCount.textContent = "0";

            return;
        }


        const totalQuantity =
            cartItems.reduce(
                (total, item) =>
                    total +
                    (Number(item.quantity) || 1),
                0
            );


        cartCount.textContent =
            totalQuantity;

    } catch (error) {

        console.error(
            "Cart count error:",
            error
        );

        cartCount.textContent = "0";
    }
}


/* =========================
   ESCAPE HTML
========================= */

function escapeHTML(value) {

    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}


/* =========================
   ESCAPE ORDER ID
========================= */

function escapeOrderId(value) {

    return String(value)
        .replace(/\\/g, "\\\\")
        .replace(/'/g, "\\'");
}


/* =========================
   SEARCH ENTER KEY
========================= */

document.addEventListener(
    "keydown",
    function (event) {

        if (
            event.key === "Enter" &&
            document.activeElement &&
            document.activeElement.id === "ordersSearch"
        ) {

            searchFromOrders();
        }

    }
);