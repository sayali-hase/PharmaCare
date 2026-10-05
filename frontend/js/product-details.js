/* =========================================================
   PHARMACARE - PRODUCT DETAILS
   ========================================================= */


/* =========================
   PRODUCT DATA
   ========================= */

const products = [
    {
        id: "med001",
        name: "Paracetamol 500mg",
        category: "Pain Relief",
        price: 22.50,
        mrp: 25,
        discount: 10,
        rating: 4.5,
        reviews: 120,
        description: "Used for temporary relief from fever and mild pain.",
        visual: "tablet"
    },

    {
        id: "med002",
        name: "Vitamin C Tablets",
        category: "Vitamins",
        price: 102,
        mrp: 120,
        discount: 15,
        rating: 4.6,
        reviews: 98,
        description: "Vitamin supplement for daily nutritional support.",
        visual: "bottle"
    },

    {
        id: "med003",
        name: "Cetirizine 10mg",
        category: "Cold & Flu",
        price: 42.75,
        mrp: 45,
        discount: 5,
        rating: 4.4,
        reviews: 86,
        description: "Commonly used for allergy-related symptoms.",
        visual: "blue-tablet"
    },

    {
        id: "med004",
        name: "Antiseptic Cream",
        category: "Skin Care",
        price: 74.80,
        mrp: 85,
        discount: 12,
        rating: 4.3,
        reviews: 74,
        description: "Cream for basic skin protection and care.",
        visual: "cream"
    },

    {
        id: "med005",
        name: "Digestive Tablets",
        category: "Digestive",
        price: 59.80,
        mrp: 65,
        discount: 8,
        rating: 4.2,
        reviews: 61,
        description: "Digestive support tablets for everyday use.",
        visual: "orange-bottle"
    },

    {
        id: "med006",
        name: "Ibuprofen 400mg",
        category: "Pain Relief",
        price: 49.50,
        mrp: 55,
        discount: 10,
        rating: 4.4,
        reviews: 68,
        description: "Used for temporary relief from pain and inflammation.",
        visual: "tablet"
    },

    {
        id: "med007",
        name: "Cough Syrup",
        category: "Cold & Flu",
        price: 80.75,
        mrp: 95,
        discount: 15,
        rating: 4.3,
        reviews: 57,
        description: "Syrup for cough and throat discomfort.",
        visual: "syrup"
    },

    {
        id: "med008",
        name: "Multivitamin Tablets",
        category: "Vitamins",
        price: 144,
        mrp: 180,
        discount: 20,
        rating: 4.7,
        reviews: 103,
        description: "Daily multivitamin supplement for nutritional support.",
        visual: "bottle"
    }
];


/* =========================
   GET PRODUCT ID FROM URL
   ========================= */

const urlParams = new URLSearchParams(
    window.location.search
);

const productId = urlParams.get("id");

const product = products.find(function (item) {

    return item.id === productId;

});


/* =========================
   MEDICINE VISUALS
   ========================= */

