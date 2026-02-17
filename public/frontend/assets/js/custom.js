/* for search service  */
const exactMatch = (arr, key, q) => {
    return arr.find((o) => {
        const value = o[key];
        return value && value.toLowerCase().trim() === q;
    });
};
const startsWith = (arr, key, q) =>
    arr.filter((o) => {
        const value = o[key];
        return value && value.toLowerCase().startsWith(q);
    });
/* for search service  */

/* top progress bar  */
const progressBar = document.getElementById("page-progress");
window.addEventListener("beforeunload", () => {
    progressBar.style.width = "40%";

    setTimeout(() => {
        progressBar.style.width = "70%";
    }, 150);

    setTimeout(() => {
        progressBar.style.width = "100%";
    }, 300);
});

window.addEventListener("load", () => {
    progressBar.style.width = "0";
});
/* top progress bar  */

$(document).ready(function () {
    /* travel stories slider */
    $(".ts-main-slider").slick({
        dots: false,
        infinite: false,
        speed: 600,
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: true,
        autoplay: false,
        autoplaySpeed: 2000,
        fade: true,
        cssEase: "linear",
    });
    /* travel stories slider */

    /* activity slider  */
    $(".activity-slider").slick({
        dots: false,
        infinite: false,
        speed: 300,
        slidesToShow: 4,
        slidesToScroll: 1,
        prevArrow: $(".activity-prev-slide"),
        nextArrow: $(".activity-next-slide"),
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 3,
                },
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 2,
                },
            },
            {
                breakpoint: 480,
                settings: {
                    slidesToShow: 1,
                },
            },
        ],
    });
    /* activity slider  */

    /* activity slider  */
    $(".reviews-slider").slick({
        dots: false,
        arrows: true,
        infinite: false,
        speed: 300,
        slidesToShow: 2,
        slidesToScroll: 2,
        autoplay: true,
        autoplaySpeed: 2000,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 2,
                },
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 2,
                },
            },
            {
                breakpoint: 480,
                settings: {
                    arrows: false,
                    slidesToShow: 1,
                },
            },
        ],
    });
    /* activity slider  */

    /* hotels slider  */
    $(".hotels-slider").slick({
        dots: false,
        arrows: true,
        infinite: false,
        autoplay: true,
        autoplaySpeed: 2000,
        speed: 300,
        slidesToShow: 3,
        slidesToScroll: 1,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 3,
                },
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 2,
                },
            },
            {
                breakpoint: 480,
                settings: {
                    slidesToShow: 1,
                },
            },
        ],
    });
    /* hotels slider  */

    /* hotels slider  */
    $(".flights-slider").slick({
        dots: false,
        arrows: true,
        infinite: false,
        autoplay: true,
        autoplaySpeed: 2000,
        speed: 300,
        slidesToShow: 4,
        slidesToScroll: 1,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 3,
                },
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 2,
                },
            },
            {
                breakpoint: 480,
                settings: {
                    slidesToShow: 1,
                },
            },
        ],
    });
    /* hotels slider  */

    /* category-slider  */
    $(".category-slider").slick({
        dots: false,
        infinite: false,
        speed: 300,
        slidesToShow: 5,
        slidesToScroll: 1,
        prevArrow: $(".category-prev-slide"),
        nextArrow: $(".category-next-slide"),
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 4,
                },
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 3,
                },
            },
            {
                breakpoint: 480,
                settings: {
                    slidesToShow: 1,
                },
            },
        ],
    });
    /* category-slider  */

    /* category-slider  */
    $(".category-slider2").slick({
        dots: false,
        arrows: true,
        infinite: false,
        speed: 300,
        slidesToShow: 6,
        slidesToScroll: 1,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 4,
                },
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 3,
                    arrows: false,
                },
            },
            {
                breakpoint: 480,
                settings: {
                    slidesToShow: 2,
                    arrows: false,
                },
            },
        ],
    });
    /* category-slider  */

    /* banner slider */
    $(".banner-slider").slick({
        dots: false,
        infinite: true,
        centerMode: true,
        centerPadding: "60px",
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: false,
        autoplay: true,
        autoplaySpeed: 2500,
        responsive: [
            {
                breakpoint: 768,
                settings: {
                    centerMode: false,
                    centerPadding: "0px",
                    autoplaySpeed: 3500,
                },
            },
        ],
    });
    /* banner slider */
});

