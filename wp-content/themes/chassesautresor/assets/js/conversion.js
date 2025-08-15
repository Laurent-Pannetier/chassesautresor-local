document.addEventListener("DOMContentLoaded", () => {
    const openModal = document.getElementById("open-conversion-modal");
    const modal = document.getElementById("conversion-modal");

    if (!modal) {
        console.error("❌ ERREUR : Le modal #conversion-modal est introuvable !");
        return;
    }

    const modalContent = modal.querySelector(".points-modal-content");

    const overlay = document.createElement("div");
    overlay.classList.add("modal-overlay");
    document.body.appendChild(overlay);

    const attachCloseHandlers = () => {
        modal.querySelectorAll(".close-modal").forEach((btn) => {
            btn.addEventListener("click", () => {
                modal.style.display = "none";
                overlay.style.display = "none";
            });
        });
    };

    const initForm = () => {
        const inputPoints = document.getElementById("points-a-convertir");
        const montantEquivalent = document.getElementById("montant-equivalent");

        if (inputPoints && montantEquivalent) {
            inputPoints.addEventListener("input", function () {
                const tauxConversion = parseFloat(inputPoints.dataset.taux) || 85;
                const points = parseInt(inputPoints.value) || 0;
                montantEquivalent.textContent = (
                    (points / 1000) * tauxConversion
                ).toFixed(2);
            });
        }
    };

    const openConversionModal = () => {
        fetch("/wp-admin/admin-ajax.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ action: "conversion_modal_content" })
        })
            .then((res) => res.json())
            .then((res) => {
                if (res.success && res.data?.html) {
                    modalContent.innerHTML = res.data.html;
                    attachCloseHandlers();
                    initForm();
                    modal.style.display = "block";
                    overlay.style.display = "block";
                }
            });
    };

    if (openModal) {
        openModal.addEventListener("click", (e) => {
            e.preventDefault();
            openConversionModal();
        });
    }

    overlay.addEventListener("click", () => {
        modal.style.display = "none";
        overlay.style.display = "none";
    });
});