function getVisualHTML(type) {

    switch (type) {

        /* ---------- TABLET ---------- */

        case "tablet":

            return `
                <div class="medicine-visual tablet-visual">

                    <div class="tablet-strip">

                        <span class="tablet-pill"></span>
                        <span class="tablet-pill"></span>
                        <span class="tablet-pill"></span>

                        <span class="tablet-pill"></span>
                        <span class="tablet-pill"></span>
                        <span class="tablet-pill"></span>

                    </div>

                </div>
            `;


        /* ---------- BLUE TABLET ---------- */

        case "blue-tablet":

            return `
                <div class="medicine-visual tablet-visual blue-tablets">

                    <div class="tablet-strip">

                        <span class="tablet-pill"></span>
                        <span class="tablet-pill"></span>
                        <span class="tablet-pill"></span>

                        <span class="tablet-pill"></span>
                        <span class="tablet-pill"></span>
                        <span class="tablet-pill"></span>

                    </div>

                </div>
            `;


        /* ---------- GREEN BOTTLE ---------- */

        case "bottle":

            return `
                <div class="medicine-visual bottle-visual">

                    <div class="bottle-cap"></div>

                    <div class="medicine-bottle">

                        <div class="bottle-label">
                            VIT C
                        </div>

                    </div>

                </div>
            `;


        /* ---------- ORANGE BOTTLE ---------- */

        case "orange-bottle":

            return `
                <div class="medicine-visual bottle-visual orange-bottle">

                    <div class="bottle-cap"></div>

                    <div class="medicine-bottle">

                        <div class="bottle-label">
                            DIGEST
                        </div>

                    </div>

                </div>
            `;


        /* ---------- CREAM ---------- */

        case "cream":

            return `
                <div class="medicine-visual cream-visual">

                    <div class="cream-tube">

                        <div class="cream-label">
                            CARE
                        </div>

                    </div>

                </div>
            `;


        /* ---------- SYRUP ---------- */

        case "syrup":

            return `
                <div class="medicine-visual syrup-visual">

                    <div class="syrup-cap"></div>

                    <div class="syrup-bottle">

                        <div class="syrup-label">
                            SYRUP
                        </div>

                    </div>

                </div>
            `;


        /* ---------- DEFAULT ---------- */

        default:

            return `
                <div class="medicine-visual tablet-visual">

                    <div class="tablet-strip">

                        <span class="tablet-pill"></span>
                        <span class="tablet-pill"></span>
                        <span class="tablet-pill"></span>

                        <span class="tablet-pill"></span>
                        <span class="tablet-pill"></span>
                        <span class="tablet-pill"></span>

                    </div>

                </div>
            `;
    }
}


/* =========================
   FORMAT PRICE
   ========================= */

function formatPrice(price) {

    return "₹" + Number(price).toFixed(2);

}


/* =========================
   STAR RATING
   ========================= */

function getStars(rating) {

    return "★ " + Number(rating).toFixed(1);

}


/* =========================
   UPDATE CART COUNT
   ========================= */

function updateCartCount() {

    const cartCountElement =
        document.getElementById("cartCount");

    if (!cartCountElement) {
        return;
    }

    let cart = [];

    try {

        cart = JSON.parse(
            localStorage.getItem("pharmacareCart")
        ) || [];

    } catch (error) {

        cart = [];

    }

    let totalQuantity = 0;

    cart.forEach(function (item) {

        totalQuantity += Number(item.quantity) || 0;

    });

    cartCountElement.textContent = totalQuantity;

}


/* =========================
   ADD TO CART
   ========================= */

function addToCart(item, quantity) {

    let cart = [];

    try {

        cart = JSON.parse(
            localStorage.getItem("pharmacareCart")
        ) || [];

    } catch (error) {

        cart = [];

    }


    const existingItem = cart.find(function (cartItem) {

        return cartItem.productId === item.id;

    });


    if (existingItem) {

        existingItem.quantity += quantity;

    } else {

        cart.push({

            productId: item.id,

            name: item.name,

            category: item.category,

            price: item.price,

            mrp: item.mrp,

            discount: item.discount,

            visual: item.visual,

            quantity: quantity

        });
    }


    localStorage.setItem(
        "pharmacareCart",
        JSON.stringify(cart)
    );


    updateCartCount();


    alert(
        item.name +
        " added to cart successfully!"
    );
}


/* =========================
   BUY NOW - DIRECT ORDER
   ========================= */

function buyNow(item, quantity) {

    /*
       Direct Order Item

       User does NOT need to add product
       to cart before ordering.
    */

    const buyNowItem = {

        productId: item.id,

        name: item.name,

        category: item.category,

        price: item.price,

        mrp: item.mrp,

        discount: item.discount,

        visual: item.visual,

        quantity: Number(quantity) || 1,

        directOrder: true

    };


    /*
       Store selected product separately
       from normal cart.
    */

    localStorage.setItem(
        "buyNowItem",
        JSON.stringify(buyNowItem)
    );


    /*
       Tell checkout page that this is
       a direct Buy Now order.
    */

    sessionStorage.setItem(
        "checkoutMode",
        "buyNow"
    );


    /*
       Open checkout directly.
    */

    window.location.href =
        "checkout.html?mode=buyNow";

}


