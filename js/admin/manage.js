document.addEventListener("DOMContentLoaded", () => {
  const isSpecialAdmin = document.getElementById('isSpecialAdmin')?.value === '1';
  const currentAdminId = parseInt(document.getElementById('currentAdminId')?.value || "0");

  const tableBody = document.getElementById("records-table-body");
  const tableHeader = document.getElementById("table-header");
  const paginationContainer = document.getElementById("pagination");
  const search = document.getElementById("reportSearch");
  const roleFilter = document.getElementById("roleFilter");
  const region = document.getElementById("regionFilter");
  const regionWrapper = document.getElementById("regionFilterWrapper");
  const dateFilter = document.getElementById("dateFilter");
  const title = document.getElementById("pageTitle");
  const statusFilter = document.getElementById("statusFilter");

  let savedFilters = {};
  try {
    savedFilters = JSON.parse(localStorage.getItem("adminFilters") || "{}");
  } catch {
    localStorage.removeItem("adminFilters");
  }

  if (isSpecialAdmin && savedFilters.role) roleFilter.value = savedFilters.role;
  if (savedFilters.search) search.value = savedFilters.search;
  if (savedFilters.region) region.value = savedFilters.region;
  if (savedFilters.sort) dateFilter.value = savedFilters.sort;
  if (savedFilters.status) {
    statusFilter.value = savedFilters.status;
  } else {
    statusFilter.value = "active"; // 👈 default
  }

  function toggleRegionFilter(forceRole = roleFilter.value) {
    const isHealthcare = forceRole === "healthcare";
    regionWrapper.style.display = isHealthcare ? "block" : "none";
    renderHeader(forceRole);
    title.textContent = isHealthcare ? "Healthcare Staff" : "Admins";
  }

  if (!isSpecialAdmin) {
    roleFilter.value = "healthcare";
    roleFilter.disabled = true;
  }
  toggleRegionFilter();

  roleFilter.addEventListener("change", () => {
    saveFilters();
    toggleRegionFilter();
    loadPage(1);
  });

  [search, region, dateFilter, statusFilter].forEach(input => {
    input.addEventListener("change", () => {
      saveFilters();
      loadPage(1);
    });
  });

  let debounceTimer;
  search.addEventListener("input", () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      saveFilters();
      loadPage(1);
    }, 300);
  });

  function saveFilters() {
    localStorage.setItem("adminFilters", JSON.stringify({
      role: roleFilter.value,
      region: region.value,
      search: search.value,
      sort: dateFilter.value,
      status: statusFilter.value
    }));
  }

  function renderHeader(role) {
    let widths;

    if (role === "healthcare") {
      widths = {
        num: "5%",
        name: "20%",
        username: "15%",
        email: "20%",
        region: "15%",
        registered: "15%",
        action: "10%"
      };
    } else {
      widths = {
        num: "5%",
        name: "25%",
        username: "20%",
        email: "25%",
        registered: "15%",
        action: "10%"
      };
    }

    const common = `
      <th style="width:${widths.num}">#</th>
      <th style="width:${widths.name}">Full Name</th>
      <th style="width:${widths.username}">Username</th>
      <th style="width:${widths.email}">Email</th>`;

    const extra = role === "healthcare"
      ? `<th style="width:${widths.region}">Region</th>`
      : "";

    const action = `
      <th style="width:${widths.registered}">Registered</th>
      <th style="width:${widths.action}; text-align: left;">Actions</th>`;

    tableHeader.innerHTML = `<tr>${common}${extra}${action}</tr>`;
  }

  function formatDate(dateStr) {
    const d = new Date(dateStr);
    return isNaN(d.getTime()) ? "-" : d.toLocaleDateString();
  }

  function loadPage(page = 1) {
    const params = new URLSearchParams({
      search: search.value,
      role: roleFilter.value,
      region: region.value,
      sort: dateFilter.value,
      status: statusFilter.value,
      page
    });

    const colspan = (roleFilter.value === "healthcare") ? 7 : 6;
    tableBody.innerHTML = `<tr><td colspan="${colspan}" style="text-align:center; padding:30px 0;">⏳ Loading...</td></tr>`;

    fetch("/eyecheck/backend/admin/get.php?" + params, { credentials: 'include' })
      .then(res => res.json())
      .then(response => {
        if (!response.success) {
          tableBody.innerHTML = `<tr><td colspan="${colspan}" style="text-align:center;">🚫 Error loading data.</td></tr>`;
          return;
        }
        renderTable(response.data, roleFilter.value, response.currentPage, response.perPage);
        renderPagination(response.total, response.perPage, response.currentPage);
        if (roleFilter.value === "healthcare") updateRegionFilter(response.regions);
      })
      .catch(() => {
        tableBody.innerHTML = `<tr><td colspan="${colspan}" style="text-align:center;">⚠️ Network error.</td></tr>`;
      });
  }

  function renderTable(data, role, currentPage, perPage) {
    const colspan = role === "healthcare" ? 7 : 6;
    if (data.length === 0) {
      const msg = role === 'admin' ? "😐 No admins found." : "😐 No staff found.";
      tableBody.innerHTML = `<tr><td colspan="${colspan}" style="text-align:center;">${msg}</td></tr>`;
      return;
    }

    tableBody.innerHTML = data.map((user, index) => {
      const createdAt = formatDate(user.created_at);
      const isActive = Number(user.is_active) === 1;
      let row = `
        <td>#${(currentPage - 1) * perPage + index + 1}</td>
        <td>${user.full_name}</td>
        <td>${user.username}</td>
        <td>${user.email}</td>`;

      if (role === "healthcare") {
        row += `<td>${user.healthcare_region ?? '-'}</td>`;
      }

      row += `<td>${createdAt}</td>
        <td style="white-space: nowrap;">
          <a href="form.php?id=${user.id}&role=${role}" class="action-btn edit-btn" title="Edit" aria-label="Edit user">
            <i class="fas fa-edit"></i>
          </a>
          <a href="change-password.php?id=${user.id}&role=${role}" class="action-btn password-btn" title="Change Password" aria-label="Change password">
            <i class="fas fa-key"></i>
          </a>`;

      const isSelf = user.id === currentAdminId;

      // --- TOGGLE ACTIVE/INACTIVE ---
      if (isSpecialAdmin && !isSelf) {
        const targetStatus = isActive ? 0 : 1;
        const label = isActive ? "Deactivate" : "Activate";
        const icon = isActive ? "fa-ban" : "fa-rotate-left";
        row += `
          <button class="action-btn toggle-status-btn"
                  data-id="${user.id}"
                  data-target="${targetStatus}"
                  title="${label}" aria-label="${label}">
            <i class="fas ${icon}"></i>
          </button>`;
      } else {
        row += `
          <button class="action-btn" disabled title="No permission">
            <i class="fas fa-ban"></i>
          </button>`;
      }
      // --- END TOGGLE ---

     // Show delete ONLY if Special Admin AND not self AND user is already deactivated
      if (isSpecialAdmin && !isSelf && !isActive) {
        row += `<button class="action-btn open-delete-modal"
                        data-id="${user.id}"
                        data-role="${user.role}"
                        data-type="user"
                        title="Delete"
                        aria-label="Delete user">
                  <i class="fas fa-trash-alt"></i>
                </button>`;
      } else {
        row += `<button class="action-btn disabled-delete-btn"
                        style="opacity: 0.4; cursor: not-allowed;"
                        title="${isActive ? 'You must deactivate first' : 'No permission'}"
                        aria-label="No permission">
                  <i class="fas fa-trash-alt"></i>
                </button>`;
      }

      if (role === "healthcare") {
        row += `
          <button style="margin-left: 10px" class="action-btn print-btn"
              data-id="${user.id}"
              title="Print Full Report"
              aria-label="Print healthcare report">
              <i class="fas fa-print"></i>
          </button>`;
      }

      row += `</td>`;
      return `<tr>${row}</tr>`;
    }).join("");

    bindDeleteButtons();
    bindPrintButtons();
    bindToggleButtons();
  }

  function renderPagination(total, perPage, currentPage) {
    paginationContainer.innerHTML = "";
    const totalPages = Math.ceil(total / perPage);
    if (totalPages <= 1) return;

    for (let i = 1; i <= totalPages; i++) {
      const a = document.createElement("a");
      a.textContent = i;
      a.href = "#";
      if (i === currentPage) a.classList.add("active");
      a.addEventListener("click", e => {
        e.preventDefault();
        loadPage(i);
      });
      paginationContainer.appendChild(a);
    }
  }

  function updateRegionFilter(regions) {
    region.innerHTML = "<option value='all'>All</option>";
    regions.forEach(r => {
      const opt = document.createElement("option");
      opt.value = r;
      opt.textContent = r;
      region.appendChild(opt);
    });
    if (savedFilters.region) region.value = savedFilters.region;
  }

  function openDeleteModal(id, role, type = 'user') {
    const overlay = document.querySelector(".delete-modal-overlay");
    overlay.classList.add("active");

    const confirmBtn = overlay.querySelector(".delete-confirm-btn");
    confirmBtn.replaceWith(confirmBtn.cloneNode(true));
    const newConfirmBtn = overlay.querySelector(".delete-confirm-btn");

    newConfirmBtn.addEventListener("click", () => {
      const bodyData = new URLSearchParams({ target_user_id: id, role: 'admin'});
      fetch("/eyecheck/backend/shared/delete-handler.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: bodyData,
        credentials: "include"
      })
        .then(res => res.json())
        .then(data => {
          overlay.classList.remove("active");
          showToast(data.message || "User deleted.", data.success ? "success" : "error");
          if (data.success) loadPage(1);
        })
        .catch(() => {
          overlay.classList.remove("active");
          showToast("Server error. Try again.", "error");
        });
    });

    overlay.querySelector(".cancel-btn").onclick = () => {
      overlay.classList.remove("active");
    };
  }

  function openStatusModal(id, target) {
    const overlay = document.querySelector(".delete-modal-overlay");
    const modal = overlay.querySelector(".delete-modal");
    const iconEl = modal.querySelector(".delete-icon i");
    const titleEl = modal.querySelector("h2");
    const textEl = modal.querySelector("p");
    const confirmBtn = modal.querySelector(".delete-confirm-btn");
    const cancelBtn = modal.querySelector(".cancel-btn");

    // Save defaults once so we can restore after use (keeps Delete modal intact)
    if (!overlay.dataset.defaultTitle) {
      overlay.dataset.defaultTitle = titleEl.textContent;
      overlay.dataset.defaultText = textEl.textContent;
      overlay.dataset.defaultIcon = iconEl.className;
      overlay.dataset.defaultConfirm = confirmBtn.textContent;
    }

    const isDeactivation = target === 0;

    // Customize modal for Activate/Deactivate
    titleEl.textContent = isDeactivation ? "Confirm Deactivation" : "Confirm Activation";
    textEl.textContent = isDeactivation
      ? "This user will be prevented from signing in and performing any actions."
      : "This user will regain access and can sign in again.";
    iconEl.className = isDeactivation ? "fas fa-ban" : "fas fa-rotate-left";
    confirmBtn.textContent = isDeactivation ? "Deactivate" : "Activate";

    // Reset previous listeners on the confirm button
    confirmBtn.replaceWith(confirmBtn.cloneNode(true));
    const newConfirmBtn = modal.querySelector(".delete-confirm-btn");

    newConfirmBtn.addEventListener("click", () => {
      const bodyData = new URLSearchParams({ target_user_id: id, status: String(target) });
      fetch("/eyecheck/backend/admin/toggle-status.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: bodyData,
        credentials: "include"
      })
        .then(r => r.json())
        .then(data => {
          overlay.classList.remove("active");
          restoreDeleteModal(overlay); // restore original delete modal copy/icon
          showToast(data.message || "Updated.", data.success ? "success" : "error");
          if (data.success) loadPage(1);
        })
        .catch(() => {
          overlay.classList.remove("active");
          restoreDeleteModal(overlay);
          showToast("Server error. Try again.", "error");
        });
    });

    // Cancel restores modal to its original delete state
    cancelBtn.onclick = () => {
      overlay.classList.remove("active");
      restoreDeleteModal(overlay);
    };

    // Show modal
    overlay.classList.add("active");
  }

  function restoreDeleteModal(overlay) {
    const modal = overlay.querySelector(".delete-modal");
    const iconEl = modal.querySelector(".delete-icon i");
    const titleEl = modal.querySelector("h2");
    const textEl = modal.querySelector("p");
    const confirmBtn = modal.querySelector(".delete-confirm-btn");

    if (overlay.dataset.defaultTitle) {
      titleEl.textContent = overlay.dataset.defaultTitle;
      textEl.textContent = overlay.dataset.defaultText;
      iconEl.className = overlay.dataset.defaultIcon;
      confirmBtn.textContent = overlay.dataset.defaultConfirm;
    }
  }

  function bindDeleteButtons() {
    document.querySelectorAll(".open-delete-modal").forEach(btn => {
      btn.addEventListener("click", () => {
        const id = parseInt(btn.dataset.id);
        const role = btn.dataset.role;
        if (!isSpecialAdmin || id === currentAdminId) {
          showToast("You don’t have the authority to delete this.", 'error');
          return;
        }
        openDeleteModal(id, role);
      });
    });

    document.querySelectorAll(".disabled-delete-btn").forEach(btn => {
      btn.addEventListener("click", () => {
        const msg = btn.getAttribute("title") || "You don’t have permission to delete this user.";
        showToast(msg, 'error');
      });
    });
  }

  function bindPrintButtons() {
    document.querySelectorAll(".print-btn").forEach(btn => {
      btn.addEventListener("click", () => {
        const id = parseInt(btn.dataset.id);
        const url = `/eyecheck/admin/print-report.php?type=healthcare&id=${id}`;
        window.open(url, "_blank", "noopener,noreferrer");
      });
    });
  }

  // NEW: Toggle activate/deactivate
  function bindToggleButtons() {
    document.querySelectorAll(".toggle-status-btn").forEach(btn => {
      btn.addEventListener("click", () => {
        const id = parseInt(btn.dataset.id, 10);
        const target = parseInt(btn.dataset.target, 10); // 0=deactivate, 1=activate

        if (!isSpecialAdmin || id === currentAdminId) {
          showToast("You can’t change your own status.", 'error');
          return;
        }

        // Open the same modal used for deletion, but with dynamic copy/icons
        openStatusModal(id, target);
      });
    });
  }

  loadPage(1);
});
