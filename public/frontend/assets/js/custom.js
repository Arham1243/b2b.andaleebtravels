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

/* sideBar */

// Close sidebar when clicking outside
document.addEventListener("click", function (e) {
    const sidebar = document.getElementById("sideBar");
    const isClickInside = sidebar.contains(e.target);
    const isToggleButton = e.target.closest("[data-menu-button]");

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

$(".perks-slider").slick({
    dots: false,
    arrows: false,
    infinite: false,
    slidesToShow: 4,
    slidesToScroll: 1,
    autoplay: true,
    autoplaySpeed: 6000,
    responsive: [
        {
            breakpoint: 1024,
            settings: {
                slidesToShow: 1,
                slidesToScroll: 1,
            },
        },
    ],
});

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