/* =========================
   RENDER MAIN PRODUCT
   ========================= */

function renderProduct() {

    if (!product) {

        document.body.innerHTML = `
            <div style="
                min-height:100vh;
                display:flex;
                align-items:center;
                justify-content:center;
                font-family:Arial,sans-serif;
                text-align:center;
                padding:30px;
            ">

                <div>

                    <h2>Product Not Found</h2>

                    <p>
                        The requested medicine could not be found.
                    </p>

                    <a href="medicines.html">
                        Back to Medicines
                    </a>

                </div>

            </div>
        `;

        return;
    }


    /* ---------- Product Name ---------- */

    const productNameElements =
        document.querySelectorAll("#productName");

    productNameElements.forEach(function (element) {

        element.textContent = product.name;

    });


    /* ---------- Category ---------- */

    const categoryElements =
        document.querySelectorAll("#productCategory");

    categoryElements.forEach(function (element) {

        element.textContent = product.category;

    });


    /* ---------- Description ---------- */

    const descriptionElements =
        document.querySelectorAll("#productDescription");

    descriptionElements.forEach(function (element) {

        element.textContent = product.description;

    });


    /* ---------- Price ---------- */

    const priceElements =
        document.querySelectorAll("#productPrice");

    priceElements.forEach(function (element) {

        element.textContent =
            formatPrice(product.price);

    });


    /* ---------- MRP ---------- */

    const mrpElements =
        document.querySelectorAll("#productMrp");

    mrpElements.forEach(function (element) {

        element.textContent =
            formatPrice(product.mrp);

    });


    /* ---------- Discount ---------- */

    const discountElements =
        document.querySelectorAll("#productDiscount");

    discountElements.forEach(function (element) {

        element.textContent =
            product.discount + "% OFF";

    });


    /* ---------- Rating ---------- */

    const ratingElements =
        document.querySelectorAll("#productRating");

    ratingElements.forEach(function (element) {

        element.textContent =
            getStars(product.rating);

    });


    /* ---------- Reviews ---------- */

    const reviewElements =
        document.querySelectorAll("#productReviews");

    reviewElements.forEach(function (element) {

        element.textContent =
            product.reviews + " Reviews";

    });


    /* ---------- Product Visual ---------- */

    const visualElements =
        document.querySelectorAll("#productVisual");

    visualElements.forEach(function (element) {

        element.innerHTML =
            getVisualHTML(product.visual);

    });


    /* ---------- Breadcrumb ---------- */

    const breadcrumbElements =
        document.querySelectorAll("#breadcrumbProduct");

    breadcrumbElements.forEach(function (element) {

        element.textContent =
            product.name;

    });


    /* ---------- Page Title ---------- */

    document.title =
        product.name + " | PharmaCare";

}


/* =========================
   QUANTITY
   ========================= */

let quantity = 1;


function updateQuantityDisplay() {

    const quantityElements =
        document.querySelectorAll("#quantity");

    quantityElements.forEach(function (element) {

        element.value = quantity;

    });


    const quantityInput =
        document.getElementById("quantityInput");

    if (quantityInput) {

        quantityInput.value = quantity;

    }

}


function increaseQuantity() {

    quantity++;

    updateQuantityDisplay();

}


function decreaseQuantity() {

    if (quantity > 1) {

        quantity--;

    }

    updateQuantityDisplay();

}


/* =========================
   QUANTITY BUTTON EVENTS
   ========================= */

function setupQuantityButtons() {

    const plusButtons =
        document.querySelectorAll(
            "#increaseQty, #plusBtn, .increase-qty, .qty-plus"
        );


    plusButtons.forEach(function (button) {

        button.addEventListener(
            "click",
            increaseQuantity
        );

    });


    const minusButtons =
        document.querySelectorAll(
            "#decreaseQty, #minusBtn, .decrease-qty, .qty-minus"
        );


    minusButtons.forEach(function (button) {

        button.addEventListener(
            "click",
            decreaseQuantity
        );

    });

}


