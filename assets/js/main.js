// Main JavaScript functionality

// Menu toggle for mobile
const menuToggle = document.getElementById("menuToggle")
const sidebar = document.getElementById("sidebar")

if (menuToggle && sidebar) {
  menuToggle.addEventListener("click", () => {
    sidebar.classList.toggle("show")
  })

  // Close sidebar when clicking outside
  document.addEventListener("click", (e) => {
    if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
      sidebar.classList.remove("show")
    }
  })
}

// User menu dropdown
const userMenuToggle = document.getElementById("userMenuToggle")
const userDropdown = document.getElementById("userDropdown")

if (userMenuToggle && userDropdown) {
  userMenuToggle.addEventListener("click", (e) => {
    e.stopPropagation()
    userDropdown.classList.toggle("show")
  })

  // Close dropdown when clicking outside
  document.addEventListener("click", (e) => {
    if (!userDropdown.contains(e.target) && !userMenuToggle.contains(e.target)) {
      userDropdown.classList.remove("show")
    }
  })
}

// Form validation
function validateForm(formId) {
  const form = document.getElementById(formId)
  if (!form) return

  form.addEventListener("submit", (e) => {
    const inputs = form.querySelectorAll("input[required], select[required], textarea[required]")
    let isValid = true

    inputs.forEach((input) => {
      if (!input.value.trim()) {
        isValid = false
        input.style.borderColor = "var(--danger-color)"
      } else {
        input.style.borderColor = "var(--border-color)"
      }
    })

    if (!isValid) {
      e.preventDefault()
      alert("Please fill in all required fields")
    }
  })
}

// Confirm delete
function confirmDelete(message) {
  return confirm(message || "Are you sure you want to delete this item?")
}

// Show notification
function showNotification(message, type = "success") {
  const notification = document.createElement("div")
  notification.className = `alert alert-${type}`
  notification.textContent = message
  notification.style.position = "fixed"
  notification.style.top = "80px"
  notification.style.right = "20px"
  notification.style.zIndex = "1000"
  notification.style.minWidth = "300px"

  document.body.appendChild(notification)

  setTimeout(() => {
    notification.remove()
  }, 3000)
}

// Export table to CSV
function exportTableToCSV(tableId, filename) {
  const table = document.getElementById(tableId)
  if (!table) return

  const csv = []
  const rows = table.querySelectorAll("tr")

  rows.forEach((row) => {
    const cols = row.querySelectorAll("td, th")
    const rowData = Array.from(cols).map((col) => {
      return '"' + col.textContent.trim().replace(/"/g, '""') + '"'
    })
    csv.push(rowData.join(","))
  })

  const csvContent = csv.join("\n")
  const blob = new Blob([csvContent], { type: "text/csv" })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement("a")
  a.href = url
  a.download = filename + ".csv"
  a.click()
  window.URL.revokeObjectURL(url)
}
