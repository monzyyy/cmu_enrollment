document.addEventListener("DOMContentLoaded", function () {

    const menuButton = document.getElementById("menuButton");
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");


    /* =====================================================
       CHECK ELEMENTS
    ===================================================== */

    if (!menuButton || !sidebar || !overlay) {
        return;
    }


    /* =====================================================
       OPEN SIDEBAR
    ===================================================== */

    menuButton.addEventListener("click", function () {

        sidebar.classList.add("open");
        overlay.classList.add("show");

    });


    /* =====================================================
       CLOSE SIDEBAR - OVERLAY
    ===================================================== */

    overlay.addEventListener("click", function () {

        sidebar.classList.remove("open");
        overlay.classList.remove("show");

    });


    /* =====================================================
       CLOSE SIDEBAR - MENU LINK
    ===================================================== */

    const menuLinks = sidebar.querySelectorAll(".menu-link");

    menuLinks.forEach(function (link) {

        link.addEventListener("click", function () {

            sidebar.classList.remove("open");
            overlay.classList.remove("show");

        });

    });


    /* =====================================================
       CLOSE SIDEBAR - ESCAPE
    ===================================================== */

    document.addEventListener("keydown", function (event) {

        if (event.key === "Escape") {

            sidebar.classList.remove("open");
            overlay.classList.remove("show");

        }

    });

});