/* =========================
   ADD TO CART BUTTON
   ========================= */

function setupAddToCart() {

    const buttons =
        document.querySelectorAll(
            "#addToCartBtn, .add-to-cart-btn"
        );


    buttons.forEach(function (button) {

        button.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                if (!product) {
                    return;
                }

                addToCart(
                    product,
                    quantity
                );

            }
        );

    });

}


/* =========================
   BUY NOW BUTTON
   ========================= */

function setupBuyNow() {

    const buttons =
        document.querySelectorAll(
            "#buyNowBtn, .buy-now-btn"
        );


    buttons.forEach(function (button) {

        button.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                if (!product) {
                    return;
                }

                /*
                   Direct order.
                   No Add to Cart required.
                */

                buyNow(
                    product,
                    quantity
                );

            }
        );

    });

}


/* =========================
   RELATED PRODUCTS
   ========================= */

function getRelatedProducts() {

    if (!product) {
        return [];
    }


    const sameCategory =
        products.filter(function (item) {

            return (
                item.id !== product.id &&
                item.category === product.category
            );

        });


    const otherProducts =
        products.filter(function (item) {

            return (
                item.id !== product.id &&
                item.category !== product.category
            );

        });


    return [
        ...sameCategory,
        ...otherProducts
    ].slice(0, 4);

}


/* =========================
   RELATED PRODUCT CARD
   ========================= */

function createRelatedCard(item) {

    return `
        <article
            class="related-card"
            data-product-id="${item.id}"
        >

            <div class="medicine-image">

                ${getVisualHTML(item.visual)}

            </div>


            <div class="medicine-info">

                <div class="medicine-category">
                    ${item.category}
                </div>


                <h3>
                    ${item.name}
                </h3>


                <div class="related-price">

                    <span class="current-price">
                        ${formatPrice(item.price)}
                    </span>

                    <span class="mrp">
                        ${formatPrice(item.mrp)}
                    </span>

                    <span class="discount">
                        ${item.discount}% OFF
                    </span>

                </div>

            </div>

        </article>
    `;
}


/* =========================
   RENDER RELATED PRODUCTS
   ========================= */

function renderRelatedProducts() {

    const relatedProducts =
        getRelatedProducts();


    let container =
        document.getElementById(
            "relatedProducts"
        );


    if (!container) {

        container =
            document.getElementById(
                "relatedGrid"
            );

    }


    if (!container) {

        container =
            document.querySelector(
                ".related-grid"
            );

    }


    if (!container) {
        return;
    }


    container.innerHTML = "";


    relatedProducts.forEach(function (item) {

        container.insertAdjacentHTML(
            "beforeend",
            createRelatedCard(item)
        );

    });


    setupRelatedProductClicks();

}


/* =========================
   RELATED PRODUCT CLICK
   ========================= */

function setupRelatedProductClicks() {

    const cards =
        document.querySelectorAll(
            ".related-card, .more-medicine-card"
        );


    cards.forEach(function (card) {

        card.addEventListener(
            "click",
            function () {

                const id =
                    card.getAttribute(
                        "data-product-id"
                    );


                if (!id) {
                    return;
                }


                window.location.href =
                    "product-details.html?id=" +
                    encodeURIComponent(id);

            }
        );

    });

}


/* =========================
   CART COUNT
   ========================= */

function setupCartCount() {

    updateCartCount();

}


/* =========================
   HEADER CART COUNT
   ========================= */

window.addEventListener(
    "storage",
    function (event) {

        if (
            event.key ===
            "pharmacareCart"
        ) {

            updateCartCount();

        }

    }
);


/* =========================
   PAGE INITIALIZATION
   ========================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        renderProduct();

        renderRelatedProducts();

        setupQuantityButtons();

        setupAddToCart();

        setupBuyNow();

        setupCartCount();

        updateQuantityDisplay();

    }
);