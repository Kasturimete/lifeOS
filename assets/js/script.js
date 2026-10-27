/* ================================
   LIFEOS - ANIMATIONS
================================ */


/* ---------- Typing Animation ---------- */

const typing = document.getElementById("typing");

const words = [
    "plan your day.",
    "track your goals.",
    "build better habits.",
    "manage your finances.",
    "grow your career.",
    "learn smarter."
];

let word = 0;
let letter = 0;
let deleting = false;


function typingEffect() {

    const current = words[word];

    if (!deleting) {

        typing.textContent =
            current.substring(0, letter + 1);

        letter++;

        if (letter === current.length) {

            deleting = true;

            setTimeout(typingEffect, 1400);

            return;
        }

    } else {

        typing.textContent =
            current.substring(0, letter - 1);

        letter--;

        if (letter === 0) {

            deleting = false;

            word++;

            if (word >= words.length) {
                word = 0;
            }
        }
    }

    setTimeout(
        typingEffect,
        deleting ? 40 : 80
    );
}

typingEffect();



/* ---------- Dashboard Mouse Effect ---------- */

const dashboard =
    document.querySelector(".dashboard");

document.addEventListener("mousemove", function(event) {

    if (!dashboard) return;

    const x =
        (window.innerWidth / 2 - event.clientX) / 100;

    const y =
        (window.innerHeight / 2 - event.clientY) / 100;

    dashboard.style.transform =
        `perspective(1200px)
         rotateY(${x * -0.7}deg)
         rotateX(${y * 0.3}deg)`;

});



/* ---------- Reveal Animation ---------- */

const elements =
    document.querySelectorAll(
        ".feature, .ai-section, .cta"
    );


const observer =
    new IntersectionObserver(

        function(entries) {

            entries.forEach(entry => {

                if (entry.isIntersecting) {

                    entry.target.style.opacity = "1";

                    entry.target.style.transform =
                        "translateY(0)";

                }

            });

        },

        {
            threshold: 0.12
        }

    );


elements.forEach(element => {

    element.style.opacity = "0";

    element.style.transform =
        "translateY(40px)";

    element.style.transition =
        "opacity .8s ease, transform .8s ease";

    observer.observe(element);

});



/* ---------- AI Chat subtle movement ---------- */

const aiChat =
    document.querySelector(".ai-chat");

if (aiChat) {

    setInterval(() => {

        aiChat.style.boxShadow =
            "0 30px 80px rgba(0,0,0,.4), 0 0 30px rgba(130,55,230,.08)";

        setTimeout(() => {

            aiChat.style.boxShadow =
                "0 30px 80px rgba(0,0,0,.4)";

        }, 1000);

    }, 2500);

}