/* sideBar */

// Close sidebar when clicking outside
document.addEventListener("click", function (e) {
    const sidebar = document.getElementById("sideBar");
    const isClickInside = sidebar.contains(e.target);
    const isToggleButton = e.target.closest("[data-menu-button]"); // example trigger class

    // If sidebar is open, you clicked outside, and it's not the toggle button
    if (
        sidebar.classList.contains("show") &&
        !isClickInside &&
        !isToggleButton
    ) {
        closeSideBar();
    }
});

function openSideBar() {
    document.getElementById("sideBar").classList.add("show");
}
function closeSideBar() {
    document.getElementById("sideBar").classList.remove("show");
}
/* sideBar */

const showMessage = (message, type, position = "bottom-right") => {
    $.toast({
        heading: type === "success" ? "Success!" : "Error!",
        position: position,
        text: message,
        loaderBg: type === "success" ? "#ff6849" : "#ff6849",
        icon: type === "success" ? "success" : "error",
        hideAfter: 3000,
        stack: 6,
    });
};

document.addEventListener("DOMContentLoaded", function () {
    const imgs = document.querySelectorAll("img");
    imgs?.forEach(function (imgElement) {
        imgElement.onerror = function () {
            imgElement.src =
                "{{ asset('frontend/assets/images/placeholder.png') }}";
        };
    });
});

/* Faqs Toggler */
document.addEventListener("DOMContentLoaded", function () {
    const faqItems = document.querySelectorAll(".faq-item");

    faqItems?.forEach((item) => {
        const header = item.querySelector(".faq-header");
        const body = item.querySelector(".faq-body");

        if (item.classList.contains("active")) {
            body.style.maxHeight = body.scrollHeight + "px";
        }

        header.addEventListener("click", () => {
            const isOpen = item.classList.contains("active");

            if (!isOpen) {
                item.classList.add("active");
                body.style.maxHeight = body.scrollHeight + "px";
            } else {
                item.classList.remove("active");
                body.style.maxHeight = null;
            }
        });
    });
});
/* Faqs Toggler */

/* Expandable Card */
document.addEventListener("DOMContentLoaded", function () {
    const wrappers = document.querySelectorAll(".expandable-wrapper");

    wrappers?.forEach((wrapper) => {
        const contentDiv = wrapper.querySelector(".expandable-content");
        const innerContent = wrapper.querySelector(".expandable-content-inner");
        const btn = wrapper.querySelector(".expand-btn");

        const collapsedHeight =
            parseInt(wrapper.getAttribute("data-collapsed-height")) || 100;
        const moreText = wrapper.getAttribute("data-more-text") || "Read More";
        const lessText = wrapper.getAttribute("data-less-text") || "Read Less";

        let isExpanded = false;

        contentDiv.style.maxHeight = collapsedHeight + "px";
        btn.textContent = moreText;

        if (innerContent.scrollHeight <= collapsedHeight) {
            btn.style.display = "none";
            contentDiv.style.maxHeight = "none";
        }

        btn.addEventListener("click", function () {
            if (!isExpanded) {
                // EXPAND
                contentDiv.style.maxHeight = innerContent.scrollHeight + "px";
                btn.textContent = lessText;
                isExpanded = true;
            } else {
                // COLLAPSE
                contentDiv.style.maxHeight = collapsedHeight + "px";
                btn.textContent = moreText;
                isExpanded = false;
            }
        });

        window.addEventListener("resize", function () {
            if (isExpanded) {
                contentDiv.style.maxHeight = innerContent.scrollHeight + "px";
            }
        });
    });
});

/* Expandable Card */

/* Global popup */
const popupWrapper = document.querySelector("[data-popup-wrapper]");
const popupClose = document.querySelector("[data-popup-close]");
const popupTriggers = document.querySelectorAll("[data-popup-trigger]");

