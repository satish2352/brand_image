/*
 * Site behaviour that is not tied to one page.
 *
 * This file used to initialise the original theme's whole plugin set — owl
 * carousel (three of them), isotope filtering, magnific popup, a countdown
 * timer — against elements that no longer exist anywhere in the site: nothing
 * renders .homepage-slider, .product-lists, .popup-youtube or .time-countdown
 * any more. The sliders are Swiper now and the hero is a Bootstrap 5 carousel.
 *
 * Those calls were keeping ~200KB of JavaScript alive for no reason, so the
 * plugins were dropped from the footer and the dead initialisers with them.
 * Calling a removed plugin throws rather than quietly doing nothing on an empty
 * selection, which would have stopped this file before the mobile menu below.
 */
(function ($) {
    "use strict";

    $(document).ready(function ($) {

        // sticky header
        $("#sticker").sticky({
            topSpacing: 0
        });

        // mobile menu
        $('.main-menu').meanmenu({
            meanMenuContainer: '.mobile-menu',
            meanScreenWidth: "992",
            // The plugin's default hamburger is "<span /><span /><span />".
            // jQuery 1.11 — which this file used to run under, because the
            // footer loaded a second copy of jQuery over the head's — expands
            // those self-closing tags into three siblings. jQuery 3 nests them
            // instead, leaving one visible bar instead of three. Spelling the
            // spans out keeps both parsers honest.
            meanMenuOpen: '<span></span><span></span><span></span>'
        });

        // search form
        $(".search-bar-icon").on("click", function () {
            $(".search-area").addClass("search-active");
        });

        $(".close-btn").on("click", function () {
            $(".search-area").removeClass("search-active");
        });

    });


    $(window).on("load", function () {
        $(".loader").fadeOut(1000);
    });

}(jQuery));
