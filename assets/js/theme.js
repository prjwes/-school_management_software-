// Theme management
;(() => {
  const themeToggle = document.getElementById("themeToggle")
  const html = document.documentElement

  // Load saved theme
  const savedTheme = localStorage.getItem("theme") || "light"
  html.setAttribute("data-theme", savedTheme)
  updateThemeIcon(savedTheme)

  // Toggle theme
  if (themeToggle) {
    themeToggle.addEventListener("click", () => {
      const currentTheme = html.getAttribute("data-theme")
      const newTheme = currentTheme === "light" ? "dark" : "light"

      html.setAttribute("data-theme", newTheme)
      localStorage.setItem("theme", newTheme)
      updateThemeIcon(newTheme)
    })
  }

  function updateThemeIcon(theme) {
    const icon = document.querySelector(".theme-icon")
    if (icon) {
      icon.textContent = theme === "light" ? "🌙" : "☀️"
    }
  }
})()
