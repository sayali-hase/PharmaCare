document.addEventListener("DOMContentLoaded", function () {

    console.log("home.js connected");

    // =========================
    // SHOP NOW BUTTON
    // =========================

    const shopBtn = document.querySelector(".shop-btn");

    if (shopBtn) {
        shopBtn.addEventListener("click", function () {
            window.location.href = "pages/medicines.html";
        });
    }


    // =========================
    // HOME SEARCH
    // =========================

    const searchInput =
        document.getElementById("homeSearch") ||
        document.getElementById("searchInput");

    const searchButton =
        document.getElementById("searchButton");

    if (searchButton && searchInput) {

        searchButton.addEventListener("click", function () {

            const value = searchInput.value.trim();

            if (value !== "") {
                window.location.href =
                    "pages/medicines.html?search=" +
                    encodeURIComponent(value);
            } else {
                alert("Please enter a medicine name.");
            }

        });

    }


    // =========================
    // SEARCH WITH ENTER KEY
    // =========================

    if (searchInput) {

        searchInput.addEventListener("keypress", function (event) {

            if (event.key === "Enter") {

                event.preventDefault();

                const value = searchInput.value.trim();

                if (value !== "") {
                    window.location.href =
                        "pages/medicines.html?search=" +
                        encodeURIComponent(value);
                } else {
                    alert("Please enter a medicine name.");
                }

            }

        });

    }


    // =========================
    // CATEGORY CARDS
    // =========================

    const categoryCards =
        document.querySelectorAll(".category-card");

    categoryCards.forEach(function (card) {

        card.addEventListener("click", function () {

            const category =
                card.getAttribute("data-category");

            if (category) {

                window.location.href =
                    "pages/medicines.html?category=" +
                    encodeURIComponent(category);

            } else {

                window.location.href =
                    "pages/medicines.html";

            }

        });

    });


    // =========================
    // MEDICINE CARDS
    // =========================

    const medicineCards =
        document.querySelectorAll(".medicine-card");

    medicineCards.forEach(function (card) {

        card.addEventListener("click", function () {

            const id =
                card.getAttribute("data-id");

            const name =
                card.getAttribute("data-name");

            if (id) {

                window.location.href =
                    "pages/product-details.html?id=" +
                    encodeURIComponent(id);

            } else if (name) {

                window.location.href =
                    "pages/product-details.html?name=" +
                    encodeURIComponent(name);

            }

        });

    });


    // =========================
    // IMPORTANT:
    // DO NOT BLOCK NAVBAR LINKS
    // =========================

    // No preventDefault() on navbar links.
    // Medicines link will work normally through href.


});