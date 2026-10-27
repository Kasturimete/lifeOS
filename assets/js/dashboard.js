/* ===================================
   LIFEOS DASHBOARD
=================================== */


/* ================= PRODUCTIVITY CHART ================= */

const canvas =
    document.getElementById("productivityChart");

if (canvas) {

    const ctx = canvas.getContext("2d");

    const gradient =
        ctx.createLinearGradient(
            0,
            0,
            0,
            250
        );

    gradient.addColorStop(
        0,
        "rgba(145,70,255,0.30)"
    );

    gradient.addColorStop(
        1,
        "rgba(145,70,255,0)"
    );


    new Chart(ctx, {

        type: "line",

        data: {

            labels: [
                "Mon",
                "Tue",
                "Wed",
                "Thu",
                "Fri",
                "Sat",
                "Sun"
            ],

            datasets: [

                {
                    label: "Productivity",

                    data: [
                        42,
                        58,
                        46,
                        72,
                        61,
                        84,
                        76
                    ],

                    borderWidth: 2,

                    borderColor: "#a45cff",

                    backgroundColor: gradient,

                    fill: true,

                    tension: 0.45,

                    pointRadius: 3,

                    pointBackgroundColor: "#c17aff",

                    pointBorderWidth: 0
                }

            ]
        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            animation: {

                duration: 1800,

                easing: "easeOutQuart"

            },

            plugins: {

                legend: {
                    display: false
                }

            },

            scales: {

                x: {

                    grid: {
                        display: false
                    },

                    ticks: {

                        color: "#5e5668",

                        font: {
                            size: 9
                        }
                    }
                },

                y: {

                    beginAtZero: true,

                    max: 100,

                    grid: {

                        color:
                            "rgba(255,255,255,0.035)"
                    },

                    ticks: {

                        color: "#5e5668",

                        font: {
                            size: 8
                        }
                    }
                }

            }

        }

    });

}


/* ================= MOBILE SIDEBAR ================= */

const mobileButton =
    document.querySelector(".mobile-menu");

const sidebar =
    document.querySelector(".sidebar");


if (mobileButton && sidebar) {

    mobileButton.addEventListener(
        "click",
        function () {

            sidebar.classList.toggle("show");

        }
    );

}


/* ================= KPI NUMBER ANIMATION ================= */

function animateNumber(element, target) {

    let current = 0;

    const duration = 1000;

    const start =
        performance.now();


    function update(time) {

        const progress =
            Math.min(
                (time - start) / duration,
                1
            );

        current =
            Math.floor(
                progress * target
            );

        element.textContent =
            current;

        if (progress < 1) {

            requestAnimationFrame(update);

        } else {

            element.textContent =
                target;

        }

    }

    requestAnimationFrame(update);
}


/* ================= CARD GLOW ================= */

const cards =
    document.querySelectorAll(".kpi-card");


cards.forEach(card => {

    card.addEventListener(
        "mousemove",
        function(e) {

            const rect =
                card.getBoundingClientRect();

            const x =
                e.clientX - rect.left;

            const y =
                e.clientY - rect.top;

            card.style.background =
                `radial-gradient(
                    circle at ${x}px ${y}px,
                    rgba(130,55,220,.12),
                    rgba(20,12,30,.9) 45%
                )`;

        }
    );


    card.addEventListener(
        "mouseleave",
        function() {

            card.style.background =
                "";

        }
    );

});