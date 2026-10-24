const menuButton = document.querySelector(".menu-toggle");
const navigation = document.querySelector(".primary-nav");

if (menuButton && navigation) {
  menuButton.addEventListener("click", () => {
    const isOpen = menuButton.getAttribute("aria-expanded") === "true";
    menuButton.setAttribute("aria-expanded", String(!isOpen));
    menuButton.setAttribute("aria-label", isOpen ? "Open navigation" : "Close navigation");
    navigation.classList.toggle("is-open", !isOpen);
  });

  navigation.addEventListener("click", (event) => {
    if (event.target.closest("a")) {
      menuButton.setAttribute("aria-expanded", "false");
      menuButton.setAttribute("aria-label", "Open navigation");
      navigation.classList.remove("is-open");
    }
  });
}

const year = document.querySelector("#year");
if (year) year.textContent = new Date().getFullYear();

const leadForm = document.querySelector("#lead-form");
const formStatus = document.querySelector("#form-status");
if (leadForm && formStatus) {
  leadForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (!leadForm.reportValidity()) return;

    const submitButton = leadForm.querySelector('button[type="submit"]');
    if (submitButton) submitButton.disabled = true;
    formStatus.textContent = "Sending your enquiry…";
    formStatus.classList.remove("is-error");

    try {
      const response = await fetch(leadForm.action, {
        method: "POST",
        body: new FormData(leadForm),
        headers: { Accept: "application/json" },
        credentials: "same-origin",
      });
      const result = await response.json();
      if (!response.ok) throw new Error(result.message || "We couldn’t send that just now. Please try again.");
      formStatus.textContent = result.message;
      leadForm.reset();
    } catch (error) {
      formStatus.textContent = error.message || "We couldn’t reach the server. Please try again in a moment.";
      formStatus.classList.add("is-error");
    } finally {
      if (submitButton) submitButton.disabled = false;
    }
  });
}