popupTriggers?.forEach((el) => {
    el.addEventListener("click", (e) => {
        const title = el.dataset.popupTitle;
        const popupId = el.dataset.popupId;
        const htmlContent = document.getElementById(popupId)?.innerHTML || "";
        
        popupWrapper.querySelector("[data-popup-title]").innerHTML = title;
        popupWrapper.querySelector("[data-popup-text]").innerHTML = htmlContent;
        popupWrapper.classList.add("open");
    });
});

function closePopup() {
    popupWrapper.classList.remove("open");
    // reset all triggers to original text
    popupTriggers.forEach((el, idx) => {
        el.innerHTML = 'More Benefits <i class="bx bx-chevron-right"></i>';
    });
}

popupClose?.addEventListener("click", closePopup);

popupWrapper?.addEventListener("click", function (e) {
    if (e.target === popupWrapper) {
        closePopup();
    }
});
/* Global popup */

/* Custom Accordian */
document.querySelectorAll("[custom-accordion]")?.forEach((accordion) => {
    accordion
        .querySelector("[custom-accordion-header]")
        ?.addEventListener("click", () => {
            accordion.classList.toggle("open");
        });
});
/* Custom Accordian */

/* hotel details slider */
$(document).ready(function () {
    $(".hotels-lg-img-wrapper").each(function () {
        const $wrapper = $(this);
        const $slider = $wrapper.find(".hotels-lg-img-list");
        const $navSlider = $(".hotels-sm-img-list-slider");
        const totalSlides = $slider.find(".hotels-lg-img-item").length;

        $wrapper
            .find(".event-slider-actions__progress")
            .text(`1/${totalSlides}`);

        $slider.slick({
            arrows: true,
            infinite: false,
            fade: true,
            asNavFor: ".hotels-sm-img-list-slider",
            speed: 300,
            slidesToShow: 1,
            slidesToScroll: 1,
            prevArrow: $wrapper.find(".event-slider-prev"),
            nextArrow: $wrapper.find(".event-slider-next"),
        });

        $navSlider.slick({
            slidesToShow: 5,
            slidesToScroll: 1,
            asNavFor: ".hotels-lg-img-list",
            arrows: false,
            infinite: false,
            focusOnSelect: true,
        });

        $slider.on("afterChange", function (event, slick, currentSlide) {
            updateProgress(currentSlide);
        });

        $navSlider.on("afterChange", function (event, slick, currentSlide) {
            updateProgress(currentSlide);
        });

        function updateProgress(currentSlide) {
            const currentSlideNumber = currentSlide + 1;
            $wrapper
                .find(".event-slider-actions__progress")
                .text(`${currentSlideNumber}/${totalSlides}`);
        }

        // Fullscreen toggle
        $(".full-screen").click(function () {
            if (!document.fullscreenElement) {
                // Enter fullscreen
                $wrapper[0]
                    .requestFullscreen()
                    .then(() => {
                        $(this).html(
                            "<i class='bx bx-exit-fullscreen'></i> Close",
                        );
                        $wrapper.addClass("full-screen-enabled");
                    })
                    .catch((err) => {
                        console.log(
                            `Error attempting to enable full-screen mode: ${err.message}`,
                        );
                    });
            } else {
                // Exit fullscreen
                document
                    .exitFullscreen()
                    .then(() => {
                        $(this).html(
                            "<i class='bx bx-fullscreen'></i> Full screen",
                        );
                        $wrapper.removeClass("full-screen-enabled");
                    })
                    .catch((err) => {
                        console.log(
                            `Error attempting to exit full-screen mode: ${err.message}`,
                        );
                    });
            }
        });

        // Listen for fullscreen change events to handle icon and text updates
        document.addEventListener("fullscreenchange", () => {
            if (!document.fullscreenElement) {
                $(".full-screen").html(
                    "<i class='bx bx-fullscreen'></i> Full screen",
                );
            }
        });

        document.addEventListener("fullscreenchange", () => {
            const $wrapper = $(".hotels-lg-img-wrapper.full-screen-enabled");

            if (!document.fullscreenElement) {
                // Remove class when exiting fullscreen via ESC
                $wrapper.removeClass("full-screen-enabled");

                // Update button icon/text
                $(".full-screen").html(
                    "<i class='bx bx-fullscreen'></i> Full screen",
                );
            }
        });
    });
});
/* hotel details slider */