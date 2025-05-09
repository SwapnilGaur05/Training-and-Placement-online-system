document.addEventListener("DOMContentLoaded", function () {
  // Mobile menu toggle
  const mobileMenuToggle = document.querySelector(".mobile-menu-toggle");
  if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener("click", function () {
      document.body.classList.toggle("mobile-menu-active");
    });
  }

  // Close mobile menu when clicking outside
  document.addEventListener("click", function (event) {
    if (
      document.body.classList.contains("mobile-menu-active") &&
      !event.target.closest(".mobile-menu-toggle") &&
      !event.target.closest(".main-menu")
    ) {
      document.body.classList.remove("mobile-menu-active");
    }
  });

  // Form validation
  const forms = document.querySelectorAll("form.needs-validation");
  if (forms.length > 0) {
    Array.from(forms).forEach((form) => {
      form.addEventListener(
        "submit",
        function (event) {
          if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
          }
          form.classList.add("was-validated");
        },
        false
      );
    });
  }

  // Password visibility toggle
  const passwordToggles = document.querySelectorAll(".password-toggle");
  if (passwordToggles.length > 0) {
    passwordToggles.forEach((toggle) => {
      toggle.addEventListener("click", function () {
        const passwordField = document.querySelector(this.dataset.target);
        if (passwordField) {
          const type =
            passwordField.getAttribute("type") === "password"
              ? "text"
              : "password";
          passwordField.setAttribute("type", type);
          this.querySelector("i").classList.toggle("fa-eye");
          this.querySelector("i").classList.toggle("fa-eye-slash");
        }
      });
    });
  }

  // File input custom display
  const fileInputs = document.querySelectorAll(".custom-file-input");
  if (fileInputs.length > 0) {
    fileInputs.forEach((input) => {
      input.addEventListener("change", function (e) {
        const fileName = this.files[0]?.name || "No file chosen";
        const fileLabel = this.nextElementSibling;
        if (fileLabel) {
          fileLabel.textContent = fileName;
        }
      });
    });
  }

  // Dropdown menus on hover for desktop
  const dropdowns = document.querySelectorAll(".dropdown");
  if (dropdowns.length > 0 && window.innerWidth > 992) {
    dropdowns.forEach((dropdown) => {
      dropdown.addEventListener("mouseenter", function () {
        this.querySelector(".dropdown-menu").style.display = "block";
      });
      dropdown.addEventListener("mouseleave", function () {
        this.querySelector(".dropdown-menu").style.display = "none";
      });
    });
  }

  // Smooth scrolling for anchor links
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();
      const targetId = this.getAttribute("href");
      if (targetId === "#") return;

      const targetElement = document.querySelector(targetId);
      if (targetElement) {
        window.scrollTo({
          top: targetElement.offsetTop - 70,
          behavior: "smooth",
        });
      }
    });
  });

  // Animated counters
  const counters = document.querySelectorAll(".counter");
  if (counters.length > 0) {
    const counterObserver = new IntersectionObserver(
      (entries, observer) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const counter = entry.target;
            const target = parseInt(counter.getAttribute("data-target"));
            let count = 0;
            const updateCounter = () => {
              const increment = target / 100;
              if (count < target) {
                count += increment;
                counter.innerText = Math.ceil(count);
                setTimeout(updateCounter, 10);
              } else {
                counter.innerText = target;
              }
            };
            updateCounter();
            observer.unobserve(counter);
          }
        });
      },
      { threshold: 0.5 }
    );

    counters.forEach((counter) => {
      counterObserver.observe(counter);
    });
  }

  // Job filter functionality
  const jobFilterForm = document.getElementById("job-filter-form");
  const jobCards = document.querySelectorAll(".job-card");

  if (jobFilterForm && jobCards.length > 0) {
    jobFilterForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      const filters = {
        jobType: formData.get("job_type"),
        location: formData.get("location"),
        keyword: formData.get("keyword").toLowerCase(),
      };

      jobCards.forEach((card) => {
        let shouldShow = true;

        // Filter by job type
        if (filters.jobType && filters.jobType !== "all") {
          const cardJobType = card
            .querySelector(".job-type")
            .textContent.trim();
          if (cardJobType !== filters.jobType) {
            shouldShow = false;
          }
        }

        // Filter by location
        if (shouldShow && filters.location && filters.location !== "all") {
          const cardLocation = card
            .querySelector(".job-location")
            .textContent.trim();
          if (!cardLocation.includes(filters.location)) {
            shouldShow = false;
          }
        }

        // Filter by keyword
        if (shouldShow && filters.keyword) {
          const cardTitle = card
            .querySelector(".job-title h3")
            .textContent.toLowerCase();
          const cardCompany = card
            .querySelector(".company-name")
            .textContent.toLowerCase();
          const cardDescription =
            card.querySelector(".job-description")?.textContent.toLowerCase() ||
            "";

          if (
            !cardTitle.includes(filters.keyword) &&
            !cardCompany.includes(filters.keyword) &&
            !cardDescription.includes(filters.keyword)
          ) {
            shouldShow = false;
          }
        }

        card.style.display = shouldShow ? "block" : "none";
      });
    });

    // Reset filters
    const resetButton = jobFilterForm.querySelector(".reset-filters");
    if (resetButton) {
      resetButton.addEventListener("click", function () {
        jobFilterForm.reset();
        jobCards.forEach((card) => {
          card.style.display = "block";
        });
      });
    }
  }
});
